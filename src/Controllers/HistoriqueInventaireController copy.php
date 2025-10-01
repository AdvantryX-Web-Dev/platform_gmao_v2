<?php

namespace App\Controllers;

use App\Models\Database;
use App\Models\HistoriqueInventaire_model;
use App\Models\Maintainer_model;

class HistoriqueInventaireController
{
    public function index()
    {
        $db = Database::getInstance('db_digitex');
        $conn = $db->getConnection();

        // Vérifier si l'utilisateur connecté est admin
        $isAdmin = isset($_SESSION['qualification']) && $_SESSION['qualification'] === 'ADMINISTRATEUR';
        $connectedMatricule = $_SESSION['user']['matricule'] ?? '';

        // Récupérer les filtres depuis GET
        $filterMaintainer = $_GET['filter_maintainer'] ?? '';
        $filterStatus = $_GET['filter_status'] ?? '';

        // Si l'utilisateur n'est pas admin, forcer le filtre sur son matricule
        if (!$isAdmin && $connectedMatricule) {
            $filterMaintainer = $connectedMatricule;
        }

        // Récupérer toutes les machines de init__machine
        $allMachines = HistoriqueInventaire_model::getAllMachines();

        // Récupérer l'historique d'inventaire le plus récent pour chaque machine
        $historiqueData = HistoriqueInventaire_model::getLatestHistorique();

        // Récupérer tous les maintenanciers pour le filtre (seulement si admin)
        $allMaintainers = $isAdmin ? Maintainer_model::findAll() : [];

        // Créer un mapping par machine_id pour l'historique
        $historiqueMap = [];
        foreach ($historiqueData as $item) {
            $historiqueMap[$item['machine_id']] = $item;
        }

        // Préparer les données de comparaison
        $comparisons = [];
        $confirmes = 0;        // Machines inventoriées et conformes
        $differences = 0;      // Machines inventoriées avec écarts
        $manquantes = 0;       // Machines pas inventoriées (dans init__machine mais pas dans historique)
        $supprimees = 0;
        // Machines supprimées (dans historique mais plus dans init__machine)

        // 1. Traiter les machines accessibles à l'utilisateur (userMachines)
        // foreach ($userMachines as $machine) {
        foreach ($allMachines as $machine) {
            $machineId = $machine['id'];
            $comparison = [
                'machine' => $machine,
                'historique' => isset($historiqueMap[$machineId]) ? $historiqueMap[$machineId] : null,
                'status' => 'manquante' // par défaut
            ];

            if (isset($historiqueMap[$machineId])) {
                // Machine trouvée dans l'historique = elle a été inventoriée
                $historique = $historiqueMap[$machineId];

                // Comparer location et status pour voir si elle est conforme
                $locationMatch = ($machine['machines_location_id'] == $historique['location_id']);
                $statusMatch = ($machine['machines_status_id'] == $historique['status_id']);

                if ($locationMatch && $statusMatch) {
                    $comparison['status'] = 'inventoriee_conforme';  // Machine inventoriée et conforme
                    $confirmes++;
                } else {
                    $comparison['status'] = 'inventoriee_ecart';     // Machine inventoriée avec écart
                    $differences++;
                }
            } else {
                // Machine dans userMachines mais pas dans historique = pas inventoriée (manquante)
                $comparison['status'] = 'non_inventoriee';
                $manquantes++;
            }

            $comparisons[] = $comparison;
        }

        // 2. Chercher les machines supprimées (dans historique mais plus dans init__machine)
        foreach ($historiqueData as $item) {
            $found = false;
            foreach ($allMachines as $machine) {
                if ($machine['id'] == $item['machine_id']) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                // Machine dans historique mais plus dans init__machine = machine supprimée
                $comparison = [
                    'machine' => null, // Pas de données machine actuelle
                    'historique' => $item,
                    'status' => 'machine_supprimee'
                ];
                $comparisons[] = $comparison;
                $supprimees++; // On garde le même compteur pour compatibilité
            }
        }

        // 3. Appliquer les filtres backend
        if ($filterMaintainer || $filterStatus) {
            $filteredComparisons = [];
            foreach ($comparisons as $comp) {
                $showRow = true;

                // Filtrer par maintenancier
                if ($filterMaintainer && $filterMaintainer !== '') {
                    if (!$comp['historique'] || $comp['historique']['maintainer_matricule'] !== $filterMaintainer) {
                        $showRow = false;
                    }
                }

                // Filtrer par statut
                if ($filterStatus && $filterStatus !== '') {
                    if ($comp['status'] !== $filterStatus) {
                        $showRow = false;
                    }
                }

                if ($showRow) {
                    $filteredComparisons[] = $comp;
                }
            }
            $comparisons = $filteredComparisons;

            // Recalculer les compteurs après filtrage
            $confirmes = 0;
            $differences = 0;
            $manquantes = 0;
            $supprimees = 0;

            foreach ($comparisons as $comp) {
                switch ($comp['status']) {
                    case 'inventoriee_conforme':
                        $confirmes++;
                        break;
                    case 'inventoriee_ecart':
                        $differences++;
                        break;
                    case 'non_inventoriee':
                        $manquantes++;
                        break;
                    case 'machine_supprimee':
                        $supprimees++;
                        break;
                }
            }
        }

        // Calculer le total de machines selon les permissions (même logique que maintenancier_machine)
        $totalMachines = $this->getTotalMachinesForUser($isAdmin, $connectedMatricule);
        $totalInventoriees = $confirmes + $differences; // Machines qui ont été inventoriées
        $totalNonInventoriees = $manquantes;

        // Calculer les pourcentages
        $pourcentageConformite = $totalInventoriees > 0 ? round(($confirmes / $totalInventoriees) * 100, 1) : 0;
        $pourcentageCouverture = $totalMachines > 0 ? round(($totalInventoriees / $totalMachines) * 100, 1) : 0;
        $pourcentageEcarts = $totalInventoriees > 0 ? round(($differences / $totalInventoriees) * 100, 1) : 0;

        // Passer les variables à la vue
        $machine_supprimees = $supprimees; // Pour compatibilité avec la vue

        include __DIR__ . '/../views/inventaire/historique_inventaire.php';
    }

    /**
     * Calcule le total de machines selon les permissions utilisateur
     * (même logique que InventaireController::maintenancier_machine)
     */
    private function getTotalMachinesForUser($isAdmin, $connectedMatricule)
    {
        $db = Database::getInstance('db_digitex');
        $conn = $db->getConnection();

        // Construire la requête de base - une ligne par machine (même logique que maintenancier_machine)
        $sql = "
            SELECT COUNT(DISTINCT mm.machine_id) as total_machines
            FROM (
                SELECT mm.*
                FROM gmao__machine_maint mm
                INNER JOIN (
                    SELECT MAX(id) AS id
                    FROM gmao__machine_maint
                    GROUP BY machine_id
                ) last ON last.id = mm.id
            ) mm
            LEFT JOIN init__employee e ON e.id = mm.maintener_id
            LEFT JOIN init__machine m ON m.id = mm.machine_id
            WHERE 1=1
        ";

        $params = [];

        // Si pas admin, filtrer par matricule connecté (même logique que maintenancier_machine)
        if (!$isAdmin && $connectedMatricule) {
            $sql .= " AND e.matricule = :matricule";
            $params['matricule'] = $connectedMatricule;
        }

        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindParam(':' . $key, $value);
        }
        $stmt->execute();

        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return (int)($result['total_machines'] ?? 0);
    }

    /**
     * Récupère les machines selon les permissions utilisateur
     * (même logique que InventaireController::maintenancier_machine)
     */
    // private function getUserMachines($isAdmin, $connectedMatricule)
    // {
    //     $db = Database::getInstance('db_digitex');
    //     $conn = $db->getConnection();

    //     // Construire la requête de base - une ligne par machine (même logique que maintenancier_machine)
    //     $sql = "
    //         SELECT DISTINCT
    //             m.id,
    //             m.machine_id,
    //             m.reference,
    //             m.designation,
    //             m.type,
    //             m.machines_location_id,
    //             m.machines_status_id,
    //             l.location_name,
    //             l.location_category,
    //             s.status_name
    //         FROM (
    //             SELECT mm.*
    //             FROM gmao__machine_maint mm
    //             INNER JOIN (
    //                 SELECT MAX(id) AS id
    //                 FROM gmao__machine_maint
    //                 GROUP BY machine_id
    //             ) last ON last.id = mm.id
    //         ) mm
    //         LEFT JOIN init__employee e ON e.id = mm.maintener_id
    //         LEFT JOIN init__machine m ON m.id = mm.machine_id
    //         LEFT JOIN gmao__location l ON l.id = m.machines_location_id
    //         LEFT JOIN gmao__status s ON s.id = m.machines_status_id
    //         WHERE 1=1
    //     ";

    //     $params = [];

    //     // Si pas admin, filtrer par matricule connecté (même logique que maintenancier_machine)
    //     if (!$isAdmin && $connectedMatricule) {
    //         $sql .= " AND e.matricule = :matricule";
    //         $params['matricule'] = $connectedMatricule;
    //     }

    //     $sql .= " ORDER BY m.machine_id ASC";

    //     $stmt = $conn->prepare($sql);
    //     foreach ($params as $key => $value) {
    //         $stmt->bindParam(':' . $key, $value);
    //     }
    //     $stmt->execute();

    //     return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    // }
}
