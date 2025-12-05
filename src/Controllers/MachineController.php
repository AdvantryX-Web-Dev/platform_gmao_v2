<?php

namespace App\Controllers;

use App\Models\Machine_model;
use App\Models\AuditTrail_model;

class MachineController
{
    public function list()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $machines = Machine_model::findAllMachine();

        // Récupérer les statistiques d'intervention pour chaque machine
        $machine_stats = $this->getMachineInterventionStats();

        include(__DIR__ . '/../views/init_data/machines/init__machine.php');
    }

    public function create()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        // Vérifier les permissions (seuls les administrateurs peuvent ajouter)
        $isAdmin = isset($_SESSION['qualification']) && $_SESSION['qualification'] === 'ADMINISTRATEUR';
        if (!$isAdmin) {
            $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Accès non autorisé. Seuls les administrateurs peuvent ajouter des machines.'];
            header('Location: /platform_gmao/public/index.php?route=machines');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            // Validation et nettoyage des données
            $machine_id = !empty($_POST['machine_id']) ? substr($_POST['machine_id'], 0, 16) : null;
            $designation = !empty($_POST['designation']) ? $_POST['designation'] : null;
            $reference = !empty($_POST['reference']) ? $_POST['reference'] : null;
            $type = !empty($_POST['type']) ? $_POST['type'] : null;
            $category = !empty($_POST['category']) ? $_POST['category'] : null;
            $brand = !empty($_POST['brand']) ? $_POST['brand'] : null;
            $billing_num = !empty($_POST['billing_num']) ? $_POST['billing_num'] : null;
            $bill_date = !empty($_POST['bill_date']) ? $_POST['bill_date'] : null;
            $price = !empty($_POST['price']) ? $_POST['price'] : null;
            $location_id = !empty($_POST['location_id']) ? $_POST['location_id'] : null;
            $status_id = !empty($_POST['status_id']) ? $_POST['status_id'] : null;

            // Unicité: machine_id et reference
            if (Machine_model::existsByMachineId($machine_id) || Machine_model::existsByReference($reference)) {
                $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Machine déjà existante (ID ou Référence).'];
                header('Location: /platform_gmao/public/index.php?route=machine/create');
                exit;
            }

            $machine = new Machine_model(
                $machine_id,
                $designation,
                $reference,
                $type,
                $category,
                $brand,
                $billing_num,
                $bill_date,
                $location_id,
                $status_id
            );

            if (Machine_model::AjouterMachine($machine, $price)) {
                $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Machine ajoutée avec succès !'];

                // Audit trail
                if (isset($_SESSION['user']['matricule'])) {
                    $newValues = [
                        'machine_id' => $machine_id,
                        'designation' => $designation,
                        'reference' => $reference,
                        'type' => $type,
                        'category' => $category,
                        'brand' => $brand,
                        'billing_num' => $billing_num,
                        'bill_date' => $bill_date,
                        'price' => $price,
                        'machines_location_id' => $location_id,
                        'machines_status_id' => $status_id
                    ];
                    AuditTrail_model::logAudit($_SESSION['user']['matricule'], 'add', 'init__machine', null, $newValues);
                }
            } else {
                $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Erreur lors de l\'ajout de la machine.'];
            }
            header('Location: /platform_gmao/public/index.php?route=machines');
            exit;
        }
        include(__DIR__ . '/../views/init_data/machines/add_machine.php');
    }

    public function edit()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        // Vérifier les permissions (seuls les administrateurs peuvent modifier)
        $isAdmin = isset($_SESSION['qualification']) && $_SESSION['qualification'] === 'ADMINISTRATEUR';
        if (!$isAdmin) {
            $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Accès non autorisé. Seuls les administrateurs peuvent modifier des machines.'];
            header('Location: /platform_gmao/public/index.php?route=machines');
            exit;
        }

        $id = $_GET['id'] ?? null;
        if (!$id) {
            $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'ID de la machine non spécifié.'];
            header('Location: ../../platform_gmao/public/index.php?route=machines');
            exit;
        }

        // Récupérer les anciennes valeurs pour l'audit
        $oldMachine = Machine_model::findById($id);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validation et nettoyage des données
            $designation = !empty($_POST['designation']) ? $_POST['designation'] : null;
            $reference = !empty($_POST['reference']) ? $_POST['reference'] : null;
            $type = !empty($_POST['type']) ? $_POST['type'] : null;
            $category = !empty($_POST['category']) ? $_POST['category'] : null;
            $brand = !empty($_POST['brand']) ? $_POST['brand'] : null;
            $billing_num = !empty($_POST['billing_num']) ? $_POST['billing_num'] : null;
            $bill_date = !empty($_POST['bill_date']) ? $_POST['bill_date'] : null;
            $price = !empty($_POST['price']) ? $_POST['price'] : null;
            $location_id = !empty($_POST['location_id']) ? $_POST['location_id'] : null;
            $status_id = !empty($_POST['status_id']) ? $_POST['status_id'] : null;

            // Unicité: la référence ne doit pas appartenir à une autre machine
            if (Machine_model::existsByReference($reference, $id)) {
                $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Référence déjà utilisée par une autre machine.'];
                header('Location: ../../platform_gmao/public/index.php?route=machine/edit&id=' . urlencode($id));
                exit;
            }

            $machine = new Machine_model(
                $id,
                $designation,
                $reference,
                $type,
                $category,
                $brand,
                $billing_num,
                $bill_date,
                $location_id,
                $status_id
            );

            if (Machine_model::ModifierMachine($machine, $price)) {
                $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Machine modifiée avec succès !'];

                // Audit trail
                if (isset($_SESSION['user']['matricule']) && $oldMachine) {
                    $newValues = [
                        'machine_id' => $id,
                        'designation' => $designation,
                        'reference' => $reference,
                        'type' => $type,
                        'category' => $category,
                        'brand' => $brand,
                        'billing_num' => $billing_num,
                        'bill_date' => $bill_date,
                        'price' => $price,
                        'machines_location_id' => $location_id,
                        'machines_status_id' => $status_id
                    ];
                    AuditTrail_model::logAudit($_SESSION['user']['matricule'], 'update', 'init__machine', $oldMachine, $newValues);
                }
            } else {
                $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Erreur lors de la modification de la machine.'];
            }
            header('Location: ../../platform_gmao/public/index.php?route=machines');
            exit;
        }
        $machine = Machine_model::findById($id);
        include(__DIR__ . '/../views/init_data/machines/edit_machine.php');
    }

    public function delete()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        // Vérifier les permissions (seuls les administrateurs peuvent supprimer)
        $isAdmin = isset($_SESSION['qualification']) && $_SESSION['qualification'] === 'ADMINISTRATEUR';
        if (!$isAdmin) {
            $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Accès non autorisé. Seuls les administrateurs peuvent supprimer des machines.'];
            header('Location: /platform_gmao/public/index.php?route=machines');
            exit;
        }

        $id = $_GET['id'] ?? null;
        if ($id) {
            // Récupérer les anciennes valeurs pour l'audit
            $oldMachine = Machine_model::findById($id);

            if (Machine_model::deleteById($id)) {
                $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Machine supprimée avec succès !'];

                // Audit trail
                if (isset($_SESSION['user']['matricule']) && $oldMachine) {
                    AuditTrail_model::logAudit($_SESSION['user']['matricule'], 'delete', 'init__machine', $oldMachine, null);
                }
            } else {
                $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'mpossible de supprimer cette machine : elle est référencée par d’autres fonctions.'];
            }
        } else {
            $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'ID de la machine non spécifié.'];
        }
        header('Location: ../../platform_gmao/public/index.php?route=machines');
        exit;
    }


    private function getMachineInterventionStats()
    {
        $db = new \App\Models\Database();
        $conn = $db->getConnection();

        $stats = [];

        try {
            // Requête pour récupérer les interventions avec leur type et date
            $query = "SELECT 
                        i.id_machine, 
                        i.date_intervention AS dateIntervention,
                        i.type AS typeIntervention, 
                        p.code_panne AS codePanne,
                        COUNT(i.id_intervention) AS nbInter
                     FROM 
                        gmao__intervention i
                     LEFT JOIN 
                        gmao__panne p ON i.id_panne = p.id_panne
                     GROUP BY 
                        i.id_machine, i.type, p.code_panne
                     ORDER BY 
                        i.id_machine, p.code_panne";

            $stmt = $conn->prepare($query);
            $stmt->execute();
            $interventions = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Organiser les données par machine
            foreach ($interventions as $intervention) {
                $machineId = $intervention['id_machine'];

                if (!isset($stats[$machineId])) {
                    $stats[$machineId] = [];
                }

                $stats[$machineId][] = [
                    'codePanne' => $intervention['codePanne'] ?: 'Non spécifié',
                    'typeIntervention' => $intervention['typeIntervention'],
                    'dateIntervention' => $intervention['dateIntervention'],
                    'nbInter' => (int)$intervention['nbInter']
                ];
            }

            return $stats;
        } catch (\PDOException $e) {
            error_log('Erreur lors de la récupération des statistiques d\'interventions: ' . $e->getMessage());
            return [];
        }
    }
}
