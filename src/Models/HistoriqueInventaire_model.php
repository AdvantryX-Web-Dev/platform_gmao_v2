<?php

namespace App\Models;

use App\Models\Database;
use App\Models\AuditTrail_model;

class HistoriqueInventaire_model
{
    public static function Evaluation_inventaire($filterMaintainer = '', $filterStatus = '', $filterDateFrom = '', $filterDateTo = '')
    {
        try {
            $db = Database::getInstance('db_digitex');
            $conn = $db->getConnection();

            // Vérifier si l'utilisateur est admin
            $isAdmin = isset($_SESSION['qualification']) && $_SESSION['qualification'] === 'ADMINISTRATEUR';

            // Construire la requête de base
            $sql = "
                SELECT 
                    gei.*,
                    CONCAT(inv_emp.last_name, ' ', inv_emp.first_name) AS inventory_maintainer,
                    inv_emp.matricule AS inventory_maintainer_matricule,
                    CONCAT(cur_emp.last_name, ' ', cur_emp.first_name) AS current_maintainer,
                    cur_emp.matricule AS current_maintainer_matricule
                FROM gmao__evaluation_inventaire gei
                INNER JOIN (
                    SELECT machine_id, MAX(id) AS max_id
                    FROM gmao__evaluation_inventaire
                    GROUP BY machine_id
                ) latest ON gei.id = latest.max_id
                LEFT JOIN init__employee inv_emp ON inv_emp.id = gei.inventory_maintainer
                LEFT JOIN init__employee cur_emp ON cur_emp.id = gei.current_maintainer
            ";

            $params = [];
            $whereConditions = [];

            // Filtrage par maintenancier
            if ($isAdmin && $filterMaintainer) {
                // Filtrer par maintenancier fourni (peut être l'inventoriste ou le maintenancier courant)
                $whereConditions[] = "(inv_emp.matricule = :filter_maintainer )";
                $params[':filter_maintainer'] = $filterMaintainer;
            } elseif (!$isAdmin) {
                // Pour les non-admins : afficher seulement les machines associées à l'utilisateur connecté
                $userMatricule = $_SESSION['user']['matricule'] ?? null;

                if (!$userMatricule) {
                    return []; // Retourner un tableau vide si pas de matricule
                }
                // Conditions combinées correctement avec OR
                $whereConditions[] = "(
                    inv_emp.matricule = :user_matricule_inv
                    OR cur_emp.matricule = :user_matricule_cur
                )";

                $params[':user_matricule_inv'] = $userMatricule;
                $params[':user_matricule_cur'] = $userMatricule;
            }

            // Filtrage par statut d'évaluation
            if ($filterStatus) {
                $whereConditions[] = "gei.evaluation = :filter_status";
                $params[':filter_status'] = $filterStatus;
            }

            // Filtrage par date
            if ($filterDateFrom) {
                $whereConditions[] = "gei.inventory_date >= :filter_date_from";
                $params[':filter_date_from'] = $filterDateFrom;
            }

            if ($filterDateTo) {
                $whereConditions[] = "gei.inventory_date <= :filter_date_to";
                $params[':filter_date_to'] = $filterDateTo;
            }

            // Ajouter les conditions WHERE
            if (!empty($whereConditions)) {
                $sql .= " WHERE " . implode(' AND ', $whereConditions);
            }

            $sql .= " ORDER BY gei.inventory_date DESC";

            // Exécuter la requête
            if (!empty($params)) {
                $stmt = $conn->prepare($sql);
                $stmt->execute($params);
            } else {
                $stmt = $conn->query($sql);
            }

            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            // Log l'erreur pour le debugging
            error_log("Erreur dans Evaluation_inventaire: " . $e->getMessage());

            // Retourner un tableau vide en cas d'erreur
            return [];
        }
    }
    public static function insert_Evaluation_inventaire($comparisons)
    {
        try {
            $db = Database::getInstance('db_digitex');
            $conn = $db->getConnection();

            $sql = "INSERT INTO gmao__evaluation_inventaire (
                machine_id,
                reference,
                type,
                current_location,
                current_status,
                inventory_maintainer,
                current_maintainer,
                evaluation,
                difference,
                inventory_date
            ) VALUES (
                :machine_id,
                :reference,
                :type,
                :current_location,
                :current_status,
                :inventory_maintainer,
                :current_maintainer,
                :evaluation,
                :difference,
                :inventory_date
            )";

            $stmt = $conn->prepare($sql);

            $machine_id = $comparisons['machine_id'] ?? 'non defini';
            $reference = $comparisons['reference'] ?? 'non defini';
            $type = $comparisons['type'] ?? 'non defini';
            $current_location = $comparisons['current_location_name'] ?? 'non defini';
            $current_status = $comparisons['current_status'] ?? 'non defini';
            $inventory_maintainer = $comparisons['inventory_maintainer_name'] ?? 'non defini';
            $current_maintainer = $comparisons['current_maintainer_name'] ?? 'non defini';
            $evaluation = $comparisons['status'] ?? 'non defini';
            $difference = $comparisons['differences'] ?? 'non defini';
            $inventory_date = date('Y-m-d');

            $stmt->bindParam(':machine_id', $machine_id);
            $stmt->bindParam(':reference', $reference);
            $stmt->bindParam(':type', $type);
            $stmt->bindParam(':current_location', $current_location);
            $stmt->bindParam(':current_status', $current_status);
            $stmt->bindParam(':inventory_maintainer', $inventory_maintainer);
            $stmt->bindParam(':current_maintainer', $current_maintainer);
            $stmt->bindParam(':evaluation', $evaluation);
            $stmt->bindParam(':difference', $difference);
            $stmt->bindParam(':inventory_date', $inventory_date);

            $stmt->execute();

            // Audit trail pour l'insertion d'évaluation
            self::logAuditEvaluation($machine_id, $reference, $type, $current_location, $current_status, $inventory_maintainer, $current_maintainer, $evaluation, $difference, $inventory_date);

            return true;
        } catch (\Throwable $e) {
            

            error_log('Erreur insert_Evaluation_inventaire: ' . $e->getMessage());
            return false;
        }
    }

    private static function logAuditEvaluation($machine_id, $reference, $type, $current_location, $current_status, $inventory_maintainer, $current_maintainer, $evaluation, $difference, $inventory_date)
    {
        try {
            $userMatricule = $_SESSION['user']['matricule'] ?? null;
            if (!$userMatricule) return;

            $newValue = [
                'machine_id' => $machine_id,
                'reference' => $reference,
                'type' => $type,
                'current_location' => $current_location,
                'current_status' => $current_status,
                'inventory_maintainer' => $inventory_maintainer,
                'current_maintainer' => $current_maintainer,
                'evaluation' => $evaluation,
                'difference' => $difference,
                'inventory_date' => $inventory_date
            ];
            AuditTrail_model::logAudit($userMatricule, 'add', 'gmao__evaluation_inventaire', null, $newValue);
        } catch (\Throwable $e) {
            error_log("Erreur audit evaluation: " . $e->getMessage());
        }
    }
}
