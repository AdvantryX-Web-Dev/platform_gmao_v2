<?php
namespace App\Controllers;
use App\Models\AuditTrail_model;
use App\Models\Machines_status_model;

class Machines_statusController {
    public function list() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $machines_status = Machines_status_model::findAll();
        include(__DIR__ . '/../views/machines_status/list_machines_status.php');
    }

    public function create() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $machines_status = new Machines_status_model(
                null,
                $_POST['status_name']
            );
            if (Machines_status_model::StoreMachinesStatus($machines_status)) {
                $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Statut de machine ajouté avec succès !'];
                
                // Audit trail
                if (isset($_SESSION['user']['matricule'])) {
                    $newValues = [
                        'status_name' => $_POST['status_name']
                    ];
                    AuditTrail_model::logAudit($_SESSION['user']['matricule'], 'add', 'gmao_status_machine', null, $newValues);
                }
            } else {
                $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Erreur lors de l\'ajout du statut de machine.'];
            }
            header('Location: /public/index.php?route=machines_status/list');
            exit;
        }

        include(__DIR__ . '/../views/machines_status/add_machines_status.php');
    }

    public function edit() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $id = $_GET['id'] ?? null;
        if (!$id) {
                    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'ID du statut de machine non spécifié.'];
            header('Location: /public/index.php?route=machines_status/list');
            exit;
        }
        
        // Récupérer les anciennes valeurs pour l'audit
        $oldMachines_status = Machines_status_model::findById($id);
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $machines_status = new Machines_status_model(
                $id,
                $_POST['status_name']
            );
            if (Machines_status_model::UpdateMachinesStatus($machines_status)) {
                $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Statut de machine modifié avec succès !'];
                
                // Audit trail
                if (isset($_SESSION['user']['matricule']) && $oldMachines_status) {
                    $newValues = [
                        'id' => $id,
                        'status_name' => $_POST['status_name']
                    ];
                    AuditTrail_model::logAudit($_SESSION['user']['matricule'], 'update', 'gmao_status_machine', $oldMachines_status, $newValues);
                }
            } else {
                $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Erreur lors de la modification du statut de machine.'];
            }
            header('Location: /public/index.php?route=machines_status/list');
            exit;
        }
        $machines_status = Machines_status_model::findById($id);
       
        include(__DIR__ . '/../views/machines_status/edit_machines_status.php');
    }

    public function delete() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $id = $_GET['id'] ?? null;
        if ($id) {
            // Récupérer les anciennes valeurs pour l'audit
            $oldMachines_status = Machines_status_model::findById($id);
            
            if (Machines_status_model::deleteById($id)) {
                $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Statut de machine supprimé avec succès !'];
                
                // Audit trail
                if (isset($_SESSION['user']['matricule']) && $oldMachines_status) {
                    AuditTrail_model::logAudit($_SESSION['user']['matricule'], 'delete', 'gmao_status_machine', $oldMachines_status, null);
                }
            } else {
                $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Erreur lors de la suppression du statut de machine.'];
            }
        } else {
            $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'ID du statut de machine non spécifié.'];
        }
        header('Location: /public/index.php?route=machines_status/list');
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
            header('Location: /public/index.php?route=machines_status/list');
            exit;
        }
        
        // Récupérer les filtres
        $action = $_GET['action'] ?? null;
        $table = 'gmao_status_machine'; // On filtre spécifiquement pour la table des employés/mainteneurs
        
        // Récupérer l'historique des audits
        $auditTrails = AuditTrail_model::getFilteredAuditTrails($action, $table, 100);
        
        include(__DIR__ . '/../views/machines_status/audit_trails.php');
    }
    
} 