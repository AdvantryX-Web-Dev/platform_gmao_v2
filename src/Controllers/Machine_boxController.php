<?php

namespace App\Controllers;

use App\Models\Machines_box_model;
use App\Models\Database;
use App\Models\AuditTrail_model;
use PDO;

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
            $maintainer_select = $_POST['maintainer_select'] ?? '';
            $operator = $_POST['operator'] ?? '0';
            $prod_line = $_POST['prod_line'] ?? '';
            $potential = $_POST['potential'] ?? 0.00;
            $cur_date = $_POST['cur_date'] ?? date('Y-m-d');
            $cur_time = $_POST['cur_time'] ?? date('H:i:s');

            // Utiliser maintainer_select si disponible, sinon maintainer
            $selectedMaintainer = !empty($maintainer_select) ? $maintainer_select : $maintainer;

            // Validation des données obligatoires
            if (empty($machine_id) || empty($selectedMaintainer) || empty($prod_line)) {
                $_SESSION['flash_error'] = 'Veuillez remplir tous les champs obligatoires.';
                header('Location: /platform_gmao/public/index.php?route=box_machine/affectation_scan');
                exit;
            }

            // Connexion à la base de données
            $db = new Database();
            $conn = $db->getConnection();
            //vrifie si cette  est deja trouve dans ini__machine
            $checkStmt = $conn->prepare("SELECT id FROM init__machine WHERE machine_id = ?");
            $checkStmt->execute([$machine_id]);
            $machine = $checkStmt->fetch(PDO::FETCH_ASSOC);
            if (empty($machine)) {
                $_SESSION['flash_error'] = 'Cette machine est pas encore enregistrée dans la list de machine.';
                header('Location: /platform_gmao/public/index.php?route=box_machine/affectation_scan');
                exit;
            }
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
                $operator,
                $potential,
                $cur_date,
                $cur_time
            ]);
            //insert dans gmao__machine_maint
            $stmt = $conn->prepare("INSERT INTO gmao__machine_maint (machine_id, maintener_id, location_id) VALUES (:machine_id, :maintener_id, :location_id)");
            $stmt->execute([
                ':machine_id' => $machine_id,
                ':maintener_id' => $selectedMaintainer,
                ':location_id' => $prod_line
            ]);
            if ($success) {
                // audit trail pour gmao__machine_maint
                $this->logAuditMachineMaint($machine_id, $selectedMaintainer, $prod_line);
                // Audit trail pour l'affectation implimentation
                $this->logAuditAffecter($prod_line, $machine_id, $box_id, $selectedMaintainer, $potential, $cur_date, $cur_time);

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
    private function logAuditMachineMaint($machineId, $selectedMaintainer, $locationId)
    {
        try {
            $userMatricule = $_SESSION['user']['matricule'] ?? null;
            if (!$userMatricule) return;
            $newValue = [
                'machine_id' => $machineId,
                'maintener_id' => $selectedMaintainer,
                'location_id' => $locationId
            ];
            AuditTrail_model::logAudit($userMatricule, 'add', 'gmao__machine_maint', null, $newValue);
        } catch (\Throwable $e) {
            error_log("Erreur audit machine maint: " . $e->getMessage());
        }
    }
}
