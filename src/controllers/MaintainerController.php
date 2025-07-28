<?php

namespace App\Controllers;

use App\Models\Maintainer_model;
use App\Models\AuditTrail_model;

class MaintainerController
{
    public function list()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $maintainers = Maintainer_model::findAll();
        include(__DIR__ . '/../views/init_data/maintainers/list__maintainer.php');
    }

    public function create()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $maintainer = new Maintainer_model(
                null,
                $_POST['card_rfid'],
                $_POST['matricule'],
                $_POST['first_name'],
                $_POST['last_name'],
                $_POST['qualification']
            );
            if (Maintainer_model::StoreMaintainer($maintainer)) {
                $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Mainteneur ajouté avec succès !'];

                // Audit trail
                if (isset($_SESSION['user']['matricule'])) {
                    $newValues = [
                        'card_rfid' => $_POST['card_rfid'],
                        'matricule' => $_POST['matricule'],
                        'first_name' => $_POST['first_name'],
                        'last_name' => $_POST['last_name'],
                        'qualification' => $_POST['qualification']
                    ];
                    AuditTrail_model::logAudit($_SESSION['user']['matricule'], 'add', 'init__employee', null, $newValues);
                }
            } else {
                $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Erreur lors de l\'ajout du mainteneur.'];
            }
            header('Location: /platform_gmao/public/index.php?route=maintainers');
            exit;
        }

        include(__DIR__ . '/../views/init_data/maintainers/add_maintainer.php');
    }

    public function edit()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $id = $_GET['id'] ?? null;
        if (!$id) {
            $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'ID du mainteneur non spécifié.'];
            header('Location: ../../platform_gmao/public/index.php?route=maintainers');
            exit;
        }

        // Récupérer les anciennes valeurs pour l'audit
        $oldMaintainer = Maintainer_model::findById($id);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $maintainer = new Maintainer_model(
                $id,
                $_POST['card_rfid'],
                $_POST['matricule'],
                $_POST['first_name'],
                $_POST['last_name'],
                $_POST['qualification']
            );
            if (Maintainer_model::UpdateMaintainer($maintainer)) {
                $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Mainteneur modifié avec succès !'];

                // Audit trail
                if (isset($_SESSION['user']['matricule']) && $oldMaintainer) {
                    $newValues = [
                        'id' => $id,
                        'card_rfid' => $_POST['card_rfid'],
                        'matricule' => $_POST['matricule'],
                        'first_name' => $_POST['first_name'],
                        'last_name' => $_POST['last_name'],
                        'qualification' => $_POST['qualification']
                    ];
                    AuditTrail_model::logAudit($_SESSION['user']['matricule'], 'update', 'init__employee', $oldMaintainer, $newValues);
                }
            } else {
                $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Erreur lors de la modification du mainteneur.'];
            }
            header('Location: ../../platform_gmao/public/index.php?route=maintainers');
            exit;
        }
        $maintainer = Maintainer_model::findById($id);

        include(__DIR__ . '/../views/init_data/maintainers/edit_maintainer.php');
    }

    public function delete()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $id = $_GET['id'] ?? null;
        if ($id) {
            // Récupérer les anciennes valeurs pour l'audit
            $oldMaintainer = Maintainer_model::findById($id);

            if (Maintainer_model::deleteById($id)) {
                $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Mainteneur supprimé avec succès !'];

                // Audit trail
                if (isset($_SESSION['user']['matricule']) && $oldMaintainer) {
                    AuditTrail_model::logAudit($_SESSION['user']['matricule'], 'delete', 'init__employee', $oldMaintainer, null);
                }
            } else {
                $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Erreur lors de la suppression du mainteneur.'];
            }
        } else {
            $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'ID du mainteneur non spécifié.'];
        }
        header('Location: ../../platform_gmao/public/index.php?route=maintainers');
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
            header('Location: ../../platform_gmao/public/index.php?route=maintainers');
            exit;
        }

        // Récupérer les filtres
        $action = $_GET['action'] ?? null;
        $table = 'init__employee'; // On filtre spécifiquement pour la table des employés/mainteneurs

        // Récupérer l'historique des audits
        $auditTrails = AuditTrail_model::getFilteredAuditTrails($action, $table, 100);

        include(__DIR__ . '/../views/init_data/maintainers/audit_trails.php');
    }
    public function auditTrails_history()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        // Vérifier les permissions (seuls les administrateurs peuvent voir)
        $isAdmin = isset($_SESSION['qualification']) && $_SESSION['qualification'] === 'ADMINISTRATEUR';
        if (!$isAdmin) {
            $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Accès non autorisé.'];
            header('Location: ../../platform_gmao/public/index.php?route=maintainers');
            exit;
        }

        // Récupérer les filtres
        $action = $_GET['action'] ?? null;
        $table = 'init__employee'; // On filtre spécifiquement pour la table des employés/mainteneurs

        // Récupérer l'historique des audits
        $auditTrails = AuditTrail_model::getFilteredAuditTrails($action, $table, 100);

        include(__DIR__ . '/../views/init_data/maintainers/audit_trails.php');
    }
}
