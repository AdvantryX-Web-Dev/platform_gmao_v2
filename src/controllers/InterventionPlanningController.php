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
                INSERT INTO gmao__planning 
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
        header('Location: ../../platform_gmao/public/index.php?route=intervention_preventive');
        exit;
    }

    /**
     * Display a list of planned interventions
     */
    public function list()
    {
        $plannings = $this->getPlannedInterventions();
        include(__DIR__ . '/../views/intervention/intervention_planing_list.php');
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
                    p.updated_at,
                    ia.created_at as intervention_date
                FROM 
                    gmao__planning p
                LEFT JOIN 
                    db_mahdco.init__machine m ON p.machine_id = m.id
                LEFT JOIN 
                    gmao__type_intervention ti ON p.intervention_type_id = ti.id
                      LEFT JOIN
                    gmao__intervention_action ia ON p.id = ia.planning_id
               
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
                SELECT COUNT(*) AS count
                FROM gmao__planning p
                LEFT JOIN gmao__intervention_action ia ON p.id = ia.planning_id
                WHERE ia.planning_id IS NULL
                AND (
                    p.planned_date = CURDATE()
                    OR p.planned_date = CURDATE() + INTERVAL 1 DAY
                );
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
                UPDATE gmao__planning 
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
        header('Location: ../../platform_gmao/public/index.php?route=intervention_planning/list');
        exit;
    }

    /**
     * Get active planning records for interventions
     *
     * @return array
     */
    public function getActivePlannings()
    {
        // Find all future plannings that haven't been completed yet
        // and are not already used in interventions
        $db = new Database();
        $conn = $db->getConnection();
        $currentDate = date('Y-m-d');

        try {
            $stmt = $conn->prepare("SELECT p.*, m.machine_id FROM gmao__planning p 
                    JOIN db_mahdco.init__machine m ON p.machine_id = m.id 
                    WHERE p.planned_date >= ? 
                    AND p.id NOT IN (
                        SELECT planning_id FROM gmao__intervention_action 
                        WHERE planning_id =p.id
                    )
                    ORDER BY p.planned_date ASC");

            $stmt->execute([$currentDate]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting active plannings: " . $e->getMessage());
            return [];
        }
    }
}
