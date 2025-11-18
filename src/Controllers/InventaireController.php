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
    public function ajouterEvaluationInventaire(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?route=ajouterInventaire');
            exit;
        }

        $maintainerId = $_POST['evaluation_maintener_id'] ?? null;
        if (empty($maintainerId)) {
            $_SESSION['flash_error'] = "Veuillez sélectionner un maintenancier pour lancer l'évaluation.";
            header('Location: index.php?route=ajouterInventaire');
            exit;
        }

        try {
            $this->insert_Evaluation_inventaire((int)$maintainerId);
            $_SESSION['flash_success'] = "Résultat d'inventaire généré.";
            header('Location: index.php?route=historyInventaire');
            exit;
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = 'Erreur: ' . $e->getMessage();
            header('Location: index.php?route=ajouterInventaire');
            exit;
        }
    }
    private function insert_Evaluation_inventaire($maintainer_id)
    {
        $db = Database::getInstance('db_digitex');
        $conn = $db->getConnection();

        try {
            $sql = "INSERT INTO gmao__evaluation_inventaire 
                (inventory_maintainer, machine_id, reference, type, current_location, current_status, evaluation, difference, created_at) 
                VALUES (:maintainer_id, :machine_id, :reference, :type, :location_id, :status_id, :evaluation, :difference, CURRENT_TIMESTAMP)";
            $stmt = $conn->prepare($sql);

            $current_data = HistoriqueInventaire_model::maintMachine($maintainer_id);
         
            $inventory_data = HistoriqueInventaire_model::inventory_data($maintainer_id);
            $comparaison = $this->comparaison($maintainer_id, $current_data, $inventory_data);
      
            foreach ($comparaison as $row) {
                $maintainerId = $row['inventory_maintainer'] ?? $maintainer_id;
                $machineId = $row['machine_id'] ?? null;

                $reference = $row['reference'] ?? 'non défini';
                $type = $row['type'] ?? 'non défini';
                $locationId = $row['current_location_id'] ?? null;
                $locationName = $row['current_location_name'] ?? 'Non défini';
                $statusId = $row['current_status_id'] ?? null;
                $statusName = $row['current_status_name'] ?? 'Non défini';
                $evaluation = $row['evaluation'] ?? 'conforme';
                $difference = $row['difference'] ?? '';

                $stmt->execute([
                    ':reference' => $reference,
                    ':type' => $type,
                    ':maintainer_id' => $maintainerId,
                    ':machine_id'    => $machineId,
                    ':location_id'   => $locationName,
                    ':status_id'     => $statusName,
                    ':evaluation'    => $evaluation,
                    ':difference'    => $difference
                ]);


                $this->logAuditEvaluationInventaire([
                    'inventory_maintainer' => $maintainerId,
                    'machine_id' => $machineId,
                    'reference' => $reference,
                    'type' => $type,
                    'current_location_id' => $locationId,
                    'current_location_name' => $locationName,
                    'current_status_id' => $statusId,
                    'current_status_name' => $statusName,
                    'evaluation' => $evaluation,
                    'difference' => $difference,
                ]);
            }
        } catch (\Throwable $e) {
        }
    }
    private function comparaison(int $maintainerId, array $currentData, array $inventoryData): array
    {

        $db = Database::getInstance('db_digitex');
        $conn = $db->getConnection();
        $rows = [];
        $currentIndex = $this->buildCurrentMachineIndex($currentData);
        $inventoryIndex = $this->buildInventoryMachineIndex($inventoryData);
        $machineIds = array_unique(array_merge(array_keys($currentIndex), array_keys($inventoryIndex)));
        foreach ($machineIds as $machineId) {
            $currentMachine = $currentIndex[$machineId] ?? null;
            $inventoryRow = $inventoryIndex[$machineId] ?? null;
            if (!$currentMachine && !$inventoryRow) {
                continue;
            }

            $differences = [];
            $evaluation = 'conforme';
            if ($currentMachine && !$inventoryRow) {
                $evaluation = 'non_conforme';
                $differences[] = '*Machine non inventoriée';
            } elseif (!$currentMachine && $inventoryRow) {
                $evaluation = 'non_conforme';
                $differences[] = '*Machine ajoutée lors du dernier inventaire';
            } else {
                $differences = $this->diffMachineAttributes($currentMachine, $inventoryRow);
                if (!empty($differences)) {
                    $evaluation = 'non_conforme';
                }
            }
            $current_status_name = $currentMachine['production_status'] ?? $currentMachine['status_name'];
            $current_status_id = "select id from gmao__status where status_name = '$current_status_name'";
            $stmt = $conn->prepare($current_status_id);
            $stmt->execute();
            $current_status_id = $stmt->fetchColumn();
            $rows[] = [
                'inventory_maintainer' => $maintainerId,
                'machine_id' => $machineId, //machine_id pas id_machine
                'reference' => $currentMachine['reference'] ?? null,
                'type' => $currentMachine['type'] ??  null,
                'current_location_id' => $currentMachine['machines_location_id'] ?? null,
                'current_location_name' => $currentMachine['location_name'] ?? 'Non défini',
                'current_status_id' => $current_status_id,
                'current_status_name' => $current_status_name,
                'evaluation' => $evaluation,
                'difference' => implode('<br>', $differences),
            ];
        }
    
        return $rows;
    }

    private function buildCurrentMachineIndex(array $currentData): array
    {
        $index = [];
        foreach ($currentData as $assignment) {
            if (empty($assignment['machines'])) {
                continue;
            }
            foreach ($assignment['machines'] as $machine) {
                $id = $machine['id_machine'] ?? null; //mahine_id pas id machine
                if ($id) {
                    $index[(int)$id] = array_merge(
                        $machine,
                        [
                            'maintener_id' => $assignment['maintener_id'] ?? null,
                            'maintainer_name' => $assignment['maintainer_name'] ?? null,
                        ]
                    );
                }
            }
        }

        return $index;
    }

    private function buildInventoryMachineIndex(array $inventoryData): array
    {
        $index = [];
        foreach ($inventoryData as $row) {
            $id = $row['id_machine'] ?? null;
            if ($id) {
                $index[(int)$id] = $row;
            }
        }
        return $index;
    }

    private function diffMachineAttributes(array $currentMachine, array $inventoryRow): array
    {
        $issues = [];

        $currentLocationName = $currentMachine['location_name'] ?? ('ID ' . ($currentMachine['machines_location_id'] ?? 'N/A'));
        $inventoryLocationName = $inventoryRow['location_name'] ?? 'Non défini';
        if ($this->stringChanged($currentLocationName, $inventoryLocationName)) {
            $issues[] = '*Localisation modifiée : ' . $currentLocationName . ' → ' . $inventoryLocationName;
        }

        $currentStatusName = $this->normalizeStatusDisplay(
            $currentMachine['production_status'] ?? null,
            $currentMachine['status_name'] ?? null,
            $currentMachine['machines_status_id'] ?? null
        );
        $inventoryStatusName = $this->normalizeStatusDisplay(
            $inventoryRow['status_name'] ?? null,
            null,
            $inventoryRow['status_id'] ?? null
        );
        if ($this->stringChanged($currentStatusName, $inventoryStatusName)) {
            $issues[] = '*Statut modifié : ' . $currentStatusName . ' → ' . $inventoryStatusName;
        }

        $currentMaintId = $currentMachine['maintener_id'] ?? null;
        $inventoryMaintId = $inventoryRow['maintener_id'] ?? null;
        $currentMaintName = $currentMachine['maintainer_name'] ?? ($currentMaintId !== null ? 'ID ' . $currentMaintId : 'Non défini');
        $inventoryMaintName = $inventoryRow['maintainer_name'] ?? ($inventoryMaintId !== null ? 'ID ' . $inventoryMaintId : 'Non défini');
        if ($this->valueChanged($currentMaintId, $inventoryMaintId)) {
            $issues[] = '*Maintenancier modifié : ' . $currentMaintName . ' → ' . $inventoryMaintName;
        }

        return $issues;
    }

    private function normalizeStatusDisplay($primary = null, $secondary = null, $id = null): string
    {
        foreach ([$primary, $secondary] as $candidate) {
            $resolved = $this->mapStatusToken($candidate);
            if ($resolved !== null) {
                return $resolved;
            }
        }

        $fallback = $this->mapStatusToken($id);
        return $fallback ?? 'Non défini';
    }

    private function mapStatusToken($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $raw = trim((string)$value);
        if ($raw === '') {
            return null;
        }

        $normalized = strtolower($raw);
        if ($normalized === '1') {
            return 'active';
        }
        if ($normalized === '0') {
            return 'inactive';
        }
        if ($normalized === 'active' || $normalized === 'inactive') {
            return $normalized;
        }

        if (is_numeric($raw)) {
            return 'ID ' . $raw;
        }

        return $raw;
    }

    private function stringChanged(?string $current, ?string $target): bool
    {
        $normalize = static function (?string $value): string {
            return strtolower(trim((string)$value));
        };

        return $normalize($current) !== $normalize($target);
    }

    private function valueChanged($current, $target): bool
    {
        if ($current === null && $target === null) {
            return false;
        }

        return (string)$current !== (string)$target;
    }


    private function logAuditEvaluationInventaire(array $newValue): void
    {
        try {
            $userMatricule = $_SESSION['user']['matricule'] ?? null;
            if (!$userMatricule) {
                return;
            }
            AuditTrail_model::logAudit($userMatricule, 'add', 'gmao__evaluation_inventaire', null, $newValue);
        } catch (\Throwable $e) {
            error_log('Erreur audit evaluation inventaire: ' . $e->getMessage());
        }
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
