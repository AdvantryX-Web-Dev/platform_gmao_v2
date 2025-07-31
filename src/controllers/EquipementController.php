<?php

namespace App\Controllers;

use App\Models\AuditTrail_model;
use App\Models\Intervention_type_model;
use App\Models\Equipement_model;
use App\Models\Mouvement_equipment_model;

class EquipementController
{
    public function list()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $equipements = Equipement_model::findAll();
        include(__DIR__ . '/../views/init_data/equipements/list__equipement.php');
    }

    public function create()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $equipement = new Equipement_model(
                null,
                $_POST['equipment_id'],
                $_POST['designation'],
                $_POST['reference'],
                $_POST['equipment_category'],
                $_POST['location_id']
            );

            if (Equipement_model::StoreEquipement($equipement)) {

                $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'équipement ajouté avec succès !'];

                // Audit trail
                if (isset($_SESSION['user']['matricule'])) {
                    $newValues = [
                        'equipment_id' => $_POST['equipment_id'],
                        'designation' => $_POST['designation'],
                        'reference' => $_POST['reference'],
                        'equipment_category' => $_POST['equipment_category'],
                        'location_id' => $_POST['location_id']
                    ];
                    AuditTrail_model::logAudit($_SESSION['user']['matricule'], 'add', 'init__equipement', null, $newValues);
                }
            } else {
                $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Erreur lors de l\'ajout du équipement.'];
            }
            header('Location: /platform_gmao/public/index.php?route=equipement/list');
            exit;
        }

        include(__DIR__ . '/../views/init_data/equipements/add_equipement.php');
    }

    public function edit()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $id = $_GET['id'] ?? null;
        if (!$id) {
            $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'ID du équipement non spécifié.'];
            header('Location: ../../platform_gmao/public/index.php?route=equipement/list');
            exit;
        }

        // Récupérer les anciennes valeurs pour l'audit
        $oldIntervention_type = Equipement_model::findById($id);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $equipement = new Equipement_model(
                $id,
                $_POST['equipment_id'],
                $_POST['designation'],
                $_POST['reference'],
                $_POST['equipment_category'],
                $_POST['location_id']
            );
            if (Equipement_model::UpdateEquipement($equipement)) {
                $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'équipement modifié avec succès !'];

                // Audit trail
                if (isset($_SESSION['user']['matricule']) && $oldIntervention_type) {
                    $newValues = [
                        'id' => $id,
                        'designation' => $_POST['designation'],
                        'reference' => $_POST['reference'],
                        'equipment_category' => $_POST['equipment_category'],
                        'location_id' => $_POST['location_id']
                    ];
                    AuditTrail_model::logAudit($_SESSION['user']['matricule'], 'update', 'gmao__type_intervention', $oldIntervention_type, $newValues);
                }
            } else {
                $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Erreur lors de la modification du équipement.'];
            }
            header('Location: ../../platform_gmao/public/index.php?route=equipement/list');
            exit;
        }
        $equipement = Equipement_model::findById($id);

        include(__DIR__ . '/../views/init_data/equipements/edit_equipement.php');
    }

    public function delete()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $id = $_GET['id'] ?? null;
        if ($id) {
            // Récupérer les anciennes valeurs pour l'audit
            $oldIntervention_type = Equipement_model::findById($id);

            if (Equipement_model::deleteById($id)) {
                $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Intervention type supprimé avec succès !'];

                // Audit trail
                if (isset($_SESSION['user']['matricule']) && $oldIntervention_type) {
                    AuditTrail_model::logAudit($_SESSION['user']['matricule'], 'delete', 'gmao__type_intervention', $oldIntervention_type, null);
                }
            } else {
                $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Erreur lors de la suppression du intervention type.'];
            }
        } else {
            $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'ID du intervention type non spécifié.'];
        }
        header('Location: /platform_gmao/public/index.php?route=equipement/list');
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
            header('Location: /platform_gmao/public/index.php?route=equipement/list');
            exit;
        }

        // Récupérer les filtres
        $action = $_GET['action'] ?? null;
        $table = 'gmao__equipement'; // On filtre spécifiquement pour la table des employés/mainteneurs

        // Récupérer l'historique des audits
        $auditTrails = AuditTrail_model::getFilteredAuditTrails($action, $table, 100);

        include(__DIR__ . '/../views/init_data/equipements/audit_trails.php');
    }

    public function equipements_state()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $equipements = Equipement_model::equipements_state();

        include(__DIR__ . '/../views/G_equipements/G_equipement_status/equipementStatus.php');
    }
}
