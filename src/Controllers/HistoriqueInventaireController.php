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

            // Récupérer l'identifiant du maintenancier connecté
            $connectedMaintainerId = $_SESSION['user']['id'] ?? null;

            // Pour un utilisateur non administrateur, forcer le filtre sur son propre identifiant
            if (!$isAdmin && $connectedMaintainerId) {
                if ($filterMaintainer === '' || (int)$filterMaintainer !== (int)$connectedMaintainerId) {
                    $filterMaintainer = (string)$connectedMaintainerId;
                }
            }

            $export = $_GET['export'] ?? '';



            // Gérer l'export
            if ($export) {
                $this->handleExport($export, $filterMaintainer, $filterStatus);
                return;
            }

            // Récupérer les données d'évaluation d'inventaire avec filtres
            $HistoriqueInventaire = HistoriqueInventaire_model::Evaluation_inventaire($filterMaintainer, $filterStatus);

            // Récupérer tous les maintenanciers pour les filtres (si admin)
            $allMaintainers = [];
            if ($isAdmin) {
                $allMaintainers = Maintainer_model::findAll();
            }

            // Calculer les statistiques
            $totalMachines = count($HistoriqueInventaire);
            $confirmes = 0;
            $nonConformes = 0;
            $inventoriees = 0;
            $nonInventoriees = 0;

            foreach ($HistoriqueInventaire as $comp) {
                switch ($comp['evaluation'] ?? '') {
                    case 'conforme':
                        $confirmes++;
                        $inventoriees++;
                        break;
                    case 'non_conforme':
                        if (!empty($comp['differences']) && strpos($comp['differences'], 'Machine non inventoriée') !== false) {
                            $nonInventoriees++;
                        } else {
                            $nonConformes++;
                            $inventoriees++;
                        }
                        break;
                }
            }
            // Calculer les pourcentages
            $tauxConformite = $totalMachines > 0 ? round(($confirmes / $totalMachines) * 100, 1) : 0;
            $tauxNonConforme = $totalMachines > 0 ? round(($nonConformes / $totalMachines) * 100, 1) : 0;
            $tauxCouverture = $totalMachines > 0 ? round(($inventoriees / $totalMachines) * 100, 1) : 0;
            $nonInventoriees = $nonInventoriees > 0 ? $nonInventoriees : max(0, $totalMachines - $inventoriees);

            // Construire les paramètres d'export
            $exportParams = '';
            if ($filterMaintainer) $exportParams .= '&filter_maintainer=' . urlencode($filterMaintainer);
            if ($filterStatus) $exportParams .= '&filter_status=' . urlencode($filterStatus);

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
    private function handleExport($format, $filterMaintainer, $filterStatus)
    {
        try {
            if ($format === 'excel') {
                \App\Export\EvaluationInventaireExport::exportToExcel($filterMaintainer, $filterStatus);
            }
        } catch (\Exception $e) {
            error_log("Erreur export: " . $e->getMessage());
            $_SESSION['flash_error'] = 'Erreur lors de l\'export: ' . $e->getMessage();
            header('Location: index.php?route=historyInventaire');
        }
    }
}
