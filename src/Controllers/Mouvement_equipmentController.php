<?php

namespace App\Controllers;

use App\Models\Machine_model;
use App\Models\Mouvement_equipment_model;
use App\Models\Maintainer_model;
use App\Models\Database;
use App\Models\Equipement_model;
use PDOException;

class Mouvement_equipmentController
{

    public function entre_magasin()
    {
        $mouvements = Mouvement_equipment_model::findEntreMagasin();

        include(__DIR__ . '/../views/G_equipements/mouvement_equipement/entre_magasin.php');
    }

    public function sortie_magasin()
    {
        $mouvements = Mouvement_equipment_model::findSortieMagasin();
        include(__DIR__ . '/../views/G_equipements/mouvement_equipement/sortie_magasin.php');
    }



    public function accept()
    {
        // Vérifier si le formulaire a été soumis
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mouvement_id'])) {
            $mouvementId = $_POST['mouvement_id'];
            $type_mouvement = $_POST['type_mouvement'] ?? 'entre_magasin'; // Valeur par défaut
            $equipmentId = $_POST['equipment_id'] ?? null;
            $etat_equipement = $_POST['etat_equipement'] ?? null;
            // Valider le type de mouvement
            if (!in_array($type_mouvement, ['entre_magasin', 'sortie_magasin'])) {
                $type_mouvement = 'entre_magasin'; // Valeur par défaut si invalide
            }

            // Déterminer l'ID de l'utilisateur récepteur
            $userId = $_POST['recepteur'] ?? null;


            if (!$userId) {
                $_SESSION['flash_message'] = [
                    'type' => 'error',
                    'text' => 'Utilisateur non connecté ou non sélectionné.'
                ];
                header('Location: ../../platform_gmao/public/index.php?route=mouvement_machines/' . $type_mouvement);
                exit;
            }

            // Mettre à jour le mouvement avec l'ID de l'employé qui accepte
            $db = new  Database();
            $conn = $db->getConnection();

            try {

                // Transaction pour assurer l'intégrité des données
                $conn->beginTransaction();

                // 1. Mettre à jour le mouvement
                $stmt = $conn->prepare("UPDATE gmao__mouvement_equipment 
                    SET idEmp_accepted = :user_id
                    WHERE id = :mouvement_id");

                $stmt->bindParam(':user_id', $userId);
                $stmt->bindParam(':mouvement_id', $mouvementId);
                $stmt->execute();

                // 2. Récupérer l'ID de la machine associée au mouvement si non fourni
                if (!$equipmentId) {
                    $stmt = $conn->prepare("SELECT equipment_id FROM gmao__mouvement_equipment WHERE id = :mouvement_id");
                    $stmt->bindParam(':mouvement_id', $mouvementId);
                    $stmt->execute();
                    $equipmentId = $stmt->fetchColumn();
                }

                if ($equipmentId) {

                    // 3. Déterminer l'ID de l'emplacement en fonction du type de mouvement
                    $locationId = null;
                    if ($type_mouvement === 'entre_magasin') {
                        // Trouver l'ID pour "parc"
                        $stmt = $conn->prepare("SELECT id FROM gmao__location WHERE location_category = 'magasin' LIMIT 1");
                        $stmt->execute();
                        $locationId = $stmt->fetchColumn();
                    } else { //  inter_chaine ou parc_chaine
                        // Trouver l'ID pour "chaine"
                        $stmt = $conn->prepare("SELECT id FROM gmao__location WHERE location_category = 'prodline' LIMIT 1");
                        $stmt->execute();
                        $locationId = $stmt->fetchColumn();
                    }

                    // 4. Mettre à jour la equipments avec le nouvel emplacement et état
                    $updateQuery = "UPDATE gmao__init_equipment SET ";
                    $params = [];

                    if ($locationId) {
                        $updateQuery .= "location_id = :location_id";
                        $params[':location_id'] = $locationId;
                    }

                    if ($etat_equipement) {

                        $updateQuery .= "status_id = :status_id";
                        $params[':status_id'] = $etat_equipement;
                    }


                    if (!empty($params)) {
                        $updateQuery = "UPDATE gmao__init_equipment SET location_id = :location_id, status_id = :status_id";
                        $updateQuery .= " WHERE equipment_id = :equipment_id";

                        $params[':equipment_id'] = $equipmentId;

                        $stmt = $conn->prepare($updateQuery);
                        foreach ($params as $key => $value) {
                            $stmt->bindValue($key, $value);
                        }
                        $stmt->execute();
                    }


                    if ($equipmentId) {

                        // 6. Mettre à jour la table gmao__prod_implementation_equipment - marquer l'équipement comme retiré
                        $stmt = $conn->prepare("UPDATE gmao__prod_implementation_equipment 
                            SET is_removed = 1, removed_at = CURRENT_TIMESTAMP 
                            WHERE accessory_ref = :equipment_id");
                        $stmt->bindParam(':equipment_id', $equipmentId);
                        $stmt->execute();
                    }
                }

                // Commit la transaction
                $conn->commit();

                $_SESSION['flash_message'] = [
                    'type' => 'success',
                    'text' => 'Réception acceptée avec succès et equipment mise à jour.'
                ];
            } catch (PDOException $e) {
                // Annuler la transaction en cas d'erreur
                $conn->rollBack();

                $_SESSION['flash_message'] = [
                    'type' => 'error',
                    'text' => 'Erreur de base de données: ' . $e->getMessage()
                ];
            }

            header('Location: ../../platform_gmao/public/index.php?route=mouvement_equipements/' . $type_mouvement);
            exit;
        }

        // Si on arrive ici, c'est qu'il y a eu un problème avec la soumission du formulaire
        $_SESSION['flash_message'] = [
            'type' => 'error',
            'text' => 'Formulaire de réception non valide.'
        ];
        header('Location: ../../platform_gmao/public/index.php?route=mouvement_equipements/. $type_mouvement');
        exit;
    }


    public function getEquipements($location)
    {
        $equipements = Equipement_model::getEquipements($location);

        return $equipements;
    }

    public function getMachines()
    {
        $machines = Machine_model::findAll();
        return $machines;
    }

    public function getMaintainers()
    {
        return Maintainer_model::findAll();
    }

    public function getRaisons()
    {
        $db = new Database();
        $conn = $db->getConnection();
        $query = "SELECT * FROM gmao__raison_mouv_mach ORDER BY raison_mouv_mach";
        $stmt = $conn->query($query);
        return $stmt->fetchAll();
    }


    public function saveMouvement()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $machine = isset($_POST['machine']) && $_POST['machine'] !== '' ? (int)$_POST['machine'] : null;
            $equipment = $_POST['equipment'] ?? '';
            $maintenancier = $_POST['maintenancier'] ?? '';
            $raison = $_POST['raisonMouvement'] ?? '';
            $type_mouvement = $_POST['type_mouvement'] ?? 'entre_magasin'; // Valeur par défaut


            // Valider le type de mouvement
            if (!in_array($type_mouvement, ['entre_magasin', 'sortie_magasin'])) {
                $_SESSION['flash_message'] = [
                    'type' => 'error',
                    'text' => 'Type de mouvement invalide.'
                ];
                header('Location: ../../platform_gmao/public/index.php?route=mouvement_equipements/' . $type_mouvement);
                exit;
            }

            if (empty($equipment) || empty($maintenancier) || empty($raison)) {
                $_SESSION['flash_message'] = [
                    'type' => 'error',
                    'text' => 'Tous les champs sont obligatoires.'
                ];
                header('Location: ../../platform_gmao/public/index.php?route=mouvement_equipements/' . $type_mouvement);
                exit;
            }

            // Enregistrer le mouvement dans la base de données
            $db = new Database();
            $conn = $db->getConnection();
           

            try {
                $stmt = $conn->prepare("INSERT INTO gmao__mouvement_equipment
                    (date_mouvement, id_machine, equipment_id, id_Rais, idEmp_moved, type_Mouv) 
                    VALUES (NOW(), :machine, :equipment, :raison, :maintenancier, :type_mouvement)");

                $stmt->bindParam(':machine', $machine);
                $stmt->bindParam(':equipment', $equipment);
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
                die($e->getMessage());
                $_SESSION['flash_message'] = [
                    'type' => 'error',
                    'text' => 'Erreur de base de données: ' . $e->getMessage()
                ];
            }

            // Rediriger vers la page d'où provient la demande
            header('Location: ../../platform_gmao/public/index.php?route=mouvement_equipements/' . $type_mouvement);
            exit;
        }

        // Si la méthode HTTP n'est pas POST, rediriger vers la page par défaut
        header('Location: ../../platform_gmao/public/index.php?route=mouvement_equipements/entre_magasin');
        exit;
    }
    public function getEquipementStatus()
    {
        $etat_equipement = Equipement_model::getEquipementStatus();
        return $etat_equipement;
    }

    public function getPendingReceptionCount()
    {
        $db = new Database();
        $conn = $db->getConnection();

        $stmt = $conn->query("SELECT COUNT(*) FROM gmao__mouvement_equipment WHERE type_Mouv = 'entre_magasin' AND idEmp_accepted IS NULL");
        return $stmt->fetchColumn();
    }

    public function showHistoryEquipement()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $equipements = Mouvement_equipment_model::historiqueEquipement();
        include(__DIR__ . '/../views/G_equipements/G_equipement_status/history_equipmentStatus.php');
    }
}
