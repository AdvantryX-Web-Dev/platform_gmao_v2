<?php

namespace App\Models;

use App\Models\Database;
use PDOException;

class Location_model
{
    /**
     * Récupère tous les emplacements des équipements depuis init__location
     * @return array
     */
    public static function getAllEquipmentLocations()
    {
        try {
            $db = new Database();
            $conn = $db->getConnection();
            $stmt = $conn->query("SELECT id, location_name, location_category FROM init__location ORDER BY location_name");
            return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Récupère tous les emplacements des machines depuis gmao__machine_location
     * @return array
     */
    public static function getAllMachineLocations()
    {
        try {
            $db =  Database::getInstance('db_digitex');
            $conn = $db->getConnection();
            $stmt = $conn->query("SELECT id, location_name, location_category FROM gmao__machine_location ORDER BY location_name");
            return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            return [];
        }
    }
}


