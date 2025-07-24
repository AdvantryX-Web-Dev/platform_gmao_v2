<?php

namespace App\Controllers;

use App\Models\Machine_model;
use App\Models\implantation_Prod_model;
use App\Models\MouvementMachine_model;
use App\Models\Maintainer_model;
use App\Models\Database;
use PDOException;

class Mouvement_machinesController
{
    // public function mouvement_machines()
    // {
    //     include(__DIR__ . '/../views/G_machines/mouvement_machines/inter_machine.php');
    // }
    public function chaine_parc()
    {
        $mouvements = MouvementMachine_model::findChaineParc();
        include(__DIR__ . '/../views/G_machines/mouvement_machines/chaine_parc.php');
    }
    public function inter_chaine()
    {
        $mouvements = MouvementMachine_model::findInterChaine();
        include(__DIR__ . '/../views/G_machines/mouvement_machines/inter_chaine.php');
    }

    public function parc_chaine()
    {
        $mouvements = MouvementMachine_model::findParcChaine();
        include(__DIR__ . '/../views/G_machines/mouvement_machines/parc_chaine.php');
    }



    public function accept()
    {
        // Vérifier si le formulaire a été soumis
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mouvement_id'])) {
            $mouvementId = $_POST['mouvement_id'];
            $type_mouvement = $_POST['type_mouvement'] ?? 'parc_chaine'; // Valeur par défaut
            $machineId = $_POST['machine_id'] ?? null;
            $etat_machine = $_POST['etat_machine'] ?? null;

            // Valider le type de mouvement
            if (!in_array($type_mouvement, ['inter_chaine', 'parc_chaine', 'chaine_parc'])) {
                $type_mouvement = 'parc_chaine'; // Valeur par défaut si invalide
            }

            // Déterminer l'ID de l'utilisateur récepteur
            $userId = $_POST['recepteur'] ?? null;


            if (!$userId) {
                $_SESSION['flash_message'] = [
                    'type' => 'error',
                    'text' => 'Utilisateur non connecté ou non sélectionné.'
                ];
                header('Location: ../../public/index.php?route=mouvement_machines/' . $type_mouvement);
                exit;
            }

            // Mettre à jour le mouvement avec l'ID de l'employé qui accepte
            $db = Database::getInstance('db_GMAO');
            $conn = $db->getConnection();
            $dbdigitex = Database::getInstance('db_digitex');
            $connDigitex = $dbdigitex->getConnection();
            try {
                // Transaction pour assurer l'intégrité des données
                $conn->beginTransaction();

                // 1. Mettre à jour le mouvement
                $stmt = $conn->prepare("UPDATE gmao__mouvement_machine 
                    SET idEmp_accepted = :user_id
                    WHERE num_Mouv_Mach = :mouvement_id");

                $stmt->bindParam(':user_id', $userId);
                $stmt->bindParam(':mouvement_id', $mouvementId);
                $stmt->execute();

                // 2. Récupérer l'ID de la machine associée au mouvement si non fourni
                if (!$machineId) {
                    $stmt = $conn->prepare("SELECT id_machine FROM gmao__mouvement_machine WHERE num_Mouv_Mach = :mouvement_id");
                    $stmt->bindParam(':mouvement_id', $mouvementId);
                    $stmt->execute();
                    $machineId = $stmt->fetchColumn();
                }

                if ($machineId) {
                    // 3. Déterminer l'ID de l'emplacement en fonction du type de mouvement
                    $locationId = null;
                    if ($type_mouvement === 'chaine_parc') {
                        // Trouver l'ID pour "parc"
                        $stmt = $connDigitex->prepare("SELECT id FROM gmao__machine_location WHERE location_name = 'parc' LIMIT 1");
                        $stmt->execute();
                        $locationId = $stmt->fetchColumn();
                    } else { //  inter_chaine ou parc_chaine
                        // Trouver l'ID pour "chaine"
                        $stmt = $connDigitex->prepare("SELECT id FROM gmao__machine_location WHERE location_name = 'prodline' LIMIT 1");
                        $stmt->execute();
                        $locationId = $stmt->fetchColumn();
                    }

                    // 4. Mettre à jour la machine avec le nouvel emplacement et état
                    $updateQuery = "UPDATE init__machine SET ";
                    $params = [];

                    if ($locationId) {
                        $updateQuery .= "machines_location_id = :location_id";
                        $params[':location_id'] = $locationId;
                    }

                    if ($etat_machine) {
                        if ($locationId) $updateQuery .= ", ";
                        $updateQuery .= "machines_status_id = :status_id";
                        $params[':status_id'] = $etat_machine;
                    }

                    if (!empty($params)) {
                        $updateQuery .= " WHERE machine_id = :machine_id";
                        $params[':machine_id'] = $machineId;

                        $stmt = $connDigitex->prepare($updateQuery);
                        foreach ($params as $key => $value) {
                            $stmt->bindValue($key, $value);
                        }
                        $stmt->execute();
                    }
                }

                // Commit la transaction
                $conn->commit();

                $_SESSION['flash_message'] = [
                    'type' => 'success',
                    'text' => 'Réception acceptée avec succès et machine mise à jour.'
                ];
            } catch (PDOException $e) {
                // Annuler la transaction en cas d'erreur
                $conn->rollBack();

                $_SESSION['flash_message'] = [
                    'type' => 'error',
                    'text' => 'Erreur de base de données: ' . $e->getMessage()
                ];
            }

            header('Location: ../../public/index.php?route=mouvement_machines/' . $type_mouvement);
            exit;
        }

        // Si on arrive ici, c'est qu'il y a eu un problème avec la soumission du formulaire
        $_SESSION['flash_message'] = [
            'type' => 'error',
            'text' => 'Formulaire de réception non valide.'
        ];
        header('Location: ../../public/index.php?route=mouvement_machines/parc_chaine');
        exit;
    }




    public function afficherMachines()
    {
        $machines = Machine_model::findAll();
        return $machines;
    }

    public function getChaines()
    {
        return implantation_Prod_model::findAllChaines();
    }

    public function findMachines()
    {
        return implantation_Prod_model::findByMachineNonF();
    }

    public function getMouvementsByType($type)
    {
        return MouvementMachine_model::findByType($type);
    }

    public function getMouvementsByMachine($id_machine)
    {
        return MouvementMachine_model::findByMachine($id_machine);
    }

    public function getMaintainers()
    {
        return Maintainer_model::findAll();
    }
    public function getMachineStatus()
    {
        return Machine_model::getMachineStatus();
    }
    public function getRaisons()
    {
        $db = new \App\Models\Database();
        $conn = $db->getConnection();
        $query = "SELECT * FROM gmao__raison_mouv_mach ORDER BY raison_mouv_mach";
        $stmt = $conn->query($query);
        return $stmt->fetchAll();
    }

    public function saveMouvement()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $machine = $_POST['machine'] ?? '';
            $maintenancier = $_POST['maintenancier'] ?? '';
            $raison = $_POST['raisonMouvement'] ?? '';
            $type_mouvement = $_POST['type_mouvement'] ?? 'parc_chaine'; // Valeur par défaut

            // Valider le type de mouvement
            if (!in_array($type_mouvement, ['inter_chaine', 'parc_chaine', 'chaine_parc'])) {
                $_SESSION['flash_message'] = [
                    'type' => 'error',
                    'text' => 'Type de mouvement invalide.'
                ];
                header('Location: ../../public/index.php?route=mouvement_machines/' . $type_mouvement);
                exit;
            }

            if (empty($machine) || empty($maintenancier) || empty($raison)) {
                $_SESSION['flash_message'] = [
                    'type' => 'error',
                    'text' => 'Tous les champs sont obligatoires.'
                ];
                header('Location: ../../public/index.php?route=mouvement_machines/' . $type_mouvement);
                exit;
            }

            // Enregistrer le mouvement dans la base de données
            $db = new Database();
            $conn = $db->getConnection();

            try {
                $stmt = $conn->prepare("INSERT INTO gmao__mouvement_machine 
                    (date_mouvement, id_machine, id_Rais, idEmp_moved, type_Mouv) 
                    VALUES (NOW(), :machine, :raison, :maintenancier, :type_mouvement)");

                $stmt->bindParam(':machine', $machine);
                $stmt->bindParam(':raison', $raison);
                $stmt->bindParam(':maintenancier', $maintenancier);
                $stmt->bindParam(':type_mouvement', $type_mouvement);
                $stmt->execute();

                if ($stmt->rowCount() > 0) {
                    $_SESSION['flash_message'] = [
                        'type' => 'success',
                        'text' => 'Le mouvement a été enregistré avec succès.'
                    ];
                } else {
                    $_SESSION['flash_message'] = [
                        'type' => 'error',
                        'text' => 'Une erreur est survenue lors de l\'enregistrement du mouvement.'
                    ];
                }
            } catch (PDOException $e) {
                $_SESSION['flash_message'] = [
                    'type' => 'error',
                    'text' => 'Erreur de base de données: ' . $e->getMessage()
                ];
            }

            // Rediriger vers la page d'où provient la demande
            header('Location: ../../public/index.php?route=mouvement_machines/' . $type_mouvement);
            exit;
        }

        // Si la méthode HTTP n'est pas POST, rediriger vers la page par défaut
        header('Location: ../../public/index.php?route=mouvement_machines/parc_chaine');
        exit;
    }
    public function getTypes($location)
    {
        return Machine_model::findAllTypes($location);
    }
    public function getMachinesByType()
    {
        header('Content-Type: application/json');

        if (isset($_GET['type'])) {
            $type = $_GET['type'];
            $location = $_GET['location'];
            $db = Database::getInstance('db_digitex'); // Spécifier explicitement la base de données db_digitex
            $conn = $db->getConnection();

            $query = "SELECT m.id as id, m.machine_id as machine_id, m.reference as reference, m.designation as designation 
                      FROM init__machine m
                      left join gmao__machine_location ml on ml.id=m.machines_location_id
                      WHERE type = :type 
                      and ml.location_name='$location'
                      ORDER BY machine_id";

            $stmt = $conn->prepare($query);
            $stmt->bindParam(':type', $type);
            $stmt->execute();
            $machines = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            echo json_encode($machines);
        } else {
            // Retourner toutes les machines si aucun type n'est spécifié
            $db = new \App\Models\Database();
            $conn = $db->getConnection();

            $query = "SELECT machine_id, reference, designation, type 
                      FROM init__machine 
                      ORDER BY machine_id, ";

            $stmt = $conn->query($query);
            $machines = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            echo json_encode($machines);
        }
        exit;
    }

    public function getPendingReceptionCount()
    {
        $db = new Database();
        $conn = $db->getConnection();

        $stmt = $conn->query("SELECT COUNT(*) FROM gmao__mouvement_machine WHERE type_Mouv = 'parc_chaine' AND idEmp_accepted IS NULL");
        return $stmt->fetchColumn();
    }

    public function showHistoryMachineStatus()
    {
        // Inclure la vue d'historique des mouvements d'une machine
        include(__DIR__ . '/../views/G_machines/G_machines_status/history_machineStatus.php');
    }
}
