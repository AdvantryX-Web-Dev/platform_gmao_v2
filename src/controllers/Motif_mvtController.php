<?php

namespace App\Controllers;

use App\Models\Categories_model;
use App\Models\AuditTrail_model;

class Motif_mvtController
{
    public function list()
    {

        if (session_status() === PHP_SESSION_NONE) session_start();
        $categories = Categories_model::findAll();
        include(__DIR__ . '/../views/init_data/motif_mvt/list__categories.php');
    }

    public function create()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $category = new Categories_model(
                null,
                $_POST['raison_mouv_mach'],
                $_POST['typeR']
            );
            if (Categories_model::StoreCategory($category)) {
                $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Motif ajouté avec succès !'];

                // Audit trail
                if (isset($_SESSION['user']['matricule'])) {
                    $newValues = [
                        'raison_mouv_mach' => $_POST['raison_mouv_mach'],
                        'typeR' => $_POST['typeR']
                    ];
                    AuditTrail_model::logAudit($_SESSION['user']['matricule'], 'add', 'gmao__raison_mouv_mach', null, $newValues);
                }
            } else {
                $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Erreur lors de l\'ajout du motif.'];
            }
            header('Location: /platform_gmao/public/index.php?route=categories');
            exit;
        }

        include(__DIR__ . '/../views/init_data/motif_mvt/add_categories.php');
    }

    public function edit()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $id = $_GET['id'] ?? null;
        if (!$id) {
            $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'ID du motif non spécifié.'];
            header('Location: ../../platform_gmao/public/index.php?route=categories');
            exit;
        }

        // Récupérer les anciennes valeurs pour l'audit
        $oldCategory = Categories_model::findById($id);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $category = new Categories_model(
                $id,
                $_POST['raison_mouv_mach'],
                $_POST['typeR']
            );
            if (Categories_model::UpdateCategory($category)) {
                $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Motif modifié avec succès !'];

                // Audit trail
                // Audit trail
                if (isset($_SESSION['user']['matricule']) && $oldCategory) {
                    $newValues = [
                        'id_Raison' => $id,
                        'raison_mouv_mach' => $_POST['raison_mouv_mach'],
                        'typeR' => $_POST['typeR']
                    ];
                    AuditTrail_model::logAudit($_SESSION['user']['matricule'], 'update', 'gmao__raison_mouv_mach', $oldCategory, $newValues);
                }
            } else {
                $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Erreur lors de la modification du motif.'];
            }
            header('Location: ../../platform_gmao/public/index.php?route=categories');
            exit;
        }
        $category = Categories_model::findById($id);

        include(__DIR__ . '/../views/init_data/motif_mvt/edit_categories.php');
    }

    public function delete()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $id = $_GET['id'] ?? null;
        if ($id) {
            // Récupérer les anciennes valeurs pour l'audit
            $oldCategory = Categories_model::findById($id);

            if (Categories_model::deleteById($id)) {
                $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Motif supprimé avec succès !'];

                // Audit trail
                if (isset($_SESSION['user']['matricule']) && $oldCategory) {
                    AuditTrail_model::logAudit($_SESSION['user']['matricule'], 'delete', 'gmao__raison_mouv_mach', $oldCategory, null);
                }
            } else {
                $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Erreur lors de la suppression de la catégorie.'];
            }
        } else {
            $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'ID de la catégorie non spécifié.'];
        }
        header('Location: ../../platform_gmao/public/index.php?route=categories');
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
        $table = 'init__employee'; // On filtre spécifiquement pour la table des employés/mainteneurs

        // Récupérer l'historique des audits
        $auditTrails = AuditTrail_model::getFilteredAuditTrails($action, $table, 100);

        include(__DIR__ . '/../views/init_data/motif_mvt/audit_trails.php');
    }
}
