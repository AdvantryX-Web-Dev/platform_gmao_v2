<?php

namespace App\Controllers;

use App\Models\Accessory_model;
use App\Models\AuditTrail_model;
use App\Models\Equipement_model;

class AccessoryController
{

    /**
     * Traite l'affectation d'un équipement à une machine
     */
    public function affecter()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Vérifier si le formulaire a été soumis
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Récupérer les données du formulaire
            $equipment_id = $_POST['equipment_id'] ?? '';
            $machine_id = $_POST['machine_id'] ?? '';
            $maintainer = $_POST['maintainer'] ?? '';
            $mvt_state = $_POST['mvt_state'] ?? 'SS';
            $allocation_time = $_POST['allocation_time'] ?? date('H:i:s');
            $allocation_date = $_POST['allocation_date'] ?? date('Y-m-d');

            // Vérifier que les données obligatoires sont présentes
            if (empty($equipment_id) || empty($machine_id) || empty($maintainer) || empty($allocation_date)) {
                $_SESSION['flash_error'] = 'Veuillez remplir tous les champs obligatoires.';
                header('Location: /platform_gmao/public/index.php?route=equipement_machine/affectation_equipmentMachine');
                exit;
            }

            // Vérifier que l'équipement existe dans la liste des équipements
            if (!Equipement_model::existsByEquipmentId($equipment_id)) {
                $_SESSION['flash_error'] = "Cet équipement n'existe pas. Veuillez l'enregistrer avant l'affectation.";
                header('Location: /platform_gmao/public/index.php?route=equipement_machine/affectation_scan');
                exit;
            }

            // Charger les détails de l'équipement pour contrôle statut et localisation
            $equipement = Equipement_model::findByEquipmentIdWithDetails($equipment_id);
            $locationName = strtolower(trim($equipement['location_category'] ?? ''));
            $statusName = strtolower(trim($equipement['status_name'] ?? ''));

            // Bloquer si en production (emplacement = magasin) ou statut non fonctionnel
            if ($locationName === 'magasin' || $statusName === 'non fonctionnelle') {
                $_SESSION['flash_error'] = "Affectation bloquée: l'équipement est en magasin ou son statut est non fonctionnel.";
                header('Location: /platform_gmao/public/index.php?route=equipement_machine/affectation_scan');
                exit;
            }

            // Vérifier si l'équipement est déjà affecté à une machine
            if (Accessory_model::isAlreadyAllocated($equipment_id)) {
                $_SESSION['flash_error'] = 'Cet équipement est déjà affecté à une machine.';
                header('Location: /platform_gmao/public/index.php?route=equipement_machine/affectation_scan');
                exit;
            }

            // Créer un nouvel objet Accessory
            $accessory = new Accessory_model(
                null,
                $equipment_id,
                $machine_id,
                $maintainer,
                $mvt_state,
                $allocation_time,
                $allocation_date
            );

            // Ajouter l'affectation en base de données
            if (Accessory_model::add($accessory)) {
                // Journaliser l'action dans l'audit trail
                if (isset($_SESSION['user']['matricule'])) {
                    $newValues = [
                        'accessory_ref' => $equipment_id,
                        'machine_id' => $machine_id,
                        'maintainer' => $maintainer,
                        'mvt_state' => $mvt_state,
                        'allocation_time' => $allocation_time,
                        'allocation_date' => $allocation_date
                    ];
                    AuditTrail_model::logAudit($_SESSION['user']['matricule'], 'add', 'gmao__prod_implementation_equipment', null, $newValues);
                }

                $_SESSION['equipement_success'] = 'Équipement affecté avec succès !';
            } else {
                $_SESSION['flash_error'] = 'Une erreur est survenue lors de l\'affectation.';
            }

            // Rediriger vers le formulaire d'affectation
            header('Location: /platform_gmao/public/index.php?route=equipement_machine');
            exit;
        } else {
            // Si la méthode n'est pas POST, rediriger vers le formulaire
            header('Location: /platform_gmao/public/index.php?route=equipement_machine/affectation_equipmentMachine');
            exit;
        }
    }
}
