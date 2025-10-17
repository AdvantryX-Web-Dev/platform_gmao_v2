<?php
namespace App\auditTrails;

use App\Models\AuditTrail_model;
use App\Models\Database;

class init__machine
{
    public function logAuditAddMachine($machineData)
    {
        try {
            $userMatricule = $_SESSION['user']['matricule'] ?? null;
            if (!$userMatricule) return;

            $newValue = [
                'machine_id' => $machineData['machine_id'],
                'reference' => $machineData['reference'],
                'brand' => $machineData['brand'],
                'type' => $machineData['type'],
                'designation' => $machineData['designation'],
                'billing_num' => $machineData['billing_num'],
                'bill_date' => $machineData['bill_date'],
                'cur_date' => $machineData['cur_date'],
                'machines_location_id' => $machineData['machines_location_id'],
                'machines_status_id' => $machineData['machines_status_id'],
                'created_at' => date('Y-m-d H:i:s')
            ];
            AuditTrail_model::logAudit($userMatricule, 'add', 'init__machine', null, $newValue);
        } catch (\Throwable $e) {
            error_log("Erreur audit nouvelle machine: " . $e->getMessage());
        }
    }

    public function logAuditUpdateMachine($machineData)
    {
        try {
            $userMatricule = $_SESSION['user']['matricule'] ?? null;
            if (!$userMatricule) return;

            // Récupérer seulement les colonnes modifiées depuis la base de données
            $db = Database::getInstance('db_digitex');
            $conn = $db->getConnection();
            
            // Construire la requête dynamiquement avec seulement les colonnes présentes dans $machineData
            $fieldsToCheck = array_keys($machineData);
            $fieldsToCheck = array_filter($fieldsToCheck, function($field) {
                return $field !== 'id'; // Exclure l'ID de la comparaison
            });
            
            if (empty($fieldsToCheck)) {
                return; // Aucune colonne à vérifier
            }
            
            // Construire la requête SELECT avec seulement les colonnes nécessaires
            $selectFields = implode(', ', $fieldsToCheck);
            $sql = "SELECT id, {$selectFields} FROM init__machine WHERE id = :machine_id";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([':machine_id' => $machineData['id']]);
            $oldData = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$oldData) {
                error_log("Machine non trouvée pour l'audit: ID " . $machineData['id']);
                return;
            }

            // Identifier seulement les colonnes qui ont vraiment changé
            $changedFields = [];
            $oldValues = [];
            $newValues = [];

            foreach ($fieldsToCheck as $field) {
                if (isset($machineData[$field]) && isset($oldData[$field])) {
                    $oldValue = $oldData[$field];
                    $newValue = $machineData[$field];
                    
                    // Comparer les valeurs (gérer les types)
                    if ($oldValue != $newValue) {
                        $changedFields[] = $field;
                        $oldValues[$field] = $oldValue;
                        $newValues[$field] = $newValue;
                    }
                }
            }

            // Enregistrer l'audit seulement s'il y a des changements
            if (!empty($changedFields)) {
                $oldValues['id'] = $oldData['id'];
                $newValues['id'] = $machineData['id'];
                $newValues['updated_at'] = date('Y-m-d H:i:s');
                
                AuditTrail_model::logAudit($userMatricule, 'update', 'init__machine', $oldValues, $newValues);
            }
        } catch (\Throwable $e) {
            error_log("Erreur audit mise à jour machine: " . $e->getMessage());
        }
    }

    public function logAuditDeleteMachine($machineId)
    {
        try {
            $userMatricule = $_SESSION['user']['matricule'] ?? null;
            if (!$userMatricule) return;

            // Récupérer les valeurs avant suppression
            $db = Database::getInstance('db_digitex');
            $conn = $db->getConnection();
            
            $stmt = $conn->prepare("
                SELECT id, machine_id, reference, brand, type, designation, 
                       billing_num, bill_date, cur_date, 
                       machines_location_id, machines_status_id, created_at
                FROM init__machine 
                WHERE id = :machine_id
            ");
            $stmt->execute([':machine_id' => $machineId]);
            $oldData = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$oldData) {
                error_log("Machine non trouvée pour l'audit de suppression: ID " . $machineId);
                return;
            }

            $oldValues = [
                'id' => $oldData['id'],
                'machine_id' => $oldData['machine_id'],
                'reference' => $oldData['reference'],
                'brand' => $oldData['brand'],
                'type' => $oldData['type'],
                'designation' => $oldData['designation'],
                'billing_num' => $oldData['billing_num'],
                'bill_date' => $oldData['bill_date'],
                'cur_date' => $oldData['cur_date'],
                'machines_location_id' => $oldData['machines_location_id'],
                'machines_status_id' => $oldData['machines_status_id'],
                'created_at' => $oldData['created_at']
            ];

            // Pour une suppression, newValues est null
            AuditTrail_model::logAudit($userMatricule, 'delete', 'init__machine', $oldValues, null);
        } catch (\Throwable $e) {
            error_log("Erreur audit suppression machine: " . $e->getMessage());
        }
    }
}
?>