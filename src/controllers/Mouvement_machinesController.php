<?php

namespace App\Controllers;
use App\Models\Machine_model;
use App\Models\implantation_Prod_model;
use App\Models\MouvementMachine_model;
use App\Models\Maintainer_model;
use App\Models\Database;
use PDOException;

class Mouvement_machinesController {
    public function mouvement_machines() {
        include(__DIR__ . '/../views/mouvement_machines/inter_machine.php');
    }
    
    public function inter_chaine() {
        $mouvements = MouvementMachine_model::findInterChaine();
        include(__DIR__ . '/../views/mouvement_machines/inter_chaine.php');
    }
    
    public function parc_chaine() {
        $mouvements = MouvementMachine_model::findParcChaine();
        include(__DIR__ . '/../views/mouvement_machines/parc_chaine.php');
    }
    
    // public function pending_reception() {
    //     $mouvements = MouvementMachine_model::findPendingReception();
    //     include(__DIR__ . '/../views/mouvement_machines/pending_reception.php');
    // }
    
    public function accept()
    {
        // Vérifier si le formulaire a été soumis
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mouvement_id'])) {
            $mouvementId = $_POST['mouvement_id'];
            $type_mouvement = $_POST['type_mouvement'] ?? 'parc_chaine'; // Valeur par défaut
            
            // Valider le type de mouvement
            if (!in_array($type_mouvement, ['inter_chaine', 'parc_chaine', 'chaine_parc'])) {
                $type_mouvement = 'parc_chaine'; // Valeur par défaut si invalide
            }
            
            // Déterminer l'ID de l'utilisateur récepteur
            if (isset($_POST['useConnectedUser']) && $_POST['useConnectedUser'] === '1') {
                // Utiliser l'utilisateur connecté
                $userId = $_SESSION['matricule'] ?? null;
            } else {
                // Utiliser l'utilisateur sélectionné
                $userId = $_POST['recepteur'] ?? null;
            }

            if (!$userId) {
                $_SESSION['flash_message'] = [
                    'type' => 'error',
                    'text' => 'Utilisateur non connecté ou non sélectionné.'
                ];
                header('Location: ../../public/index.php?route=mouvement_machines/' . $type_mouvement);
                exit;
            } 

            // Mettre à jour le mouvement avec l'ID de l'employé qui accepte
            $db = new Database();
            $conn = $db->getConnection();

            try {
                $stmt = $conn->prepare("UPDATE gmao__mouvement_machine 
                    SET idEmp_accepted = :user_id, statut = 2
                    WHERE num_Mouv_Mach = :mouvement_id");
                
                $stmt->bindParam(':user_id', $userId);
                $stmt->bindParam(':mouvement_id', $mouvementId);
                $stmt->execute();

                if ($stmt->rowCount() > 0) {
                    $_SESSION['flash_message'] = [
                        'type' => 'success',
                        'text' => 'Réception acceptée avec succès.'
                    ];
                } else {
                    $_SESSION['flash_message'] = [
                        'type' => 'error',
                        'text' => 'Impossible de mettre à jour le mouvement.'
                    ];
                }
            } catch (PDOException $e) {
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

   
    public function chaine_parc() {
        $mouvements = MouvementMachine_model::findChaineParc();
        include(__DIR__ . '/../views/mouvement_machines/chaine_parc.php');
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
                    (date_Mouv_Mach, id_machine, id_Rais, idEmp_moved, type_Mouv, statut) 
                    VALUES (NOW(), :machine, :raison, :maintenancier, :type_mouvement, 1)");
                
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
    public function getTypes()
    {
        return Machine_model::findAllTypes();
    }
    public function getMachinesByType()
    {
        header('Content-Type: application/json');
        
        if (isset($_GET['type'])) {
            $type = $_GET['type'];
            $db = new \App\Models\Database();
            $conn = $db->getConnection();
            
            $query = "SELECT machine_id, reference, designation 
                      FROM init__machine 
                      WHERE type = :type 
                      ORDER BY reference";
            
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
                      ORDER BY type, reference";
            
            $stmt = $conn->query($query);
            $machines = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            echo json_encode($machines);
        }
        exit;
    }

    public function getPendingReceptionCount() {
        $db = new Database();
        $conn = $db->getConnection();
        
        $stmt = $conn->query("SELECT COUNT(*) FROM gmao__mouvement_machine WHERE type_Mouv = 'parc_chaine' AND idEmp_accepted IS NULL");
        return $stmt->fetchColumn();
    }
}