<?php

namespace App\Controllers;

use App\Models\RejectionReasons_model;
use App\Models\AuditTrail_model;
use App\Models\Database;

class RejectionReasonsController
{
    public function list()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        // Charger les emplacements via le modèle dédié
        $RejectionReasons = RejectionReasons_model::getAllRejectionReasons();



        include(__DIR__ . '/../views/init_data/rejection_Reasons/list__rejection_reasons.php');
    }

    public function create()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $reasonName = trim($_POST['reason_name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            if (RejectionReasons_model::existsByReasonName($reasonName)) {
                $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'raison de rejet déjà existant.'];
                header('Location: /platform_gmao/public/index.php?route=rejection_reasons/create');
                exit;
            }
            if ($reasonName === '') {
                $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'raison de rejet requis.'];
                header('Location: /platform_gmao/public/index.php?route=rejection_reasons/create');
                exit;
            }
            try {
                $db = new Database();
                $conn = $db->getConnection();
                $db_digitex =  Database::getInstance('db_digitex');
                $conn_digitex = $db_digitex->getConnection();
                $stmt = $conn_digitex->prepare('INSERT INTO gmao__rejectionReasons (reason_name, description) VALUES (?, ?)');

                $stmt->execute([$reasonName, $description]);
                // Audit trail - création
                if (isset($_SESSION['user']['matricule'])) {
                    $newValues = [
                        'reason_name' => $reasonName,
                        'description' => $description,
                        //'location_type' => $location_type
                    ];
                    AuditTrail_model::logAudit($_SESSION['user']['matricule'], 'add', 'gmao__rejectionReasons', null, $newValues);
                }
                $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'raison de rejet ajouté avec succès.'];
            } catch (\PDOException $e) {
                $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Erreur lors de l\'ajout de la raison de rejet.'];
            }
            header('Location: /platform_gmao/public/index.php?route=rejection_reasons/list');
            exit;
        }
        include(__DIR__ . '/../views/init_data/rejection_Reasons/add__rejection_reasons.php');
    }

    public function edit()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $id = $_GET['id'] ?? null;
        $description = trim($_POST['description'] ?? '');
        $reasonName = trim($_POST['reason_name'] ?? '');
        if (!$id) {
            $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'ID de la raison de rejet non spécifié.'];
            header('Location: ../../platform_gmao/public/index.php?route=rejection_reasons/list');
            exit;
        }
        $rejectionReason = null;
        try {
            $db = new \App\Models\Database();
            $conn = $db->getConnection();
            $db_digitex =  Database::getInstance('db_digitex');
            $conn_digitex = $db_digitex->getConnection();
            $stmt = $conn_digitex->prepare('SELECT * FROM gmao__rejectionReasons WHERE id = ?');

            $stmt->execute([$id]);
            $rejectionReason = $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            $rejectionReason = null;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $reasonName = trim($_POST['reason_name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            if (RejectionReasons_model::existsByReasonName($reasonName, $id)) {
                $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'raison de rejet déjà existant.'];
                header('Location: /platform_gmao/public/index.php?route=rejection_reasons/edit&id=' . urlencode($id));
                exit;
            }
            if ($reasonName === '') {
                $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'raison de rejet requis.'];
                header('Location: ../../platform_gmao/public/index.php?route=rejection_reasons/edit&id=' . urlencode($id));
                exit;
            }
            try {
                $db = new \App\Models\Database();
                $conn = $db->getConnection();
                $db_digitex =  Database::getInstance('db_digitex');
                $conn_digitex = $db_digitex->getConnection();
                $stmt = $conn_digitex->prepare('UPDATE gmao__rejectionReasons SET reason_name = ?, description = ? WHERE id = ?');
                $stmt->execute([$reasonName, $description, $id]);
                // Audit trail - modification
                if (isset($_SESSION['user']['matricule']) && $rejectionReason) {
                    $newValues = [
                        'id' => $id,
                        'reason_name' => $reasonName,
                        'description' => $description,
                    ];
                    AuditTrail_model::logAudit($_SESSION['user']['matricule'], 'update', 'gmao__rejectionReasons', $rejectionReason, $newValues);
                }
                $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'raison de rejet modifié avec succès.'];
            } catch (\PDOException $e) {
                $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Erreur lors de la modification.'];
            }
            header('Location: ../../platform_gmao/public/index.php?route=rejection_reasons/list');
            exit;
        }
        include(__DIR__ . '/../views/init_data/rejection_Reasons/edit__rejection_reasons.php');
    }

    public function delete()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $id = $_GET['id'] ?? null;
        $description = trim($_POST['description'] ?? '');
        $reasonName = trim($_POST['reason_name'] ?? '');
        if ($id) {
            try {
                $db = new \App\Models\Database();
                $conn = $db->getConnection();
                $db_digitex =  Database::getInstance('db_digitex');
                $conn_digitex = $db_digitex->getConnection();
                // Récupérer l'enregistrement avant suppression pour l'audit
                $stmtFetch = $conn_digitex->prepare('SELECT * FROM gmao__rejectionReasons WHERE id = ?');
                $affectedTable = 'gmao__rejectionReasons';
                $stmtFetch->execute([$id]);
                $oldRejectionReason = $stmtFetch->fetch(\PDO::FETCH_ASSOC);
                $stmt = $conn_digitex->prepare('DELETE FROM gmao__rejectionReasons WHERE id = ?');
                $stmt->execute([$id]);
                // Audit trail - suppression
                if (isset($_SESSION['user']['matricule']) && $oldRejectionReason) {
                    AuditTrail_model::logAudit($_SESSION['user']['matricule'], 'delete', $affectedTable, $oldRejectionReason, null);
                }
                $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'raison de rejet supprimé avec succès.'];
            } catch (\PDOException $e) {
                $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Erreur lors de la suppression de la raison de rejet.'];
            }
        } else {
            $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'ID de la raison de rejet non spécifié.'];
        }
        header('Location: ../../platform_gmao/public/index.php?route=rejection_reasons/list');
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
            header('Location: ../../platform_gmao/public/index.php?route=rejection_reasons/list');
            exit;
        }

        // Récupérer les filtres
        $action = $_GET['action'] ?? null;
        $table = 'gmao__rejectionReasons'; // On filtre spécifiquement pour la table des employés/mainteneurs

        // Récupérer l'historique des audits
        $auditTrails = AuditTrail_model::getFilteredAuditTrails($action, $table, 100);

        include(__DIR__ . '/../views/init_data/rejection_Reasons/audit_trails.php');
    }
}
