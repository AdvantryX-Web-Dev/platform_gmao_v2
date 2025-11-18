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

            $sql = "
                    WITH latest_eval AS (
                        SELECT *
                        FROM (
                            SELECT 
                                ei.id AS eval_id,
                                ei.machine_id,
                                ei.inventory_maintainer,
                                ei.created_at,
                                ei.current_location,
                                ei.current_status,
                                ei.evaluation,
                                ei.difference,
                                ROW_NUMBER() OVER (
                                    PARTITION BY ei.inventory_maintainer, ei.machine_id 
                                    ORDER BY ei.id DESC
                                ) AS rn
                            FROM gmao__evaluation_inventaire ei
                        ) ranked_eval
                        WHERE rn = 1
                    ),
                    latest_eval_date AS (
                        SELECT 
                            inventory_maintainer, 
                            MAX(DATE(created_at)) AS max_eval_date
                        FROM latest_eval
                        GROUP BY inventory_maintainer
                    ),
                    latest_eval_raw AS (
                        SELECT 
                            le.machine_id,
                            le.inventory_maintainer,
                            CONCAT(emp.first_name, ' ', emp.last_name) AS inventory_maintainer_name,
                            led.max_eval_date,
                            le.current_location,
                            le.current_status,
                            le.evaluation,
                            le.difference,
                            le.created_at
                        FROM latest_eval le
                        LEFT JOIN init__employee emp ON emp.id = le.inventory_maintainer
                        INNER JOIN latest_eval_date led 
                            ON led.inventory_maintainer = le.inventory_maintainer
                        AND DATE(le.created_at) = led.max_eval_date
                    ),
                    latest_assignment AS (
                        SELECT 
                            mm.machine_id,
                            mm.maintener_id AS current_maintainer_id,
                            CONCAT(emp_cur.first_name, ' ', emp_cur.last_name) AS current_maintainer_name
                        FROM gmao__machine_maint mm
                        INNER JOIN (
                            SELECT machine_id, MAX(id) AS max_id
                            FROM gmao__machine_maint
                            GROUP BY machine_id
                        ) lm ON lm.machine_id = mm.machine_id AND lm.max_id = mm.id
                        LEFT JOIN init__employee emp_cur ON emp_cur.id = mm.maintener_id
                    )
                    SELECT 
                        ler.inventory_maintainer,
                        ler.inventory_maintainer_name,
                        la.current_maintainer_id,
                        la.current_maintainer_name,
                        ler.max_eval_date AS last_eval_date,
                        m.id AS id_machine,
                        m.machine_id AS display_machine_id,
                        m.reference,
                        m.type,
                        ler.current_location,
                        ler.current_status,
                        ler.evaluation,
                        ler.difference,
                        loc.location_name AS machine_location_name,
                        st.status_name AS machine_status_name
                    FROM latest_eval_raw ler
                    INNER JOIN init__machine m ON m.id = ler.machine_id
                    LEFT JOIN latest_assignment la ON la.machine_id = ler.machine_id
                    LEFT JOIN gmao__location loc ON loc.id = m.machines_location_id
                    LEFT JOIN gmao__status st ON st.id = m.machines_status_id
                    ORDER BY ler.inventory_maintainer ASC, ler.max_eval_date DESC, m.machine_id ASC
                ";

            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $allEvaluations = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $evaluations = [];

            // Application des filtres
            foreach ($allEvaluations as $evaluationRow) {
                $maintenerId = $evaluationRow['inventory_maintainer'];
                $evaluationValue = $evaluationRow['evaluation'];

                $matchesStatus = $filterStatus === '' || $filterStatus === $evaluationValue;
                $matchesMaintainer = $filterMaintainer === '' || (string)$filterMaintainer === (string)$maintenerId;
                if ($matchesStatus && $matchesMaintainer) {
                    // Si l'utilisateur n'est pas admin, filtrer pour ne voir que ses propres données
                    if ($isAdmin || $userId === (int)$maintenerId) {
                        $evaluations[] = [
                            'inventory_maintainer_id' => $evaluationRow['inventory_maintainer'] ?? null,
                            'inventory_maintainer_name' => $evaluationRow['inventory_maintainer_name'] ?? 'non défini',
                            'current_maintainer_id' => $evaluationRow['current_maintainer_id'] ?? null,
                            'current_maintainer_name' => $evaluationRow['current_maintainer_name'] ?? 'non défini',
                            'inventory_maintainer' => $evaluationRow['inventory_maintainer_name'] ?? 'non défini',
                            'current_maintainer' => $evaluationRow['current_maintainer_name'] ?? 'non défini',
                            'machine_id' => $evaluationRow['display_machine_id'] ?? 'non défini',
                            'reference' => $evaluationRow['reference'] ?? 'non défini',
                            'type' => $evaluationRow['type'] ?? 'non défini',
                            'evaluation' => $evaluationRow['evaluation'] ?? 'non défini',
                            'differences' => $evaluationRow['difference'] ?? '',
                            'current_location_name' => $evaluationRow['current_location'] ?? $evaluationRow['machine_location_name'] ?? 'non défini',
                            'current_location' => $evaluationRow['current_location'] ?? $evaluationRow['machine_location_name'] ?? 'non défini',
                            'current_status_name' => $evaluationRow['current_status'] ?? $evaluationRow['machine_status_name'] ?? 'non défini',
                            'current_status' => $evaluationRow['current_status'] ?? $evaluationRow['machine_status_name'] ?? 'non défini',
                            'inventory_date' => $evaluationRow['last_eval_date'] ?? 'non défini',
                        ];
                    }
                }
            }
            return $evaluations;
        } catch (\Exception $e) {
            error_log("Erreur dans Evaluation_inventaire: " . $e->getMessage());
            return [];
        }
    }



    public static function inventory_data($maintener_id)
    {
        try {
            if (empty($maintener_id)) {
                return [];
            }

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
                    WHERE lid.maintener_id = :maintener_id
                    ORDER BY lid.maintener_id ASC, lid.max_inv_date DESC, m.machine_id ASC
                ";

            $stmt = $conn->prepare($sql);
            $stmt->execute([':maintener_id' => $maintener_id]);
            $inventory_data = $stmt->fetchAll(\PDO::FETCH_ASSOC);



            return $inventory_data;
        } catch (\Exception $e) {
            error_log('Erreur inventory_data: ' . $e->getMessage());
            die($e->getMessage());
            return [];
        }
    }

    public static function maintMachine($maintener_id)

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
                                CASE 
                                    WHEN pp_today.id IS NOT NULL THEN 
                                        CASE 
                                            WHEN LOWER(pp_today.p_state) IN ('active','inactive') THEN LOWER(pp_today.p_state)
                                            WHEN pp_today.p_state = 1 THEN 'active'
                                            WHEN pp_today.p_state = 0 THEN 'inactive'
                                            ELSE LOWER(s.status_name)
                                        END
                                    ELSE 'inactive'
                                END
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
            if ($maintener_id !== null && $maintener_id !== '') {
                $sql .= " WHERE mm.maintener_id = :maintener_id";
                $params[':maintener_id'] = (int)$maintener_id;
            }

            $sql .= " ORDER BY mm.machine_id, m.machine_id";

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
}
