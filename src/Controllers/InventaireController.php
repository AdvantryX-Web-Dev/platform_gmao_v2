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
        // Préparer les données pour le formulaire
        $db = Database::getInstance('db_digitex');
        $conn = $db->getConnection();


        $isAdmin = isset($_SESSION['qualification']) && $_SESSION['qualification'] === 'ADMINISTRATEUR';
        $connectedMaintenerId = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleAddInventaire($isAdmin);
        }

        // Options maintenanciqualificationer (si admin)
        if ($isAdmin) {
            $maintenancier = Maintainer_model::findAll();
        } else {
            $connectedMaintenerId = $_SESSION['user']['id'] ?? null;
            $connectedMatricule = $_SESSION['user']['matricule'];
        }
        // Options location
        $stmt = $conn->query("SELECT id, location_name FROM gmao__location  ORDER BY location_name ASC");
        $locationOptions = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Options status (restricted to active, inactive, en panne, ferraille in this order)
        $stmt = $conn->prepare(
            "SELECT id, status_name
             FROM gmao__status
             WHERE status_name IN ('active','inactive','en panne','ferraille')
             ORDER BY FIELD(status_name,'active','inactive','en panne','ferraille')"
        );
        $stmt->execute();
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
            $inventory_data = [];
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
                $inventory_data[] = [
                    'machine_id' => (int)$resolvedId,
                    'maintainer_id' => (int)$maintener_id,
                    'location_id' => $location_id !== '' ? (int)$location_id : null,
                    'status_id' => $status_id !== '' ? (int)$status_id : null
                ];

                $HistoriqueInventaire = $this->insert_HistoriqueInventaire([$inventory_data[count($inventory_data) - 1]], $ok);
                $ok += (int)$HistoriqueInventaire;


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
                if (count($machineNotfound) == 0) {
                    $_SESSION['flash_success'] = "Machine ajoutée avec succès: $ok";
                } else {
                    $_SESSION['flash_error'] .= "Machine ajoutée avec succès: $ok ,Machines introuvables: " . implode(', ', $machineNotfound);
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


    private function insert_HistoriqueInventaire(array $inventoryRows, $ok)
    {
        if (empty($inventoryRows)) {
            return 0;
        }
        $db = Database::getInstance('db_digitex');
        $conn = $db->getConnection();
        $sql = "INSERT INTO gmao__historique_inventaire (maintainer_id, machine_id, location_id, status_id, created_at) VALUES (:maintainer_id, :machine_id, :location_id, :status_id, CURRENT_TIMESTAMP)";
        $stmt = $conn->prepare($sql);
        $ok = 0;
        foreach ($inventoryRows as $row) {
            $stmt->execute([
                ':maintainer_id' => $row['maintainer_id'] ?? null,
                ':machine_id' => $row['machine_id'] ?? null,
                ':location_id' => $row['location_id'] ?? null,
                ':status_id' => $row['status_id'] ?? null,
            ]);
            $ok += (int)$stmt->rowCount();

            // Audit trail pour chaque insertion dans historique inventaire
            $this->logAudithistoriqueInventaire($row);
        }
        return $ok;
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
    private function logAudithistoriqueInventaire(array $row)
    {
        try {
            $userMatricule = $_SESSION['user']['matricule'] ?? null;
            if (!$userMatricule) return;
            $newValue = [
                'maintainer_id' => $row['maintainer_id'] ?? null,
                'machine_id' => $row['machine_id'] ?? null,
                'location_id' => $row['location_id'] ?? null,
                'status_id' => $row['status_id'] ?? null,
                'created_at' => date('Y-m-d H:i:s')
            ];
            AuditTrail_model::logAudit($userMatricule, 'add', 'gmao__historique_inventaire', null, $newValue);
        } catch (\Throwable $e) {
            error_log("Erreur audit historique inventaire: " . $e->getMessage());
        }
    }
}
