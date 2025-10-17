<?php

namespace App\Models;

use App\Models\Database;
use PDOException;

class RejectionReasons_model
{
    /**
     * Récupère tous les raisons de rejet depuis gmao__rejectionReasons
     * @return array
     */
    public static function getAllRejectionReasons()
    {
        try {
            $db = new Database();
            $conn = $db->getConnection();
            $stmt = $conn->query("SELECT id, reason_name, description FROM gmao__rejectionReasons ORDER BY reason_name");
            return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            return [];
        }
    }
    public static function existsByReasonName($reasonName, $excludeReasonId = null)
    {
        if ($reasonName === null || $reasonName === '') {
            return false;
        }
        $db = Database::getInstance('db_digitex');
        $conn = $db->getConnection();
        try {
            if ($excludeReasonId) {
                $stmt = $conn->prepare("SELECT 1 FROM gmao__rejectionReasons WHERE reason_name = ? AND id <> ? LIMIT 1");
                $stmt->bindParam(1, $reasonName);
                $stmt->bindParam(2, $excludeReasonId);
            } else {
                $stmt = $conn->prepare("SELECT 1 FROM gmao__rejectionReasons WHERE reason_name = ? LIMIT 1");
                $stmt->bindParam(1, $reasonName);
            }
            $stmt->execute();
            return (bool)$stmt->fetchColumn();
        } catch (PDOException $e) {
            return false;
        }
    }
}
