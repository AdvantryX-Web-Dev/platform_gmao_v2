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


    public static function getLocationsByCategory($locationcategory)
    {
        if ($locationcategory === null || $locationcategory === '') {
            return false;
        }
        try {
            $db = new Database();
            $conn = $db->getConnection();
            $stmt = $conn->prepare("SELECT id, location_name, location_category, location_type FROM gmao__location WHERE location_category = ? ORDER BY location_name");
            $stmt->bindParam(1, $locationcategory);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            return [];
        }
    }
    public static function existsByLocationName($locationName, $excludeLocationId = null)
    {
        if ($locationName === null || $locationName === '') {
            return false;
        }
        $db = Database::getInstance('db_digitex');
        $conn = $db->getConnection();
        try {
            if ($excludeLocationId) {
                $stmt = $conn->prepare("SELECT 1 FROM gmao__location WHERE location_name = ? AND id <> ? LIMIT 1");
                $stmt->bindParam(1, $locationName);
                $stmt->bindParam(2, $excludeLocationId);
            } else {
                $stmt = $conn->prepare("SELECT 1 FROM gmao__location WHERE location_name = ? LIMIT 1");
                $stmt->bindParam(1, $locationName);
            }
            $stmt->execute();
            return (bool)$stmt->fetchColumn();
        } catch (PDOException $e) {
            return false;
        }
    }
}
