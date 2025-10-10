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
        // Récupérer tous les maintenanciers pour le filtre (seulement si admin)
        $allMaintainers = $isAdmin ? Maintainer_model::findAll() : [];
        $allmachine = HistoriqueInventaire_model::getAllMachines($isAdmin, $filterMaintainer);

        // Récupérer les machines selon les permissions utilisateur (même logique que maintenancier_machine)
        $Machines_maint = $this->getUserMachines($isAdmin, $connectedMatricule);


        // Récupérer l'historique d'inventaire le plus récent pour chaque machine
        $historiqueData = HistoriqueInventaire_model::getLatestHistorique();
        if ($isAdmin) {
            $userMachines = $allmachine;
        } else {
            $userMachines = $Machines_maint;
        }

        // Créer des mappings
        $historiqueMap = [];
        foreach ($historiqueData as $item) {
            $historiqueMap[$item['machine_id']] = $item;
        }

        $userMachinesMap = [];
        foreach ($userMachines as $machine) {
            $userMachinesMap[$machine['id']] = $machine;
        }

        // Préparer les données de comparaison
        $comparisons = [];
        $confirmes = 0;        // Machines inventoriées et conformes
        $differences = 0;      // Machines inventoriées avec differences
        $supprimer = 0;       //non_inventoriee : Machines pas inventoriées (dans init__machine mais pas dans historique)
        $ajouter = 0;        // Machines supprimées (dans historique mais plus dans init__machine)

        // 1. Traiter les machines accessibles à l'utilisateur (userMachines)
        foreach ($userMachines as $machine) {
            $machineId = $machine['id'];
            $comparison = [
                'machine' => $machine,
                'historique' => isset($historiqueMap[$machineId]) ? $historiqueMap[$machineId] : null,
                'status' => 'non_inventoriee' // par défaut
            ];

            if (isset($historiqueMap[$machineId])) {
                // Machine trouvée dans l'historique = elle a été inventoriée
                $historique = $historiqueMap[$machineId];

                // Comparer location et status pour voir si elle est conforme
                $locationMatch = ($machine['machines_location_id'] == $historique['location_id']);
                $statusMatch = ($machine['machines_status_id'] == $historique['status_id']);
                $maintenancierMatch = ($machine['current_maintainer_matricule'] == $historique['maintainer_matricule']);
                if ($locationMatch && $statusMatch && $maintenancierMatch) {
                    $comparison['status'] = 'conforme';  // Machine inventoriée et conforme
                    $confirmes++;
                } else {
                    $comparison['status'] = 'non_conforme';     // Machine inventoriée avec écart
                    $differences++;
                }
            } else {
                // Machine dans userMachines mais pas dans historique = pas inventoriée (manquante)
                $comparison['status'] = 'non_inventoriee';
                $supprimer++;
            }

            $comparisons[] = $comparison;
        }
        // 2. Chercher les machines inventoriées par l'utilisateur mais plus assignées à lui(machine ajouter)
        foreach ($historiqueData as $item) {
            if (!isset($userMachinesMap[$item['machine_id']])) {
                // Vérifier si cette machine a été inventoriée par l'utilisateur connecté
                $shouldInclude = false;

                if ($isAdmin) {
                    // Admin voit toutes les machines ajoutées/supprimées
                    $shouldInclude = true;
                } else {
                    // Maintenancier voit seulement les machines qu'il a inventoriées
                    if ($item['maintainer_matricule'] == $connectedMatricule) {
                        $shouldInclude = true;
                    }
                }

                if ($shouldInclude) {
                    $comparison = [
                        'machine' => null, // Pas de données machine actuelle pour cet utilisateur
                        'historique' => $item,
                        'status' => 'ajouter'
                    ];
                    $comparisons[] = $comparison;
                    $ajouter++;
                }
            }
        }

        // 3. Appliquer les filtres backend
        if ($filterMaintainer || $filterStatus) {
            $filteredComparisons = [];
            foreach ($comparisons as $comp) {
                $showRow = true;

                // Filtrer par maintenancier
                if ($filterMaintainer && $filterMaintainer !== '') {
                    $maintainerMatch = false;
                    
                    // Pour les machines ajoutées, vérifier le maintenancier dans l'historique
                    if ($comp['status'] == 'ajouter') {
                        if ($comp['historique'] && $comp['historique']['maintainer_matricule'] == $filterMaintainer) {
                            $maintainerMatch = true;
                        }
                    } else {
                        // Pour les machines normales, vérifier le maintenancier actuel
                        if ($comp['machine'] && $comp['machine']['current_maintainer_matricule'] == $filterMaintainer) {
                            $maintainerMatch = true;
                        }
                    }
                    
                    if (!$maintainerMatch) {
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
            $supprimer = 0;
            $ajouter = 0;

            foreach ($comparisons as $comp) {
                switch ($comp['status']) {
                    case 'conforme':
                        $confirmes++;
                        break;
                    case 'non_conforme':
                        $differences++;
                        break;
                    case 'non_inventoriee':
                        $supprimer++;
                        break;
                    case 'ajouter':
                        $ajouter++;
                        break;
                }
            }
        }

        // Calculer le total de machines selon les permissions (même logique que maintenancier_machine)
        // Utiliser le filtre maintenancier seulement si c'est un admin qui filtre
        $totalMachinesFilter = ($isAdmin && $filterMaintainer) ? $filterMaintainer : '';
        $totalMachinesAdmin = count(HistoriqueInventaire_model::getAllMachines($isAdmin, $totalMachinesFilter));
        $totalMachinesNoAdmin = count($this->getUserMachines($isAdmin, $connectedMatricule));
        $totalMachines = $isAdmin ? $totalMachinesAdmin : $totalMachinesNoAdmin;
        $totalInventoriees = $confirmes + $differences; // Machines qui ont été inventoriées
        $totalNonInventoriees = $supprimer; // Machines non inventoriées

        // Calculer les pourcentages
        $pourcentageConformite = $totalMachines > 0 ? round(($confirmes / $totalMachines) * 100, 1) : 0;
        $pourcentageinventoriees = $totalMachines > 0 ? round(($totalInventoriees / $totalMachines) * 100, 1) : 0;
        $pourcentageNonConforme = $totalMachines > 0 ? round((($differences ) / $totalMachines) * 100, 1) : 0;

        include __DIR__ . '/../views/inventaire/historique_inventaire.php';
    }

    /**
     * Récupère les machines selon les permissions utilisateur
     * (même logique que InventaireController::maintenancier_machine)
     */
    private function getUserMachines($isAdmin, $connectedMatricule)
    {
        $db = Database::getInstance('db_digitex');
        $conn = $db->getConnection();

        // Construire la requête de base - une ligne par machine (même logique que maintenancier_machine)
        $sql = "
            SELECT DISTINCT
                m.id,
                m.machine_id,
                m.reference,
                m.designation,
                m.type,
                m.machines_location_id,
                m.machines_status_id,
                l.location_name,
                l.location_category,
                s.status_name,
                -- Maintenancier actuel
                current_maint.maintener_id AS current_maintainer_id,
                current_emp.matricule AS current_maintainer_matricule,
                CONCAT(current_emp.first_name, ' ', current_emp.last_name) AS current_maintainer_name
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
            LEFT JOIN gmao__location l ON l.id = m.machines_location_id
            LEFT JOIN gmao__status s ON s.id = m.machines_status_id
            -- Jointure pour le maintenancier actuel (dernier enregistrement dans gmao__machine_maint)
            LEFT JOIN (
                SELECT mm.*
                FROM gmao__machine_maint mm
                INNER JOIN (
                    SELECT machine_id, MAX(id) AS max_id
                    FROM gmao__machine_maint
                    GROUP BY machine_id
                ) mm_latest ON mm.machine_id = mm_latest.machine_id AND mm.id = mm_latest.max_id
            ) current_maint ON current_maint.machine_id = m.id
            LEFT JOIN init__employee current_emp ON current_emp.id = current_maint.maintener_id
            WHERE 1=1
        ";

        $params = [];

        // Si pas admin, filtrer par matricule connecté (même logique que maintenancier_machine)
        if (!$isAdmin && $connectedMatricule) {
            $sql .= " AND e.matricule = :matricule";
            $params['matricule'] = $connectedMatricule;
        }

        $sql .= " ORDER BY m.machine_id ASC";

        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindParam(':' . $key, $value);
        }
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
