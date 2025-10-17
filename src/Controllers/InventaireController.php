<?php

namespace App\Controllers;

use App\Models\Database;
use App\Models\Maintainer_model;
use App\Models\AuditTrail_model;

class InventaireController
{
    public function AddInventaire()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $isAdmin = isset($_SESSION['qualification']) && $_SESSION['qualification'] === 'ADMINISTRATEUR';
            if ($isAdmin) {
                $this->handleAddInventaireAdmin();
                return;
            } else {
                $this->handleAddInventaireMaintainer();
                return;
            }
        }
        // Préparer les données pour le formulaire
        $db = Database::getInstance('db_digitex');
        $conn = $db->getConnection();

        $isAdmin = isset($_SESSION['qualification']) && $_SESSION['qualification'] === 'ADMINISTRATEUR';
        $connectedMaintenerId = null;

        // Options maintenanciqualificationer (si admin)
        if ($isAdmin) {
            $maintenancier = Maintainer_model::findAll();
        } else {
            $connectedMaintenerId = $_SESSION['user']['id'] ?? null;
            $connectedMatricule = $_SESSION['user']['matricule'];
        }
        // Options location
        $stmt = $conn->query("SELECT id, location_name FROM gmao__location ORDER BY location_name ASC");
        $locationOptions = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Options status
        $stmt = $conn->query("SELECT id, status_name FROM gmao__status ORDER BY status_name ASC");
        $statusOptions = $stmt->fetchAll(\PDO::FETCH_ASSOC);


        include __DIR__ . '/../views/inventaire/add_inventaire.php';
    }

    private function handleAddInventaireMaintainer()
    {
        $machineIds = $_POST['machines_ids'] ?? '';
        $maintener_id = $_POST['maintener_id'] ?? '';
        $location_id = $_POST['location_id'] ?? '';
        $status_id = $_POST['status_id'] ?? '';
        $db = Database::getInstance('db_digitex');
        $conn = $db->getConnection();

        try {

            $machineIds = is_array($machineIds) ? $machineIds : (trim((string)$machineIds) !== '' ? [trim((string)$machineIds)] : []);
            if (count($machineIds) === 0) {
                $_SESSION['flash_error'] = "Au moins une machine est requise";
                header('Location: index.php?route=ajouterInventaire');
                return;
            }
            $machineNotfound = [];
            $ok = 0;
            $conn->beginTransaction();

            $resolveStmt = $conn->prepare("SELECT id FROM init__machine WHERE machine_id = :mid LIMIT 1");
            $insertInv = $conn->prepare("INSERT INTO gmao__historique_inventaire (machine_id, maintainer_id, location_id, status_id, created_at) VALUES (:machine_id, :maintainer_id, :location_id, :status_id, :created_at)");

            foreach ($machineIds as $displayMid) {
                $displayMid = trim((string)$displayMid);
                if ($displayMid === '') {
                    continue;
                }
                // Resolve init__machine.id from public machine_id
                $resolveStmt->execute([':mid' => $displayMid]);
                $resolvedId = $resolveStmt->fetchColumn();
                if (!$resolvedId) {
                    $machineNotfound[] = $displayMid;
                    continue;
                }


                // Insert inventory row
                $insertInv->execute([
                    ':machine_id' => (int)$resolvedId,
                    ':maintainer_id' => $maintener_id !== '' ? (int)$maintener_id : null,
                    ':location_id' => $location_id !== '' ? (int)$location_id : null,
                    ':status_id' => $status_id !== '' ? (int)$status_id : null,
                    ':created_at' => date('Y-m-d H:i:s')
                ]);
                $ok++;
                //insert into gmao__machine_maint
                $stmt = $conn->prepare("INSERT INTO gmao__machine_maint (machine_id, maintener_id, location_id) VALUES (:machine_id, :maintener_id, :location_id)");
                $stmt->execute([':machine_id' => $resolvedId, ':maintener_id' => $maintener_id, ':location_id' => $location_id]);
                //update into init__machine location_id and status_id
                $stmt = $conn->prepare("UPDATE init__machine SET machines_status_id = :status_id, machines_location_id = :location_id WHERE id = :machine_id");
                $stmt->execute([':status_id' => $status_id, ':location_id' => $location_id, ':machine_id' => $resolvedId]);

                // Audit trail pour non-admin
                $this->logAuditNonAdmin($resolvedId, $maintener_id, $location_id, $status_id);
            }

            $conn->commit();
            if ($machineNotfound !== []) {
                $_SESSION['flash_error'] = " Machines introuvables: " . (count($machineNotfound) ? implode(',machine id ', $machineNotfound) : '0');
            } else {
                $_SESSION['flash_success'] = "Machine inventairees avec succès!, $ok machines ajoutées";
            }
        } catch (\Throwable $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            $_SESSION['flash_error'] = 'Erreur: ' . $e->getMessage();
        }

        header('Location: index.php?route=ajouterInventaire');
    }
    public function handleAddInventaireAdmin()
    {
        $machineIds = $_POST['machines_ids'] ?? '';
        $maintener_id = $_POST['maintener_id'] ?? '';
        $location_id = $_POST['location_id'] ?? '';
        $status_id = $_POST['status_id'] ?? '';
        $db = Database::getInstance('db_digitex');
        $conn = $db->getConnection();

        try {
            $machineIds = is_array($machineIds) ? $machineIds : (trim((string)$machineIds) !== '' ? [trim((string)$machineIds)] : []);
            if (count($machineIds) === 0) {
                $_SESSION['flash_error'] = "Au moins une machine est requise";
                header('Location: index.php?route=ajouterInventaire');
                return;
            }
            $machineNotfound = [];
            $ok = 0;
            $conn->beginTransaction();
            $resolveStmt = $conn->prepare("SELECT id FROM init__machine WHERE machine_id = :mid LIMIT 1");
            $insertInv = $conn->prepare("INSERT INTO gmao__historique_inventaire (machine_id, maintainer_id, location_id, status_id, created_at) VALUES (:machine_id, :maintainer_id, :location_id, :status_id, :created_at)");
            foreach ($machineIds as $displayMid) {
                $displayMid = trim((string)$displayMid);
                if ($displayMid === '') {
                    continue;
                }
                $resolveStmt->execute([':mid' => $displayMid]);
                $resolvedId = $resolveStmt->fetchColumn();
                if (!$resolvedId) {
                    $machineNotfound[] = $displayMid;
                    continue;
                }
                $insertInv->execute([
                    ':machine_id' => (int)$resolvedId,
                    ':maintainer_id' => (int)$maintener_id,
                    ':location_id' => $location_id !== '' ? (int)$location_id : null,
                    ':status_id' => $status_id !== '' ? (int)$status_id : null,
                    ':created_at' => date('Y-m-d H:i:s')
                ]);
                $ok++;

                // Audit trail pour admin (seulement gmao__historique_inventaire)
                $this->logAuditAdmin($resolvedId, $maintener_id, $location_id, $status_id);
            }
            $conn->commit();
            $_SESSION['flash_success'] = "Machine ajoutée avec succès: $ok, Machines introuvables: " . (count($machineNotfound) ? implode(',machine id ', $machineNotfound) : '0');
        } catch (\Throwable $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            $_SESSION['flash_error'] = 'Erreur: ' . $e->getMessage();
        }
        header('Location: index.php?route=ajouterInventaire');
    }
    //audit trails
    private function logAuditNonAdmin($machineId, $maintenerId, $locationId, $statusId)
    {
        try {
            $userMatricule = $_SESSION['user']['matricule'] ?? null;
            if (!$userMatricule) return;

            // 1. gmao__historique_inventaire (ADD)
            $newValue = [
                'machine_id' => $machineId,
                'maintainer_id' => $maintenerId,
                'location_id' => $locationId,
                'status_id' => $statusId,
                'created_at' => date('Y-m-d H:i:s')
            ];
            AuditTrail_model::logAudit($userMatricule, 'add', 'gmao__historique_inventaire', null, $newValue);

            // 2. init__machine (UPDATE)
            $db = Database::getInstance('db_digitex');
            $conn = $db->getConnection();
            $oldValuesStmt = $conn->prepare("SELECT machines_status_id, machines_location_id FROM init__machine WHERE id = :machine_id");
            $oldValuesStmt->execute([':machine_id' => $machineId]);
            $oldValues = $oldValuesStmt->fetch(\PDO::FETCH_ASSOC);

            $oldValue = [
                'id' => $machineId,
                'machines_status_id' => $oldValues['machines_status_id'],
                'machines_location_id' => $oldValues['machines_location_id']
            ];

            $newValue = [
                'id' => $machineId,
                'machines_status_id' => $statusId,
                'machines_location_id' => $locationId
            ];
            AuditTrail_model::logAudit($userMatricule, 'update', 'init__machine', $oldValue, $newValue);

            // 3. gmao__machine_maint (ADD)
            $newValue = [
                'machine_id' => $machineId,
                'maintener_id' => $maintenerId,
                'location_id' => $locationId,
                'created_at' => date('Y-m-d H:i:s')
            ];
            AuditTrail_model::logAudit($userMatricule, 'add', 'gmao__machine_maint', null, $newValue);
        } catch (\Throwable $e) {
            error_log("Erreur audit non-admin: " . $e->getMessage());
        }
    }

    private function logAuditAdmin($machineId, $maintenerId, $locationId, $statusId)
    {
        try {
            $userMatricule = $_SESSION['user']['matricule'] ?? null;
            if (!$userMatricule) return;

            // gmao__historique_inventaire (ADD seulement)
            $newValue = [
                'machine_id' => $machineId,
                'maintainer_id' => $maintenerId,
                'location_id' => $locationId,
                'status_id' => $statusId,
                'created_at' => date('Y-m-d H:i:s')
            ];
            AuditTrail_model::logAudit($userMatricule, 'add', 'gmao__historique_inventaire', null, $newValue);
        } catch (\Throwable $e) {
            error_log("Erreur audit admin: " . $e->getMessage());
        }
    }
    // public function maintenancier_machine()
    // {
    //     $db = Database::getInstance('db_digitex');
    //     $conn = $db->getConnection();

    //     // Récupérer le matricule de l'utilisateur connecté
    //     $connectedMatricule = $_SESSION['user']['matricule'] ?? null;
    //     $isAdmin = isset($_SESSION['qualification']) && $_SESSION['qualification'] === 'ADMINISTRATEUR';

    //     // Filtres pour admin
    //     $filterMatricule = $_GET['matricule'] ?? '';
    //     $filterChaine = $_GET['chaine'] ?? '';
    //     $filterMachine = $_GET['machine'] ?? '';

    //     // Construire la requête de base - une ligne par machine
    //     $sql = "
    //         SELECT
    //             e.matricule AS maintener_name,
    //             e.first_name,
    //             e.last_name,
    //             mm.maintener_id,
    //             m.reference AS machine_reference,
    //             m.machine_id AS machine_id,
    //             COALESCE(s.status_name, 'non défini') AS machine_status,
    //             l.location_name AS location_name,
    //             l.location_category AS location_category
    //         FROM (
    //             SELECT mm.*
    //             FROM gmao__machine_maint mm
    //             INNER JOIN (
    //                 SELECT MAX(id) AS id
    //                 FROM gmao__machine_maint
    //                 GROUP BY machine_id
    //             ) last ON last.id = mm.id
    //         ) mm
    //         LEFT JOIN init__employee e ON e.id = mm.maintener_id
    //         LEFT JOIN init__machine m ON m.id = mm.machine_id
    //         LEFT JOIN gmao__location l ON l.id = mm.location_id
    //         LEFT JOIN gmao__status s ON s.id = m.machines_status_id
    //         WHERE 1=1
    //     ";

    //     $params = [];

    //     // Si admin, appliquer les filtres
    //     if ($isAdmin) {
    //         if (!empty($filterMatricule)) {
    //             $sql .= " AND e.matricule = :matricule";
    //             $params['matricule'] = $filterMatricule;
    //         }
    //         if (!empty($filterChaine)) {
    //             $sql .= " AND l.location_name = :chaine";
    //             $params['chaine'] = $filterChaine;
    //         }
    //         if (!empty($filterMachine)) {
    //             // Si le filtre contient " - ", c'est un format "ID - Reference"
    //             if (strpos($filterMachine, ' - ') !== false) {
    //                 $parts = explode(' - ', $filterMachine, 2);
    //                 $sql .= " AND (m.machine_id = :machine_id AND m.reference = :machine_ref)";
    //                 $params['machine_id'] = trim($parts[0]);
    //                 $params['machine_ref'] = trim($parts[1]);
    //             } else {
    //                 // Sinon chercher par ID ou référence
    //                 $sql .= " AND (m.machine_id = :machine OR m.reference = :machine_ref)";
    //                 $params['machine'] = $filterMachine;
    //                 $params['machine_ref'] = $filterMachine;
    //             }
    //         }
    //     } else {
    //         // Si pas admin, filtrer par matricule connecté
    //         if ($connectedMatricule) {
    //             $sql .= " AND e.matricule = :matricule";
    //             $params['matricule'] = $connectedMatricule;
    //         }
    //     }

    //     $sql .= " ORDER BY e.matricule ASC, m.reference ASC";

    //     $stmt = $conn->prepare($sql);
    //     $stmt->execute($params);
    //     $maintenances = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    //     // Récupérer les options pour les filtres admin
    //     $chainOptions = [];
    //     $machineOptions = [];
    //     $matriculeOptions = [];

    //     if ($isAdmin) {
    //         // Récupérer toutes les chaînes
    //         $stmt = $conn->query("SELECT DISTINCT location_name FROM gmao__location WHERE location_category IN ('prodline', 'parc') ORDER BY location_name");
    //         $chains = $stmt->fetchAll(\PDO::FETCH_COLUMN);
    //         $chainOptions = array_combine($chains, $chains);

    //         // Récupérer toutes les machines avec ID et référence
    //         $stmt = $conn->query("SELECT DISTINCT machine_id, reference FROM init__machine ORDER BY reference");
    //         $machinesData = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    //         $machineOptions = [];
    //         foreach ($machinesData as $machine) {
    //             $machineId = $machine['machine_id'] ?? '';
    //             $machineRef = $machine['reference'] ?? '';
    //             if (!empty($machineId) && !empty($machineRef)) {
    //                 $displayValue = $machineId . ' - ' . $machineRef;
    //                 $machineOptions[$displayValue] = $displayValue;
    //             } elseif (!empty($machineId)) {
    //                 $machineOptions[$machineId] = $machineId;
    //             } elseif (!empty($machineRef)) {
    //                 $machineOptions[$machineRef] = $machineRef;
    //             }
    //         }

    //         // Récupérer tous les matricules de maintenanciers
    //         $stmt = $conn->query("SELECT  DISTINCT e.matricule FROM init__employee e 
    //            where e.qualification = 'MAINTAINER'
    //             ORDER BY e.matricule");
    //         $matricules = $stmt->fetchAll(\PDO::FETCH_COLUMN);
    //         $matriculeOptions = array_combine($matricules, $matricules);
    //     }

    //     include __DIR__ . '/../views/inventaire/maintenancier_machine.php';
    // }
    // public function maintenance_default()
    // {
    //     $db = Database::getInstance('db_digitex');
    //     $conn = $db->getConnection();
    //     $sql = "
    //         SELECT
    //             e.matricule AS maintener_name,
    //             mm.maintener_id,
    //             GROUP_CONCAT(DISTINCT CONCAT(m.reference, '|', COALESCE(s.status_name, 'unknown')) ORDER BY m.reference SEPARATOR '||') AS machines_with_status,
    //             GROUP_CONCAT(DISTINCT l.location_name ORDER BY l.location_name SEPARATOR ', ') AS chains_list
    //         FROM (
    //             SELECT mm.*
    //             FROM gmao__machine_maint mm
    //             INNER JOIN (
    //                 SELECT MAX(id) AS id
    //                 FROM gmao__machine_maint
    //                 GROUP BY machine_id
    //             ) last ON last.id = mm.id
    //         ) mm
    //         LEFT JOIN init__employee e ON e.id = mm.maintener_id
    //         LEFT JOIN init__machine m ON m.id = mm.machine_id
    //         LEFT JOIN gmao__location l ON l.id = mm.location_id
    //         LEFT JOIN gmao__status s ON s.id = m.machines_status_id
    //         GROUP BY mm.maintener_id
    //         ORDER BY e.matricule ASC
    //     ";
    //     $stmt = $conn->query($sql);
    //     $maintenances = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    //     include __DIR__ . '/../views/inventaire/maintenance_defaultold.php';
    // }
}
