<?php
namespace App\Controllers;
use App\Models\AuditTrail_model;
use App\Models\Intervention_type_model;

class Intervention_typeController {
    public function list() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $intervention_types = Intervention_type_model::findAll();
        include(__DIR__ . '/../views/intervention_type/list__intervention_type.php');
    }

    public function create() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $intervention_type = new Intervention_type_model(
                null,
                $_POST['designation'],
                $_POST['type'],
                $_POST['code']
            );
            if (Intervention_type_model::StoreInterventionType($intervention_type)) {
                $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Intervention type ajouté avec succès !'];
                
                // Audit trail
                if (isset($_SESSION['user']['matricule'])) {
                    $newValues = [
                        'designation' => $_POST['designation'],
                        'type' => $_POST['type'],
                        'code' => $_POST['code']
                    ];
                    AuditTrail_model::logAudit($_SESSION['user']['matricule'], 'add', 'gmao_type_intervention', null, $newValues);
                }
            } else {
                $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Erreur lors de l\'ajout du intervention type.'];
            }
            header('Location: /public/index.php?route=intervention_type/list');
            exit;
        }

        include(__DIR__ . '/../views/intervention_type/add_intervention_type.php');
    }

    public function edit() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $id = $_GET['id'] ?? null;
        if (!$id) {
            $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'ID du intervention type non spécifié.'];
            header('Location: ../../public/index.php?route=intervention_type/list');
            exit;
        }
        
        // Récupérer les anciennes valeurs pour l'audit
        $oldIntervention_type = Intervention_type_model::findById($id);
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $intervention_type = new Intervention_type_model(
                $id,
                $_POST['designation'],
                $_POST['type'],
                $_POST['code']
            );
            if (Intervention_type_model::UpdateInterventionType($intervention_type)) {
                $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Intervention type modifié avec succès !'];
                
                // Audit trail
                if (isset($_SESSION['user']['matricule']) && $oldIntervention_type) {
                    $newValues = [
                        'id' => $id,
                        'designation' => $_POST['designation'],

                        'type' => $_POST['type'],
                        'code' => $_POST['code']
                    ];
                    AuditTrail_model::logAudit($_SESSION['user']['matricule'], 'update', 'gmao_type_intervention', $oldIntervention_type, $newValues);
                }
            } else {
                $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Erreur lors de la modification du intervention type.'];
            }
            header('Location: ../../public/index.php?route=intervention_type/list');
            exit;
        }
        $intervention_type = Intervention_type_model::findById($id);
       
        include(__DIR__ . '/../views/intervention_type/edit_intervention_type.php');
    }

    public function delete() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $id = $_GET['id'] ?? null;
        if ($id) {
            // Récupérer les anciennes valeurs pour l'audit
            $oldIntervention_type = Intervention_type_model::findById($id);
            
            if (Intervention_type_model::deleteById($id)) {
                $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Intervention type supprimé avec succès !'];
                
                // Audit trail
                if (isset($_SESSION['user']['matricule']) && $oldIntervention_type) {
                    AuditTrail_model::logAudit($_SESSION['user']['matricule'], 'delete', 'gmao_type_intervention', $oldIntervention_type, null);
                }
            } else {
                $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Erreur lors de la suppression du intervention type.'];
            }
        } else {
            $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'ID du intervention type non spécifié.'];
        }
            header('Location: /public/index.php?route=intervention_type/list');
        exit;
    }
    
    /**
     * Affiche l'historique des audits pour les mainteneurs
     */
    public function auditTrails() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        // Vérifier les permissions (seuls les administrateurs peuvent voir)
        $isAdmin = isset($_SESSION['qualification']) && $_SESSION['qualification'] === 'ADMINISTRATEUR';
        if (!$isAdmin) {
            $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Accès non autorisé.'];
            header('Location: /public/index.php?route=intervention_type/list');
            exit;
        }
        
        // Récupérer les filtres
        $action = $_GET['action'] ?? null;
        $table = 'gmao_type_intervention'; // On filtre spécifiquement pour la table des employés/mainteneurs
        
        // Récupérer l'historique des audits
        $auditTrails = AuditTrail_model::getFilteredAuditTrails($action, $table, 100);
        
        include(__DIR__ . '/../views/intervention_type/audit_trails.php');
    }
    
} 