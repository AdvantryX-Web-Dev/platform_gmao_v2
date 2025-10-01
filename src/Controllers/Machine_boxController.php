<?php

namespace App\Controllers;

use App\Models\Machines_box_model;
use App\Models\Database;
use App\Models\AuditTrail_model;

class Machine_boxController
{
    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
    }

    public static function affectationBoxsMachines()
    {
        return Machines_box_model::findAll();
    }

    public function affecter()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['flash_error'] = 'Méthode non autorisée.';
            header('Location: /platform_gmao/public/index.php?route=box_machine/affectation_scan');
            exit;
        }

        try {


            // Récupérer les données du formulaire
            $box_id = $_POST['box_id'] ?? '';
            $machine_id = $_POST['machine_id'] ?? '';
            $maintainer = $_POST['maintainer'] ?? '';
            $prod_line = $_POST['prod_line'] ?? '';
            $potential = $_POST['potential'] ?? 0.00;
            $cur_date = $_POST['cur_date'] ?? date('Y-m-d');
            $cur_time = $_POST['cur_time'] ?? date('H:i:s');

            // Validation des données obligatoires
            if (empty($box_id) || empty($machine_id) || empty($maintainer) || empty($prod_line)) {
                $_SESSION['flash_error'] = 'Veuillez remplir tous les champs obligatoires.';
                header('Location: /platform_gmao/public/index.php?route=box_machine/affectation_scan');
                exit;
            }

            // Connexion à la base de données
            $db = new Database();
            $conn = $db->getConnection();

            // Vérifier si la machine est déjà implantée
            $checkStmt = $conn->prepare("SELECT id FROM prod__implantation WHERE machine_id = ?");
            $checkStmt->execute([$machine_id]);

            if ($checkStmt->fetch()) {
                $_SESSION['flash_error'] = 'Cette machine est déjà implantée.';
                header('Location: /platform_gmao/public/index.php?route=box_machine/affectation_scan');
                exit;
            }

            // Insérer dans la table prod__implantation
            $stmt = $conn->prepare("
                INSERT INTO prod__implantation 
                (prod_line, machine_id, smartbox, operator, potential, cur_date, cur_time) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            $success = $stmt->execute([
                $prod_line,
                $machine_id,
                $box_id,
                $maintainer,
                $potential,
                $cur_date,
                $cur_time
            ]);

            if ($success) {
                // Audit trail pour l'affectation
                $this->logAuditAffecter($prod_line, $machine_id, $box_id, $maintainer, $potential, $cur_date, $cur_time);
                
                $_SESSION['flash_success'] = 'Machine affectée avec succès !';
            } else {
                $_SESSION['flash_error'] = 'Erreur lors de l\'affectation de la machine.';
            }
        } catch (\Exception $e) {

           
            error_log("Erreur dans affecter(): " . $e->getMessage());
            $_SESSION['flash_error'] = 'Une erreur est survenue lors de l\'affectation.';
        }

        // Redirection
        header('Location: /platform_gmao/public/index.php?route=box_machine/affectation_scan');
        exit;
    }

    /**
     * Audit trail pour la fonction affecter
     * Table: prod__implantation (ADD)
     */
    private function logAuditAffecter($prodLine, $machineId, $boxId, $maintainer, $potential, $curDate, $curTime)
    {
        try {
            $userMatricule = $_SESSION['user']['matricule'] ?? null;
            if (!$userMatricule) return;

            // Récupérer l'ID de l'affectation créée
            $db = new Database();
            $conn = $db->getConnection();
            $stmt = $conn->prepare("SELECT id FROM prod__implantation WHERE machine_id = :machine_id AND smartbox = :box_id ORDER BY id DESC LIMIT 1");
            $stmt->execute([
                ':machine_id' => $machineId,
                ':box_id' => $boxId
            ]);
            $affectationId = $stmt->fetchColumn();

            if ($affectationId) {
                $newValue = [
                    'id' => $affectationId,
                    'prod_line' => $prodLine,
                    'machine_id' => $machineId,
                    'smartbox' => $boxId,
                    'operator' => $maintainer,
                    'potential' => $potential,
                    'cur_date' => $curDate,
                    'cur_time' => $curTime
                ];
                AuditTrail_model::logAudit($userMatricule, 'add', 'prod__implantation', null, $newValue);
            }

        } catch (\Throwable $e) {
            error_log("Erreur audit affecter: " . $e->getMessage());
        }
    }
}
