<?php

namespace App\Models;

use App\Models\Database;

class HistoriqueInventaire_model
{
    public static function Evaluation_inventaire($filterMaintainer = '', $filterStatus = '')
    {
        try {
            $db = Database::getInstance('db_digitex');
            $conn = $db->getConnection();

            $isAdmin = isset($_SESSION['qualification']) && $_SESSION['qualification'] === 'ADMINISTRATEUR';
            $userId = $_SESSION['user']['id'] ?? null;

            // Données d’inventaire (historique)
            $inventoryRows = self::inventory_data();

            // Données machines actuelles (production)
            $maintMachines = self::maintMachine();

            $evaluations = [];

            // Indexation rapide de l’inventaire par machine_id
            $inventoryByMachine = [];
            foreach ($inventoryRows as $inv) {
                $key = $inv['id_machine'] . '_' . $inv['maintener_id'];
                $inventoryByMachine[$key] = $inv;
            }

            // Comparaison par mainteneur + machine
            foreach ($maintMachines as $maintGroup) {
                $maintenerId = $maintGroup['maintener_id'];
                $maintenerName = $maintGroup['maintainer_name'];

                foreach ($maintGroup['machines'] as $machine) {
                    $key = $machine['id_machine'] . '_' . $maintenerId;



                    $differences = [];
                    $status = 'conforme';


                    if (!isset($inventoryByMachine[$key])) {
                        // print_r('<pre>');
                        // print_r($key);
                        // print_r('<pre>');die;
                        // Machine non inventoriée
                        $status = 'non_conforme';
                        $addedToThisMaint = false;
                        $MaintainerName = null;
                        $previousInventoryMaintenerId = null;
                        $previousInventoryDate = null;
                        foreach ($inventoryByMachine as $invRow) {
                            if ((int)$invRow['id_machine'] === (int)$machine['id_machine'] && (string)$invRow['maintener_id'] !== (string)$maintenerId) {
                                $addedToThisMaint = true;
                                $MaintainerName = $invRow['maintainer_name'] ?? null;
                                $previousInventoryMaintenerId = $invRow['maintener_id'] ?? null;
                                $previousInventoryDate = $invRow['last_inv_date'] ?? null;
                                break;
                            }
                        }
                        if ($addedToThisMaint) {
                            $differences[] = '*Machine ajoutée au maintenancier : ' . ($MaintainerName ?? 'Non défini');
                        } else {
                            $differences[] = 'Machine non inventoriée';
                        }
                    } else {
                        $inv = $inventoryByMachine[$key];

                        // Localisation
                        if ((string)$inv['location_name'] !== (string)$machine['location_name']) {
                            $differences[] = '*Localisation modifiée : ' . ($machine['location_name'] ?? 'Non défini') . ' → ' . ($inv['location_name'] ?? 'Non défini');
                            $status = 'non_conforme';
                        }

                        // Statut
                        if ((string)$inv['status_name'] !== (string)$machine['status_name']) {
                            $differences[] = '*Statut modifié : ' . ($machine['status_name'] ?? 'Non défini') . ' → ' . ($inv['status_name'] ?? 'Non défini');
                            $status = 'non_conforme';
                        }

                        // Mainteneur
                        if ((string)$inv['maintener_id'] !== (string)$maintenerId) {
                            $differences[] = '*Maintenancier modifié : ' . ($maintenerName ?? 'Non défini') . ' → ' . ($inv['maintainer_name'] ?? 'Non défini');
                            $status = 'non_conforme';
                        }
                    }

                    // Construction du résultat
                    $inventoryMaintenerId = $inventoryByMachine[$key]['maintener_id'] ?? ($previousInventoryMaintenerId ?? null);
                    $evaluationRow = [
                        'machine_id' => $machine['machine_id'],
                        'reference' => $machine['reference'],
                        'type' => $machine['type'],
                        'evaluation' => $status,
                        'differences' => implode('<br>', $differences),
                        'current_location_name' => $machine['location_name'] ?? 'non défini',
                        'current_status' => $machine['status_name'] ?? 'non défini',
                        'current_maintainer' => $maintenerName ?? 'non défini',
                        'inventory_location_name' => $inventoryByMachine[$key]['location_name'] ?? 'non défini',
                        'inventory_status_name' => $inventoryByMachine[$key]['status_name'] ?? 'non défini',
                        'inventory_maintainer' => isset($inventoryByMachine[$key]) ? ($inventoryByMachine[$key]['maintainer_name'] ?? 'non défini') : ($MaintainerName ?? 'non défini'),
                        'inventory_date' => isset($inventoryByMachine[$key]) ? ($inventoryByMachine[$key]['last_inv_date'] ?? 'non défini') : ($previousInventoryDate ?? 'non défini'),
                    ];

                    if ($filterStatus === '' || $filterStatus === $status) {
                        $matchesMaintFilter = ($filterMaintainer === '')
                            || ((string)$filterMaintainer === (string)$maintenerId)
                            || ($inventoryMaintenerId !== null && (string)$filterMaintainer === (string)$inventoryMaintenerId);
                        if ($matchesMaintFilter) {
                            $evaluations[] = $evaluationRow;
                        }
                    }
                }
            }

            return $evaluations;
        } catch (\Exception $e) {
            error_log("Erreur dans Evaluation_inventaire: " . $e->getMessage());
            return [];
        }
    }


    public static function inventory_data()
    {
        try {
            $db = Database::getInstance('db_digitex');
            $conn = $db->getConnection();

            $sql = "
                WITH latest_maint AS (
                    SELECT mm.machine_id, mm.maintener_id
                    FROM gmao__machine_maint mm
                    INNER JOIN (
                        SELECT machine_id, MAX(id) AS max_id
                        FROM gmao__machine_maint
                        GROUP BY machine_id
                    ) lm ON lm.machine_id = mm.machine_id AND lm.max_id = mm.id
                ),
                latest_inv_date AS (
                    SELECT lm.maintener_id, MAX(DATE(h.created_at)) AS max_inv_date
                    FROM gmao__historique_inventaire h
                    INNER JOIN latest_maint lm ON lm.machine_id = h.machine_id
                    GROUP BY lm.maintener_id
                ),
                latest_inv_raw AS (
                SELECT 
                    h.machine_id, 
                    h.maintainer_id AS maintener_id,
                    CONCAT(emp.first_name, ' ', emp.last_name) AS maintainer_name, 
                    lid.max_inv_date, 
                    h.location_id, 
                    h.status_id
                    FROM gmao__historique_inventaire h
                    INNER JOIN latest_maint lm ON lm.machine_id = h.machine_id
                  LEFT JOIN init__employee emp ON emp.id = h.maintainer_id
                    INNER JOIN latest_inv_date lid 
                        ON lid.maintener_id = lm.maintener_id 
                       AND DATE(h.created_at) = lid.max_inv_date
                )
                SELECT 
                    lid.maintener_id,
                    lid.maintainer_name AS maintainer_name,
                    lid.max_inv_date AS last_inv_date,
                    m.id AS id_machine,
                    m.machine_id AS display_machine_id,
                    m.reference,
                    m.type,
                    loc.location_name,
                    st.status_name
                FROM latest_inv_raw lid
                INNER JOIN init__machine m ON m.id = lid.machine_id
                LEFT JOIN gmao__location loc ON loc.id = lid.location_id
                LEFT JOIN gmao__status st ON st.id = lid.status_id
                ORDER BY lid.maintener_id ASC, lid.max_inv_date DESC, m.machine_id ASC
            ";

            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $inventory_data = $stmt->fetchAll(\PDO::FETCH_ASSOC);


            // print_r('function inventory_data');
            // print_r('<pre>');
            // print_r($inventory_data);
            // print_r('<pre>');
            // print_r('function inventory_data');
            // die;
            return $inventory_data;
        } catch (\Exception $e) {
            error_log('Erreur inventory_data: ' . $e->getMessage());
            return [];
        }
    }
    public static function maintMachine($maintainerId = null)
    {
        try {
            $db = Database::getInstance('db_digitex');
            $conn = $db->getConnection();

            $sql = "
                SELECT
                    mm.maintener_id,
                    e.matricule AS maintainer_matricule,
                    CONCAT(e.first_name, ' ', e.last_name) AS maintainer_name,
                    m.id AS id_machine,
                    m.machine_id,
                    m.reference,
                    m.type,
                    m.machines_location_id,
                    m.machines_status_id,
                    l.location_name,
                    s.status_name,
                    CASE 
                        WHEN s.status_name IN ('active','inactive') THEN 
                            CASE WHEN pp_today.id IS NOT NULL THEN pp_today.p_state ELSE 'inactive' END
                        ELSE s.status_name
                    END AS production_status
                FROM gmao__machine_maint mm
                INNER JOIN (
                    SELECT machine_id, MAX(id) AS max_id
                    FROM gmao__machine_maint
                    GROUP BY machine_id
                ) latest ON latest.machine_id = mm.machine_id AND latest.max_id = mm.id
                INNER JOIN init__machine m ON m.id = mm.machine_id
                LEFT JOIN gmao__location l ON l.id = m.machines_location_id
                LEFT JOIN gmao__status s ON s.id = m.machines_status_id
                LEFT JOIN init__employee e ON e.id = mm.maintener_id
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
            ";

            $params = [];
            if ($maintainerId !== null && $maintainerId !== '') {
                $sql .= " WHERE mm.maintener_id = :maintener_id";
                $params[':maintener_id'] = (int)$maintainerId;
            }

            $sql .= " ORDER BY mm.maintener_id, m.machine_id";

            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $grouped = [];
            foreach ($rows as $row) {
                $key = (string)$row['maintener_id'];
                if (!isset($grouped[$key])) {
                    $grouped[$key] = [
                        'maintener_id' => (int)$row['maintener_id'],
                        'maintainer_matricule' => $row['maintainer_matricule'] ?? null,
                        'maintainer_name' => $row['maintainer_name'] ?? null,
                        'machines' => []
                    ];
                }
                $grouped[$key]['machines'][] = [
                    'id_machine' => (int)$row['id_machine'],
                    'machine_id' => $row['machine_id'],
                    'reference' => $row['reference'],
                    'type' => $row['type'],
                    'machines_location_id' => $row['machines_location_id'],
                    'machines_status_id' => $row['machines_status_id'],
                    'location_name' => $row['location_name'] ?? null,
                    'status_name' => $row['status_name'] ?? null,
                    'production_status' => $row['production_status'] ?? null,
                ];
            }

            return array_values($grouped);
        } catch (\Exception $e) {
            error_log('Erreur maintMachine: ' . $e->getMessage());
            return [];
        }
    }


    // public static function latestInventoryGroupedByMaintainerAndDate($maintainerId = null)
    // {
    //     try {
    //         $db = Database::getInstance('db_digitex');
    //         $conn = $db->getConnection();

    //         $sql = "
    //             SELECT 
    //                 h.id,
    //                 h.machine_id,
    //                 h.maintainer_id,
    //                 h.location_id,
    //                 h.status_id,
    //                 h.created_at,
    //                 DATE(h.created_at) AS inventory_date,
    //                 CONCAT(emp.first_name, ' ', emp.last_name) AS maintainer_name,
    //                 m.machine_id AS display_machine_id,
    //                 m.reference,
    //                 m.type,
    //                 loc.location_name,
    //                 st.status_name
    //             FROM gmao__historique_inventaire h
    //             INNER JOIN (
    //                 SELECT machine_id, MAX(id) AS max_id
    //                 FROM gmao__historique_inventaire
    //                 GROUP BY machine_id
    //             ) latest ON latest.max_id = h.id
    //             LEFT JOIN init__employee emp ON emp.id = h.maintainer_id
    //             LEFT JOIN init__machine m ON m.id = h.machine_id
    //             LEFT JOIN gmao__location loc ON loc.id = h.location_id
    //             LEFT JOIN gmao__status st ON st.id = h.status_id
    //         ";

    //         $params = [];
    //         if ($maintainerId !== null && $maintainerId !== '') {
    //             $sql .= " WHERE h.maintainer_id = :maintener_id";
    //             $params[':maintener_id'] = (int)$maintainerId;
    //         }

    //         $sql .= " ORDER BY h.maintainer_id, inventory_date DESC, h.id DESC";

    //         $stmt = $conn->prepare($sql);
    //         $stmt->execute($params);
    //         $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    //         $grouped = [];
    //         foreach ($rows as $row) {
    //             $maintKey = (string)$row['maintainer_id'];
    //             $dateKey = $row['inventory_date'];
    //             if (!isset($grouped[$maintKey])) {
    //                 $grouped[$maintKey] = [
    //                     'maintainer_id' => (int)$row['maintainer_id'],
    //                     'maintainer_name' => $row['maintainer_name'] ?? null,
    //                     'by_date' => []
    //                 ];
    //             }
    //             if (!isset($grouped[$maintKey]['by_date'][$dateKey])) {
    //                 $grouped[$maintKey]['by_date'][$dateKey] = [];
    //             }
    //             $grouped[$maintKey]['by_date'][$dateKey][] = [
    //                 'id' => (int)$row['id'],
    //                 'machine_id' => (int)$row['machine_id'],
    //                 'display_machine_id' => $row['display_machine_id'],
    //                 'reference' => $row['reference'],
    //                 'type' => $row['type'],
    //                 'location_id' => $row['location_id'],
    //                 'location_name' => $row['location_name'],
    //                 'status_id' => $row['status_id'],
    //                 'status_name' => $row['status_name'],
    //                 'created_at' => $row['created_at'],
    //             ];
    //         }

    //         return $grouped;
    //     } catch (\Exception $e) {
    //         error_log('Erreur latestInventoryGroupedByMaintainerAndDate: ' . $e->getMessage());
    //         return [];
    //     }
    // }
}
 // public static function Evaluation_inventaire($filterMaintainer = '', $filterStatus = '', $filterDateFrom = '', $filterDateTo = '')
    // {
    //     try {
    //         $db = Database::getInstance('db_digitex');
    //         $conn = $db->getConnection();

    //         // Vérifier si l'utilisateur est admin
    //         $isAdmin = isset($_SESSION['qualification']) && $_SESSION['qualification'] === 'ADMINISTRATEUR';

    //         // Construire la requête de base
    //         $sql = "
    //             SELECT 
    //                 gei.*,
    //                 CONCAT(inv_emp.last_name, ' ', inv_emp.first_name) AS inventory_maintainer,
    //                 inv_emp.matricule AS inventory_maintainer_matricule,
    //                 CONCAT(cur_emp.last_name, ' ', cur_emp.first_name) AS current_maintainer,
    //                 cur_emp.matricule AS current_maintainer_matricule
    //             FROM gmao__evaluation_inventaire gei
    //             INNER JOIN (
    //                 SELECT machine_id, MAX(id) AS max_id
    //                 FROM gmao__evaluation_inventaire
    //                 GROUP BY machine_id
    //             ) latest ON gei.id = latest.max_id
    //             LEFT JOIN init__employee inv_emp ON inv_emp.id = gei.inventory_maintainer
    //             LEFT JOIN init__employee cur_emp ON cur_emp.id = gei.current_maintainer
    //         ";

    //         $params = [];
    //         $whereConditions = [];

    //         // Filtrage par maintenancier
    //         if ($isAdmin && $filterMaintainer) {
    //             // Filtrer par maintenancier fourni (peut être l'inventoriste ou le maintenancier courant)
    //             $whereConditions[] = "(inv_emp.matricule = :filter_maintainer )";
    //             $params[':filter_maintainer'] = $filterMaintainer;
    //         } elseif (!$isAdmin) {
    //             // Pour les non-admins : afficher seulement les machines associées à l'utilisateur connecté
    //             $userMatricule = $_SESSION['user']['matricule'] ?? null;

    //             if (!$userMatricule) {
    //                 return []; // Retourner un tableau vide si pas de matricule
    //             }
    //             // Conditions combinées correctement avec OR
    //             $whereConditions[] = "(
    //                 inv_emp.matricule = :user_matricule_inv
    //                 OR cur_emp.matricule = :user_matricule_cur
    //             )";

    //             $params[':user_matricule_inv'] = $userMatricule;
    //             $params[':user_matricule_cur'] = $userMatricule;
    //         }

    //         // Filtrage par statut d'évaluation
    //         if ($filterStatus) {
    //             $whereConditions[] = "gei.evaluation = :filter_status";
    //             $params[':filter_status'] = $filterStatus;
    //         }



    //         // Ajouter les conditions WHERE
    //         if (!empty($whereConditions)) {
    //             $sql .= " WHERE " . implode(' AND ', $whereConditions);
    //         }

    //         $sql .= " ORDER BY gei.inventory_date DESC";

    //         // Exécuter la requête
    //         if (!empty($params)) {
    //             $stmt = $conn->prepare($sql);
    //             $stmt->execute($params);
    //         } else {
    //             $stmt = $conn->query($sql);
    //         }

    //         return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    //     } catch (\Exception $e) {
    //         // Log l'erreur pour le debugging
    //         error_log("Erreur dans Evaluation_inventaire: " . $e->getMessage());

    //         // Retourner un tableau vide en cas d'erreur
    //         return [];
    //     }
    // }