<?php

namespace App\Controllers;

use App\Models\Database;
use App\Models\Maintainer_model;
use App\Models\AuditTrail_model;
use App\Controllers\HistoriqueInventaireController;
use App\Models\HistoriqueInventaire_model;

class InventaireController
{
    public function AddInventaire()
    {

        $isAdmin = isset($_SESSION['qualification']) && $_SESSION['qualification'] === 'ADMINISTRATEUR';
        $connectedMaintenerId = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleAddInventaire($isAdmin);
        }
        // Préparer les données pour le formulaire
        $db = Database::getInstance('db_digitex');
        $conn = $db->getConnection();



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


    public function handleAddInventaire($isAdmin)
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
                $inventory_date[] = [
                    'machine_id' => (int)$resolvedId,
                    'maintainer_id' => (int)$maintener_id,
                    'location_id' => $location_id !== '' ? (int)$location_id : null,
                    'status_id' => $status_id !== '' ? (int)$status_id : null
                ];


                $comparison_result = $this->comparisons($conn, $resolvedId, $maintener_id, $location_id, $status_id, $inventory_date);
                if ($comparison_result) {
                    HistoriqueInventaire_model::insert_Evaluation_inventaire($comparison_result);
                    $ok++;
                }

                if (!$isAdmin) {
                    //insert into gmao__machine_maint
                    $stmt = $conn->prepare("INSERT INTO gmao__machine_maint (machine_id, maintener_id, location_id) VALUES (:machine_id, :maintener_id, :location_id)");
                    $stmt->execute([':machine_id' => $resolvedId, ':maintener_id' => $maintener_id, ':location_id' => $location_id]);
                    //update into init__machine location_id and status_id
                    $stmt = $conn->prepare("UPDATE init__machine SET machines_status_id = :status_id, machines_location_id = :location_id WHERE id = :machine_id");
                    $stmt->execute([':status_id' => $status_id, ':location_id' => $location_id, ':machine_id' => $resolvedId]);

                    // Audit trail pour non-admin
                    $this->logAuditNonAdmin($resolvedId, $maintener_id, $location_id, $status_id);
                }
            }

            $conn->commit();
            if ($ok > 0) {
                $_SESSION['flash_success'] = "Machine ajoutée avec succès: $ok";
                if (count($machineNotfound) > 0) {
                    $_SESSION['flash_success'] .= ", Machines introuvables: " . implode(', ', $machineNotfound);
                }
            } else {
                $_SESSION['flash_error'] = "Aucune machine n'a été traitée. Machines introuvables: " . implode(', ', $machineNotfound);
            }
        } catch (\Exception $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            $_SESSION['flash_error'] = 'Erreur: ' . $e->getMessage();
        }

        header('Location: index.php?route=ajouterInventaire');
        exit;
    }
    /**
     * Compare les données actuelles avec les données d'inventaire
     */
    private function comparisons($conn, $resolvedId, $maintener_id, $location_id, $status_id, $inventory_date)
    {
        $isAdmin = isset($_SESSION['qualification']) && $_SESSION['qualification'] === 'ADMINISTRATEUR';
        $userMatricule = $_SESSION['user']['matricule'] ?? null;
        // Récupérer les données actuelles de la machine
        $current_data = "
            SELECT
                m.id as id_machine,
                m.machine_id,
                m.reference,
                m.type,
                m.machines_location_id,
                m.machines_status_id,
                mm.maintener_id,
                l.location_name,
                l.location_category,
                s.status_name,
                CONCAT(e.first_name, ' ', e.last_name) as maintainer_name,
                -- Statut de production basé sur la présence (seulement si status est active)
                CASE 
                    WHEN s.status_name = 'active' THEN
                        CASE 
                            WHEN pp_today.id IS NOT NULL THEN 'active'
                            ELSE 'inactive'
                        END
                    ELSE s.status_name
                END AS production_status
            FROM init__machine m
            LEFT JOIN gmao__location l ON l.id = m.machines_location_id
            LEFT JOIN gmao__status s ON s.id = m.machines_status_id
            LEFT JOIN (
                SELECT mm.*
                FROM gmao__machine_maint mm
                INNER JOIN (
                    SELECT machine_id, max(id) as max_id
                    FROM gmao__machine_maint
                    GROUP BY machine_id
                ) latest ON mm.machine_id = latest.machine_id AND mm.id = latest.max_id
            ) mm ON mm.machine_id = m.id
            LEFT JOIN init__employee e ON e.id = mm.maintener_id
            -- Dernière présence du jour
            LEFT JOIN (
                SELECT p1.*
                FROM prod__presence p1
                INNER JOIN (
                    SELECT machine_id, MAX(id) AS last_id
                    FROM prod__presence
                    WHERE cur_date = CURDATE()
                    GROUP BY machine_id
                ) t ON t.machine_id = p1.machine_id AND t.last_id = p1.id
            ) pp_today ON pp_today.machine_id = m.machine_id
            WHERE m.id = :machine_id
        ";

        // Récupérer les noms pour l'inventaire
        $inventory_names = "
            SELECT 
                l.location_name as inventory_location_name,
                s.status_name as inventory_status_name,
                CONCAT(e.first_name, ' ', e.last_name) as inventory_maintainer_name
            FROM gmao__location l, gmao__status s, init__employee e
            WHERE l.id = :location_id 
            AND s.id = :status_id 
            AND e.id = :maintainer_id
        ";

        $current_dataStmt = $conn->prepare($current_data);
        $current_dataStmt->execute([':machine_id' => $resolvedId]);
        $current_data_result = $current_dataStmt->fetchAll(\PDO::FETCH_ASSOC);
        // Récupérer les noms pour l'inventaire
        $inventory_namesStmt = $conn->prepare($inventory_names);
        $inventory_namesStmt->execute([
            ':location_id' => $location_id,
            ':status_id' => $status_id,
            ':maintainer_id' => $maintener_id
        ]);
        $inventory_names_data = $inventory_namesStmt->fetchAll(\PDO::FETCH_ASSOC);

        // Comparaison entre current_data et inventory_date
        $current = $current_data_result[0] ?? null;
        $inventory = $inventory_date[0] ?? null;
        $inventory_names = $inventory_names_data[0] ?? null;

        if ($current && $inventory) {
            $differences = [];
            $status = 'conforme';

            // Comparer location
            if ($current['machines_location_id'] != $inventory['location_id']) {
                $differences[] = '*Localisation modifiée: ' . ($current['location_name'] ?? 'Non défini') . ' → ' . ($inventory_names['inventory_location_name'] ?? 'Non défini');
                $status = 'non_conforme';
            }
            //production_status
            // Comparer status (utiliser production_status)
            $currentStatusName = $current['production_status'];
            $inventoryStatusName = $inventory_names['inventory_status_name'];

            // if ($current['machines_status_id'] != $inventory['status_id']) {
            if ($currentStatusName != $inventoryStatusName) {
                $differences[] = '*Statut modifié: ' . ($currentStatusName ?? 'Non défini') . ' → ' . ($inventory_names['inventory_status_name'] ?? 'Non défini');
                $status = 'non_conforme';
            }


            // Comparer maintainer

            if ($current['maintener_id'] != $inventory['maintainer_id']) {
                if (!$isAdmin && $userMatricule != $current['maintener_id']) {
                    // Pour les non-admins qui n'étaient pas le maintenancier actuel
                    $differences[] = '*Machine ajoutée : '
                        . ($current['maintainer_name'] ?? 'Non défini')
                        . ' → '
                        . ($inventory_names['inventory_maintainer_name'] ?? 'Non défini');
                    $status = 'ajoute';
                } else {
                    // Pour les admins ou si l'utilisateur était le maintenancier actuel
                    $differences[] = '*Maintenancier modifié : '
                        . ($current['maintainer_name'] ?? 'Non défini')
                        . ' → '
                        . ($inventory_names['inventory_maintainer_name'] ?? 'Non défini');
                    $status = 'non_conforme';
                }
            }


            return [
                'machine_id' => $current['machine_id'],
                'reference' => $current['reference'],
                'type' => $current['type'],
                'status' => $status,
                'differences' => implode('<br>', $differences),
                'current_location_name' => $current['location_name'] ?? 'non defini',
                'current_status' => $currentStatusName ?? 'non defini',
                'current_maintainer_name' => $current['maintener_id'] ?? 'non defini',
                'inventory_maintainer_name' => $inventory['maintainer_id'] ?? 'non defini',
            ];
        }

        return null;
    }
    //audit trails
    private function logAuditNonAdmin($machineId, $maintenerId, $locationId, $statusId)
    {
        try {
            $userMatricule = $_SESSION['user']['matricule'] ?? null;
            if (!$userMatricule) return;
            // 1. init__machine (UPDATE)
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

            // 2. gmao__machine_maint (ADD)
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
}
