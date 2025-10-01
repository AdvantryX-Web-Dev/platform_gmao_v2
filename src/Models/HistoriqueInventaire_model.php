<?php

namespace App\Models;

use App\Models\Database;

class HistoriqueInventaire_model
{

    /**
     * Récupère l'historique d'inventaire le plus récent pour chaque machine
     */
    public static function getLatestHistorique()
    {
        $db = Database::getInstance('db_digitex');
        $conn = $db->getConnection();

        $sql = "
            SELECT 
                hi.machine_id,
                hi.maintainer_id,
                hi.location_id,
                hi.status_id,
                hi.created_at,
                e.matricule AS maintainer_matricule,
                CONCAT(e.first_name, ' ', e.last_name) AS maintainer_name,
                l.location_name,
                l.location_category,
                s.status_name,
                m.machine_id AS machine_code,
                m.reference AS machine_reference,
                -- Maintenancier actuel depuis gmao__machine_maint
                current_maint.maintener_id AS current_maintainer_id,
                current_emp.matricule AS current_maintainer_matricule,
                CONCAT(current_emp.first_name, ' ', current_emp.last_name) AS current_maintainer_name
            FROM gmao__historique_inventaire hi
            INNER JOIN (
                SELECT machine_id, MAX(id) AS max_id
                FROM gmao__historique_inventaire
                GROUP BY machine_id
            ) latest ON hi.machine_id = latest.machine_id AND hi.id = latest.max_id
            LEFT JOIN init__employee e ON e.id = hi.maintainer_id
            LEFT JOIN gmao__location l ON l.id = hi.location_id
            LEFT JOIN gmao__status s ON s.id = hi.status_id
            LEFT JOIN init__machine m ON m.id = hi.machine_id
            -- Jointure pour le maintenancier actuel (dernier enregistrement dans gmao__machine_maint)
            LEFT JOIN (
                SELECT mm.*
                FROM gmao__machine_maint mm
                INNER JOIN (
                    SELECT machine_id, MAX(id) AS max_id
                    FROM gmao__machine_maint
                    GROUP BY machine_id
                ) mm_latest ON mm.machine_id = mm_latest.machine_id AND mm.id = mm_latest.max_id
            ) current_maint ON current_maint.machine_id = m.id
            LEFT JOIN init__employee current_emp ON current_emp.id = current_maint.maintener_id
            ORDER BY hi.id DESC
        ";

        $stmt = $conn->query($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function getAllMachines($isAdmin, $filterMaintainer)
    {
        $db = Database::getInstance('db_digitex');
        $conn = $db->getConnection();
        $params = [];
        
        $sql = "SELECT DISTINCT m.*, l.location_name, l.location_category, s.status_name,
                CONCAT(current_emp.first_name, ' ', current_emp.last_name) AS current_maintainer_name,
                current_emp.matricule AS current_maintainer_matricule
        FROM init__machine m
        LEFT JOIN gmao__location l ON l.id = m.machines_location_id
        LEFT JOIN gmao__status s ON s.id = m.machines_status_id
        LEFT JOIN (
            SELECT mm.*
            FROM gmao__machine_maint mm
            INNER JOIN (
                SELECT machine_id, MAX(id) AS max_id
                FROM gmao__machine_maint
                GROUP BY machine_id
            ) mm_latest ON mm.machine_id = mm_latest.machine_id AND mm.id = mm_latest.max_id
        ) current_maint ON current_maint.machine_id = m.id
        LEFT JOIN init__employee current_emp ON current_emp.id = current_maint.maintener_id";

        if ($filterMaintainer) {
            $sql .= " WHERE current_emp.matricule = :matricule";
            $params['matricule'] = $filterMaintainer;
        }
        
        $sql .= " ORDER BY m.id";
        
        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindParam(':' . $key, $value);
        }
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
