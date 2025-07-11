<?php
namespace App\Controllers;

use App\Models\Database;
use PDOException;

class InterventionPlanningController
{
    /**
     * Save a new intervention planning entry
     */
    public function save()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check if the request is POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error'] = "Méthode non autorisée";
            $this->redirectToPlanning();
            return;
        }
        
        // Validate required fields
        $requiredFields = ['machine_id', 'intervention_type_id', 'planned_date'];
        foreach ($requiredFields as $field) {
            if (empty($_POST[$field])) {
                $_SESSION['error'] = "Le champ '$field' est requis";
                $this->redirectToPlanning();
                return;
            }
        }
        
        // Get form data
        $machine_id = $_POST['machine_id'];
        $intervention_type_id = $_POST['intervention_type_id'];
        $planned_date = $_POST['planned_date'];
        $comments = $_POST['comments'] ?? '';
        $created_by = $_SESSION['user']['matricule'] ?? null;
        
        // Create database connection
        $db = new Database();
        $conn = $db->getConnection();
        
        try {
            // Begin transaction
            $conn->beginTransaction();
            
            // Insert into planning table
            $stmt = $conn->prepare("
                INSERT INTO gmao_planning 
                (machine_id, intervention_type_id, planned_date, comments, created_by, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->bindParam(1, $machine_id);
            $stmt->bindParam(2, $intervention_type_id);
            $stmt->bindParam(3, $planned_date);
            $stmt->bindParam(4, $comments);
            $stmt->bindParam(5, $created_by);
            
            $result = $stmt->execute();
            
            if ($result) {
                // Commit the transaction
                $conn->commit();
                $_SESSION['success'] = "Intervention planifiée ajoutée avec succès";
            } else {
                // Rollback the transaction
                $conn->rollBack();
                $_SESSION['error'] = "Erreur lors de l'ajout de l'intervention planifiée";
            }
            
        } catch (PDOException $e) {
            // Rollback the transaction
            $conn->rollBack();
            $_SESSION['error'] = "Erreur de base de données: " . $e->getMessage();
        }
        
        $this->redirectToPlanning();
    }
    
    /**
     * Redirect to the intervention planning page
     */
    private function redirectToPlanning() 
    {
        header('Location: ../../public/index.php?route=intervention_preventive');
        exit;
    }
    
    /**
     * Display a list of planned interventions
     */
    public function list()
    {
        $plannings = $this->getPlannedInterventions();
        include(__DIR__ . '/../views/intervention_planing_list.php');
    }
    
    /**
     * Get all planned interventions
     * 
     * @return array Array of planned interventions
     */
    public function getPlannedInterventions()
    {
        $db = new Database();
        $conn = $db->getConnection();
        $plannings = [];
        
        try {
            $query = "
                SELECT 
                    m.machine_id as machine_id,
                    p.planned_date, 
                    ti.designation as intervention_type,
                    p.comments, 
                    p.created_at,
                    p.updated_at
                FROM 
                    gmao_planning p
                LEFT JOIN 
                    init__machine m ON p.machine_id = m.id
                LEFT JOIN 
                    gmao_type_intervention ti ON p.intervention_type_id = ti.id
               
                ORDER BY 
                    p.planned_date DESC, p.created_at DESC
            ";
          
            $stmt = $conn->query($query);
            $plannings = $stmt->fetchAll(\PDO::FETCH_ASSOC);
          
        } catch (PDOException $e) {
            error_log("Error getting planned interventions: " . $e->getMessage());
        }
        
        return $plannings;
    }
    
    /**
     * Get count of future planned interventions
     * 
     * @return int Count of future planned interventions
     */
    public function getFutureInterventionsCount()
    {
        $db = new Database();
        $conn = $db->getConnection();
        
        try {
            $query = "
                SELECT 
                    COUNT(*) as count
                FROM 
                    gmao_planning 
                WHERE 
                    planned_date >= CURDATE() 
                    AND completed_date IS NULL
            ";
            
            $stmt = $conn->query($query);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return (int)($result['count'] ?? 0);
        } catch (PDOException $e) {
            error_log("Error counting future interventions: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Mark a planning intervention as completed
     */
    public function mark_complete()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Get intervention ID
        $id = $_GET['id'] ?? null;
        if (!$id) {
            $_SESSION['error'] = "ID de l'intervention non spécifié";
            $this->redirectToList();
            return;
        }
        
        $db = new Database();
        $conn = $db->getConnection();
        
        try {
            // Begin transaction
            $conn->beginTransaction();
            
            // Update planning record
            $stmt = $conn->prepare("
                UPDATE gmao_planning 
                SET completed_date = NOW() 
                WHERE id = ? AND completed_date IS NULL
            ");
            
            $stmt->bindParam(1, $id);
            $result = $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                // Commit the transaction
                $conn->commit();
                $_SESSION['success'] = "Intervention marquée comme complétée avec succès";
            } else {
                // Rollback the transaction
                $conn->rollBack();
                $_SESSION['error'] = "Intervention déjà complétée ou inexistante";
            }
            
        } catch (PDOException $e) {
            // Rollback the transaction
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            $_SESSION['error'] = "Erreur de base de données: " . $e->getMessage();
        }
        
        $this->redirectToList();
    }
    
    /**
     * Redirect to the intervention planning list page
     */
    private function redirectToList() 
    {
        header('Location: ../../public/index.php?route=intervention_planning/list');
        exit;
    }
} 