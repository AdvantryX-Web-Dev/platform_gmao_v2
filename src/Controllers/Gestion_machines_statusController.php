<?php

namespace App\Controllers;

use App\Models\Machine_model;
use App\Models\Database;

class Gestion_machines_statusController
{
    /** Gestion des machines */
    public function machines_state()
    {
        try {
            // Récupérer le matricule de l'utilisateur connecté
            $userMatricule = $_SESSION['user']['matricule'] ?? null;

            // Récupérer les filtres depuis GET
            $filters = [
                'matricule' => $_GET['matricule'] ?? null,
                'machine_id' => $_GET['machine_id'] ?? null,
                'location' => $_GET['location'] ?? null,
                'status' => $_GET['status'] ?? null,
                'type' => $_GET['type'] ?? null
            ];

            // Appeler le modèle avec le matricule de l'utilisateur et les filtres
            $machinesData = Machine_model::GMachinesStateTable($userMatricule, $filters);
            if (!is_array($machinesData)) {
                $machinesData = [];
            }

            // Récupérer les listes pour les filtres
            $maintainers = Machine_model::getMaintainersList();
            $machinesList = Machine_model::getMachinesList();
            $locations = Machine_model::getLocationsList();
            $statuses = Machine_model::getMachineStatus();
            $typeMachine = Machine_model::getTypeMachine();

            // Vérifier si l'utilisateur est admin
            $isAdmin = isset($_SESSION['qualification']) && $_SESSION['qualification'] === 'ADMINISTRATEUR';

            include(__DIR__ . '/../views/G_machines/G_machines_status/machineStatus.php');
        } catch (\Exception $e) {
            error_log('Erreur dans machines_state: ' . $e->getMessage());
            $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Erreur lors du chargement des données des machines.'];
            include(__DIR__ . '/../views/G_machines/G_machines_status/machineStatus.php');
        }
    }

    /**
     * Vue filtrée suite à un clic sur une carte
     */
    public function machines_state_filtered()
    {
        try {
            $userMatricule = $_SESSION['user']['matricule'] ?? null;
            $cardKey = $_GET['card'] ?? null;

            $filters = [
                'matricule' => $_GET['matricule'] ?? null,
                'machine_id' => $_GET['machine_id'] ?? null,
                'location' => $_GET['location'] ?? null,
                'status' => $_GET['status'] ?? null,
                'type' => $_GET['type'] ?? null
            ];

            $machinesData = Machine_model::GMachinesStateTable($userMatricule, $filters);

            $cardFilters = [
                'undefined' => [
                    'label' => 'Machines sans emplacement',
                    'predicate' => function ($machine) {
                        return empty($machine['location']) && empty($machine['location_category']);
                    }
                ],
                'breakdown' => [
                    'label' => 'Machines en panne',
                    'predicate' => function ($machine) {
                        $status = strtolower($machine['status_name_final'] ?? $machine['etat_machine'] ?? '');
                        return $status === 'en panne';
                    },
                    'preset' => ['status_label' => 'en panne']
                ],
                'scrap' => [
                    'label' => 'Machines ferraillées',
                    'predicate' => function ($machine) {
                        $status = strtolower($machine['status_name_final'] ?? $machine['etat_machine'] ?? '');
                        return $status === 'ferraille';
                    },
                    'preset' => ['status_label' => 'ferraille']
                ],
                'inactive' => [
                    'label' => 'Machines inactives',
                    'predicate' => function ($machine) {
                        $status = strtolower($machine['status_name_final'] ?? $machine['etat_machine'] ?? '');
                        return $status === 'inactive';
                    },
                    'preset' => ['status_label' => 'inactive']
                ],
                'inactive_delayed' => [
                    'label' => 'Machines inactives (+3 jours)',
                    'predicate' => function ($machine) {
                        $status = strtolower($machine['status_name_final'] ?? $machine['etat_machine'] ?? '');
                        $lastPresenceDate = $machine['cur_date_time'] ?? null;
                        if ($status === 'inactive' && !empty($lastPresenceDate)) {
                            try {
                                $presenceDate = new \DateTime($lastPresenceDate);
                                $thresholdDate = new \DateTime('-3 days');
                                return $presenceDate <= $thresholdDate;
                            } catch (\Exception $e) {
                                return false;
                            }
                        }
                        return false;
                    }
                ]
            ];

            $filtersLocked = false;
            $cardFilterLabel = null;
            $lockedFilterValues = [];

            if ($cardKey && isset($cardFilters[$cardKey])) {
                $filtersLocked = true;
                $cardFilterLabel = $cardFilters[$cardKey]['label'];
                $machinesData = array_values(array_filter(
                    $machinesData,
                    $cardFilters[$cardKey]['predicate']
                ));
                $lockedFilterValues = $cardFilters[$cardKey]['preset'] ?? [];
            }

            $maintainers = Machine_model::getMaintainersList();
            $machinesList = Machine_model::getMachinesList();
            $locations = Machine_model::getLocationsList();
            $statuses = Machine_model::getMachineStatus();
            $typeMachine = Machine_model::getTypeMachine();

            if (!empty($lockedFilterValues['status_label'])) {
                foreach ($statuses as $status) {
                    if (strcasecmp($status['status_name'], $lockedFilterValues['status_label']) === 0) {
                        $lockedFilterValues['status'] = $status['id'];
                        break;
                    }
                }
            }

            $currentFilters = $_GET;
            if (isset($lockedFilterValues['status'])) {
                $currentFilters['status'] = $lockedFilterValues['status'];
            }

            $isAdmin = isset($_SESSION['qualification']) && $_SESSION['qualification'] === 'ADMINISTRATEUR';

            include(__DIR__ . '/../views/G_machines/G_machines_status/machineStatusFiltre.php');
        } catch (\Exception $e) {
            error_log('Erreur dans machines_state_filtered: ' . $e->getMessage());
            $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Erreur lors du chargement du filtre.'];
            include(__DIR__ . '/../views/G_machines/G_machines_status/machineStatusFiltre.php');
        }
    }

    /**
     * Export des données des machines en Excel
     */
    public function export_machines_state()
    {
        try {
            // Récupérer le matricule de l'utilisateur connecté
            $userMatricule = $_SESSION['user']['matricule'] ?? null;

            // Récupérer les filtres depuis GET
            $filters = [
                'matricule' => $_GET['matricule'] ?? null,
                'machine_id' => $_GET['machine_id'] ?? null,
                'location' => $_GET['location'] ?? null,
                'status' => $_GET['status'] ?? null,
                'type' => $_GET['type'] ?? null
            ];

            // Appeler le modèle avec le matricule de l'utilisateur et les filtres
            // $machinesData = Machine_model::MachinesStateTable($userMatricule, $filters);

            // Vérifier si l'utilisateur est admin
            $isAdmin = isset($_SESSION['qualification']) && $_SESSION['qualification'] === 'ADMINISTRATEUR';

            // Inclure la classe d'export
            $exportPath = __DIR__ . '/../export/machineStatusExport.php';
            if (!file_exists($exportPath)) {
                throw new \Exception('Fichier d\'export non trouvé: ' . $exportPath);
            }
            require_once $exportPath;

            // Générer le fichier Excel
            call_user_func(['MachineStatusExport', 'generateExcelFile'], $machinesData, $isAdmin);
        } catch (\Exception $e) {
            error_log('Erreur dans export_machines_state: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Erreur lors de l\'export: ' . $e->getMessage()]);
            exit;
        }
    }

    public function history_machines_stateBYmachineID()
    {
        try {
            // $machines = Machine_model::MachinesStateTable();

            include(__DIR__ . '/../views/G_machines/G_machines_status/history_machineStatus.php');
        } catch (\Exception $e) {
            error_log('Erreur dans history_machines_stateBYmachineID: ' . $e->getMessage());
            $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Erreur lors du chargement de l\'historique des machines.'];
            include(__DIR__ . '/../views/G_machines/G_machines_status/history_machineStatus.php');
        }
    }


    /**
     * API pour récupérer les statistiques d'interventions d'une machine spécifique
     */
    public function getInterventionStats()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        header('Content-Type: application/json');

        $id_machine = $_GET['id_machine'] ?? null;
        if (!$id_machine) {
            http_response_code(400);
            echo json_encode(['error' => 'ID de machine non spécifié']);
            exit;
        }

        // Filtres de date optionnels
        $start_date = $_GET['start_date'] ?? null;
        $end_date = $_GET['end_date'] ?? null;

        try {
            $db = Database::getInstance('db_digitex');
            $conn = $db->getConnection();

            $params = [$id_machine];
            $query = "SELECT 
                        i.date_intervention AS dateIntervention,
                        i.type AS typeIntervention, 
                        p.code_panne AS codePanne,
                        COUNT(i.id_intervention) AS nbInter
                     FROM 
                        gmao__intervention i
                     LEFT JOIN 
                        gmao__panne p ON i.id_panne = p.id_panne
                     WHERE 
                        i.id_machine = ?";

            // Ajouter les filtres de date si présents
            if ($start_date) {
                $query .= " AND i.date_intervention >= ?";
                $params[] = $start_date;
            }

            if ($end_date) {
                $query .= " AND i.date_intervention <= ?";
                $params[] = $end_date;
            }

            $query .= " GROUP BY i.type, p.code_panne
                     ORDER BY p.code_panne";

            $stmt = $conn->prepare($query);
            $stmt->execute($params);
            $stats = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Formater les données pour le graphique
            $formattedStats = array_map(function ($item) {
                return [
                    'codePanne' => $item['codePanne'] ?: 'Non spécifié',
                    'typeIntervention' => $item['typeIntervention'],
                    'dateIntervention' => $item['dateIntervention'],
                    'nbInter' => (int)$item['nbInter']
                ];
            }, $stats);

            echo json_encode($formattedStats);
            exit;
        } catch (\PDOException $e) {
            http_response_code(500);
            error_log('Erreur dans getInterventionStats: ' . $e->getMessage());
            echo json_encode(['error' => 'Erreur de base de données: ' . $e->getMessage()]);
            exit;
        } catch (\Exception $e) {
            http_response_code(500);
            error_log('Erreur dans getInterventionStats: ' . $e->getMessage());
            echo json_encode(['error' => 'Erreur interne: ' . $e->getMessage()]);
            exit;
        }
    }
}
