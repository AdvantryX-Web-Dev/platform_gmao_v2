<?php

namespace App\Models;

use App\Models\Database;
use PDOException;

class Location_model
{
    /**
     * Récupère tous les emplacements des équipements depuis gmao__location
     * @return array
     */
    public static function getAllLocations()
    {
        try {
            $db = new Database();
            $conn = $db->getConnection();
            $stmt = $conn->query("SELECT id, location_name, location_category, location_type FROM gmao__location ORDER BY location_name");
            return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            return [];
        }
    }

   
}


