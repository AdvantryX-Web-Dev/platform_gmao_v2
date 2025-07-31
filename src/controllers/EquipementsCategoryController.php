<?php

namespace App\Controllers;

use App\Models\AuditTrail_model;
use App\Models\EquipementsCategory_model;

class EquipementsCategoryController
{
    public function list()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $categories = EquipementsCategory_model::AllCategorie();
        include(__DIR__ . '/../views/init_data/equipementsCategory/list__equipementsCategory.php');
    }

    public function create()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $categorie = new EquipementsCategory_model(
                null,
                $_POST['category_name'],
                $_POST['category_type']
            );
    
            if (EquipementsCategory_model::StoreCategorie($categorie)) {

                $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'catégorie ajouté avec succès !'];

                // Audit trail
                if (isset($_SESSION['user']['matricule'])) {
                    $newValues = [
                        'category_name' => $_POST['category_name'],
                        'category_type' => $_POST['category_type']
                    ];
                    AuditTrail_model::logAudit($_SESSION['user']['matricule'], 'add', 'init__categoris', null, $newValues);
                }
            } else {
                $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Erreur lors de l\'ajout du catégorie.'];
            }
            header('Location: /platform_gmao/public/index.php?route=equipementsCategory/list');
            exit;
        }

        include(__DIR__ . '/../views/init_data/equipementsCategory/add_equipementsCategory.php');
    }

    public function edit()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $id = $_GET['id'] ?? null;
        if (!$id) {
            $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'ID du catégorie non spécifié.'];
            header('Location: ../../platform_gmao/public/index.php?route=equipementsCategory/list');
            exit;
        }

        // Récupérer les anciennes valeurs pour l'audit
        $oldIntervention_type = EquipementsCategory_model::findById($id);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $categorie = new EquipementsCategory_model(
                $id,
                $_POST['category_name'],
                $_POST['category_type']
            );
            if (EquipementsCategory_model::UpdateCategorie($categorie)) {
                $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'catégorie modifié avec succès !'];

                // Audit trail
                if (isset($_SESSION['user']['matricule']) && $oldIntervention_type) {
                    $newValues = [
                        'id' => $id,
                        'category_name' => $_POST['category_name'],
                        'category_type' => $_POST['category_type']
                    ];
                    AuditTrail_model::logAudit($_SESSION['user']['matricule'], 'update', 'gmao__type_intervention', $oldIntervention_type, $newValues);
                }
            } else {
                $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Erreur lors de la modification du catégorie.'];
            }
            header('Location: ../../platform_gmao/public/index.php?route=equipementsCategory/list');
            exit;
        }
            $categorie = EquipementsCategory_model::findById($id);

        include(__DIR__ . '/../views/init_data/equipementsCategory/edit_equipementsCategory.php');
    }

    public function delete()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $id = $_GET['id'] ?? null;
        if ($id) {
            // Récupérer les anciennes valeurs pour l'audit
            $oldIntervention_type = EquipementsCategory_model::findById($id);

            if (EquipementsCategory_model::deleteById($id)) {
                $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Catégorie supprimé avec succès !'];

                // Audit trail
                if (isset($_SESSION['user']['matricule']) && $oldIntervention_type) {
                    AuditTrail_model::logAudit($_SESSION['user']['matricule'], 'delete', 'init__categoris', $oldIntervention_type, null);
                }
            } else {
                $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Erreur lors de la suppression de la catégorie.'];
            }
        } else {
            $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'ID de la catégorie non spécifié.'];
        }
        header('Location: /platform_gmao/public/index.php?route=equipementsCategory/list');
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
            header('Location: /platform_gmao/public/index.php?route=equipementsCategory/list');
            exit;
        }

        // Récupérer les filtres
        $action = $_GET['action'] ?? null;
        $table = 'init__categoris'; // On filtre spécifiquement pour la table des employés/mainteneurs

        // Récupérer l'historique des audits
        $auditTrails = AuditTrail_model::getFilteredAuditTrails($action, $table, 100);

        include(__DIR__ . '/../views/init_data/equipementsCategory/audit_trails.php');
    }
}
