<?php
namespace App\Models;
use App\Models\Database;
use PDOException;

/**
 * Modèle pour gérer les pistes d'audit (audit trails)
 */
class AuditTrail_model
{
    /**
     * Ajoute un enregistrement d'audit dans la table audit_trails
     * 
     * @param string $user_matricule Matricule de l'utilisateur effectuant l'action
     * @param string $action Type d'action (add, update, delete)
     * @param string $affected_table Table affectée
     * @param array|null $old_value Anciennes valeurs (pour update, delete)
     * @param array|null $new_value Nouvelles valeurs (pour add, update)
     * @return bool Succès ou échec
     */
    public static function logAudit($user_matricule, $action, $affected_table, $old_value = null, $new_value = null)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $db = new Database();
        $conn = $db->getConnection();
        try {
            $stmt = $conn->prepare("INSERT INTO audit_trails (user_id, action, affected_table, old_value, new_value) 
                                   VALUES (?, ?, ?, ?, ?)");
            
            // Conversion des valeurs en JSON si elles ne sont pas NULL
            $old_json = ($old_value !== null) ? json_encode($old_value) : null;
            $new_json = ($new_value !== null) ? json_encode($new_value) : null;
            
            $stmt->bindParam(1, $user_matricule);
            $stmt->bindParam(2, $action);
            $stmt->bindParam(3, $affected_table);
            $stmt->bindParam(4, $old_json);
            $stmt->bindParam(5, $new_json);
            
            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            // En cas d'erreur, on continue quand même (l'audit est secondaire)
            error_log("Erreur d'audit: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Récupère tous les enregistrements d'audit
     * 
     * @param int $limit Nombre maximum d'enregistrements à récupérer
     * @return array Les enregistrements d'audit
     */
    public static function getAuditTrails($limit = 100)
    {
        $db = new Database();
        $conn = $db->getConnection();
        $trails = array();
        
        try {
            $stmt = $conn->prepare("SELECT a.*, e.first_name, e.last_name 
                                   FROM audit_trails a 
                                   LEFT JOIN init__employee e ON a.user_id = e.matricule 
                                   ORDER BY a.created_at DESC 
                                   LIMIT ?");
            $stmt->bindParam(1, $limit, \PDO::PARAM_INT);
            $stmt->execute();
            $trails = $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des audits: " . $e->getMessage());
            return false;
        }
        
        return $trails;
    }
    
    /**
     * Récupère les enregistrements d'audit filtrés
     * 
     * @param string $action Type d'action (add, update, delete)
     * @param string $table Table concernée
     * @param int $limit Nombre maximum d'enregistrements à récupérer
     * @return array Les enregistrements d'audit
     */
    public static function getFilteredAuditTrails($action = null, $table = null, $limit = 100)
    {
        $db = new Database();
        $conn = $db->getConnection();
        $trails = array();
        $params = [];
        $sql = "SELECT a.*, e.first_name, e.last_name 
               FROM audit_trails a 
               LEFT JOIN init__employee e ON a.user_id = e.matricule 
               WHERE 1=1";
        
        if ($action) {
            $sql .= " AND a.action = ?";
            $params[] = $action;
        }
        
        if ($table) {
            $sql .= " AND a.affected_table = ?";
            $params[] = $table;
        }
        
        $sql .= " ORDER BY a.created_at DESC LIMIT ?";
        $params[] = $limit;
        
        try {
            $stmt = $conn->prepare($sql);
            for ($i = 0; $i < count($params); $i++) {
                $stmt->bindParam($i + 1, $params[$i], ($i == count($params) - 1) ? \PDO::PARAM_INT : \PDO::PARAM_STR);
            }
            $stmt->execute();
            $trails = $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des audits filtrés: " . $e->getMessage());
            return false;
        }
        
        return $trails;
    }
} 