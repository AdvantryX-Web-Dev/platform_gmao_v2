<?php

namespace App\Controllers;

use App\Models\HistoriqueInventaire_model;
use App\Models\Maintainer_model;

// Import manuel de la classe d'export
require_once __DIR__ . '/../export/evaluationInventairExport.php';

class HistoriqueInventaireController
{
    public function index()
    {
        try {
            // Vérifier si l'utilisateur est admin
            $isAdmin = isset($_SESSION['qualification']) && $_SESSION['qualification'] === 'ADMINISTRATEUR';

            // Récupérer le matricule de l'utilisateur connecté
            $connectedMatricule = $_SESSION['user']['matricule'] ?? null;

            // Récupérer les filtres
            $filterMaintainer = $_GET['filter_maintainer'] ?? '';
            $filterStatus = $_GET['filter_status'] ?? '';
            $filterDateFrom = $_GET['filter_date_from'] ?? '';
            $filterDateTo = $_GET['filter_date_to'] ?? '';
            $export = $_GET['export'] ?? '';

            // Définir les dates par défaut du dernier mois si aucun filtre de date n'est appliqué
            if (empty($filterDateFrom) && empty($filterDateTo)) {
                $filterDateFrom = date('Y-m-d', strtotime('-30 days'));
                $filterDateTo = date('Y-m-d'); // aujourd’hui
            }
            
            // Gérer l'export
            if ($export) {
                $this->handleExport($export, $filterMaintainer, $filterStatus, $filterDateFrom, $filterDateTo);
                return;
            }

            // Récupérer les données d'évaluation d'inventaire avec filtres
            $HistoriqueInventaire = HistoriqueInventaire_model::Evaluation_inventaire($filterMaintainer, $filterStatus, $filterDateFrom, $filterDateTo);

            // Récupérer tous les maintenanciers pour les filtres (si admin)
            $allMaintainers = [];
            if ($isAdmin) {
                $allMaintainers = Maintainer_model::findAll();
            }

            // Calculer les statistiques
            $totalMachines = count($HistoriqueInventaire);
            $confirmes = 0;
            $nonConformes = 0;
            $nonInventoriees = 0;

            foreach ($HistoriqueInventaire as $comp) {
                switch ($comp['evaluation'] ?? '') {
                    case 'conforme':
                        $confirmes++;
                        break;
                    case 'non_conforme':
                        $nonConformes++;
                        break;
                    case 'non_inventoriee':
                        $nonInventoriees++;
                        break;
                    case 'ajouter':
                        $nonConformes++;
                        break;
                }
            }

            // Calculer les pourcentages
            $tauxConformite = $totalMachines > 0 ? round(($confirmes / $totalMachines) * 100, 1) : 0;
            $tauxNonConforme = $totalMachines > 0 ? round(($nonConformes / $totalMachines) * 100, 1) : 0;
            $tauxCouverture = $totalMachines > 0 ? round((($confirmes + $nonConformes) / $totalMachines) * 100, 1) : 0;

            // Construire les paramètres d'export
            $exportParams = '';
            if ($filterMaintainer) $exportParams .= '&filter_maintainer=' . urlencode($filterMaintainer);
            if ($filterStatus) $exportParams .= '&filter_status=' . urlencode($filterStatus);
            if ($filterDateFrom) $exportParams .= '&filter_date_from=' . urlencode($filterDateFrom);
            if ($filterDateTo) $exportParams .= '&filter_date_to=' . urlencode($filterDateTo);

            include __DIR__ . '/../views/inventaire/historique_inventaire.php';
        } catch (\Exception $e) {
            error_log("Erreur dans HistoriqueInventaireController: " . $e->getMessage());
            $_SESSION['flash_error'] = 'Erreur lors du chargement de l\'historique d\'inventaire';
            header('Location: index.php?route=dashboard');
        }
    }

    /**
     * Gère l'export des données
     */
    private function handleExport($format, $filterMaintainer, $filterStatus, $filterDateFrom, $filterDateTo)
    {
        try {
            if ($format === 'excel') {
                \App\Export\EvaluationInventaireExport::exportToExcel($filterMaintainer, $filterStatus, $filterDateFrom, $filterDateTo);
            }
        } catch (\Exception $e) {
            error_log("Erreur export: " . $e->getMessage());
            $_SESSION['flash_error'] = 'Erreur lors de l\'export: ' . $e->getMessage();
            header('Location: index.php?route=historyInventaire');
        }
    }
}
