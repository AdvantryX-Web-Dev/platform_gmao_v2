<?php

namespace App\Controllers;

use App\Models\Location_model;
use App\Models\AuditTrail_model;
use App\Models\Database;
class LocationController
{
    public function list()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        // Charger les emplacements via le modèle dédié
        $Locations = Location_model::getAllLocations();
   
       
        
        include(__DIR__ . '/../views/init_data/location/list__location.php');
    }

    public function create()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $locationName = trim($_POST['location_name'] ?? '');
            $location_category = trim($_POST['location_category'] ?? '');
           // $location_type = trim($_POST['location_type'] ?? '');
            if ($locationName === '') {
                $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Nom d\'emplacement requis.'];
                header('Location: /platform_gmao/public/index.php?route=location/create');
                exit;
            }
            try {
                $db = new Database();
                $conn = $db->getConnection();
                $db_digitex =  Database::getInstance('db_digitex');
                $conn_digitex = $db_digitex->getConnection();
                    $stmt = $conn_digitex->prepare('INSERT INTO gmao__location (location_name, location_category) VALUES (?, ?)');
              
                $stmt->execute([$locationName, $location_category]);
                // Audit trail - création
                if (isset($_SESSION['user']['matricule'])) {
                    $newValues = [
                        'location_name' => $locationName,
                        'location_category' => $location_category,
                        //'location_type' => $location_type
                    ];
                    AuditTrail_model::logAudit($_SESSION['user']['matricule'], 'add', 'gmao__location', null, $newValues);
                }
                $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Emplacement ajouté avec succès.'];
            } catch (\PDOException $e) {
                $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Erreur lors de l\'ajout de l\'emplacement.'];
            }
            header('Location: /platform_gmao/public/index.php?route=location/list');
            exit;
        }
        include(__DIR__ . '/../views/init_data/location/add_location.php');
    }

    public function edit()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $id = $_GET['id'] ?? null;
        $location_category = trim($_POST['location_category'] ?? '');
        // $location_type = trim($_POST['location_type'] ?? '');
        if (!$id) {
            $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'ID d\'emplacement non spécifié.'];
            header('Location: ../../platform_gmao/public/index.php?route=location/list');
            exit;
        }
        $location = null;
        try {
            $db = new \App\Models\Database();
            $conn = $db->getConnection();
            $db_digitex =  Database::getInstance('db_digitex');
            $conn_digitex = $db_digitex->getConnection();
            $stmt = $conn_digitex->prepare('SELECT * FROM gmao__location WHERE id = ?');
          
            $stmt->execute([$id]);
            $location = $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            $location = null;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $locationName = trim($_POST['location_name'] ?? '');
            $location_category = trim($_POST['location_category'] ?? '');
            if ($locationName === '') {
                $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Nom d\'emplacement requis.'];
                header('Location: ../../platform_gmao/public/index.php?route=location/edit&id=' . urlencode($id) );
                exit;
            }
            try {
                $db = new \App\Models\Database();
                $conn = $db->getConnection();
                $db_digitex =  Database::getInstance('db_digitex');
                $conn_digitex = $db_digitex->getConnection();
                $stmt = $conn_digitex->prepare('UPDATE gmao__location SET location_name = ?, location_category = ? WHERE id = ?');
                $stmt->execute([$locationName, $location_category, $id]);
                // Audit trail - modification
                if (isset($_SESSION['user']['matricule']) && $location) {
                    $newValues = [
                        'id' => $id,
                        'location_name' => $locationName,
                        'location_category' => $location_category,
                        //'location_type' => $location_type
                    ];
                    AuditTrail_model::logAudit($_SESSION['user']['matricule'], 'update', 'gmao__location', $location, $newValues);
                }
                $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Emplacement modifié avec succès.'];
            } catch (\PDOException $e) {
                $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Erreur lors de la modification.'];
            }
            header('Location: ../../platform_gmao/public/index.php?route=location/list');
            exit;
        }
        include(__DIR__ . '/../views/init_data/location/edit_location.php');
    }

    public function delete()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $id = $_GET['id'] ?? null;
        $location_type = trim($_POST['location_type'] ?? '');
        if ($id) {
            try {
                $db = new \App\Models\Database();
                $conn = $db->getConnection();
                $db_digitex =  Database::getInstance('db_digitex');
                $conn_digitex = $db_digitex->getConnection();
                // Récupérer l'enregistrement avant suppression pour l'audit
                $stmtFetch = $conn_digitex->prepare('SELECT * FROM gmao__location WHERE id = ?');
                $affectedTable = 'gmao__location';
                $stmtFetch->execute([$id]);
                $oldLocation = $stmtFetch->fetch(\PDO::FETCH_ASSOC);
                $stmt = $conn_digitex->prepare('DELETE FROM gmao__location WHERE id = ?');
                $stmt->execute([$id]);
                // Audit trail - suppression
                if (isset($_SESSION['user']['matricule']) && $oldLocation) {
                    AuditTrail_model::logAudit($_SESSION['user']['matricule'], 'delete', $affectedTable, $oldLocation, null);
                }
                $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Emplacement supprimé avec succès.'];
            } catch (\PDOException $e) {
                $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Erreur lors de la suppression de l\'emplacement.'];
            }
        } else {
            $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'ID de l\'emplacement non spécifié.'];
        }
        header('Location: ../../platform_gmao/public/index.php?route=location/list');
        exit;
    }

    /**
     * Affiche l'historique des audits pour les mainteneurs
     */
    public function auditTrails()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        // Vérifier les permissions (seuls les administrateurs peuvent voir)
        $isAdmin = isset($_SESSION['qualification']) && $_SESSION['qualification'] === 'ADMINISTRATEUR';
        if (!$isAdmin) {
            $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Accès non autorisé.'];
            header('Location: ../../platform_gmao/public/index.php?route=categories');
            exit;
        }

        // Récupérer les filtres
        $action = $_GET['action'] ?? null;
        $table = 'gmao__location'; // On filtre spécifiquement pour la table des employés/mainteneurs

        // Récupérer l'historique des audits
        $auditTrails = AuditTrail_model::getFilteredAuditTrails($action, $table, 100);

        include(__DIR__ . '/../views/init_data/motif_mvt/audit_trails.php');
    }
}
