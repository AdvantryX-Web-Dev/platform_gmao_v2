<?php

namespace App\Export;

use App\Models\HistoriqueInventaire_model;

class EvaluationInventaireExport
{
    /**
     * Export vers Excel (format XLSX compatible)
     */
    public static function exportToExcel($filterMaintainer = '', $filterStatus = '', $filterDateFrom = '', $filterDateTo = '')
    {
        try {
            // Récupérer les données avec filtres
            $data = HistoriqueInventaire_model::Evaluation_inventaire($filterMaintainer, $filterStatus, $filterDateFrom, $filterDateTo);

            $filename = 'evaluation_inventaire_' . date('Y-m-d_H-i-s') . '.xlsx';

            // Utiliser CSV avec séparateur tabulation pour une meilleure compatibilité Excel
            header('Content-Type: application/vnd.ms-excel; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: max-age=0');

            $output = fopen('php://output', 'w');

            // Ajouter BOM UTF-8 pour Excel
            fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // En-têtes
            fputcsv($output, [
                'ID Machine',
                'Référence',
                'Type Machine',
                'Emplacement Actuel',
                'Statut Actuel',
                'Maintenancier Inventaire',
                'Maintenancier Actuel',
                'Évaluation',
                'Différences Détectées',
                'Date Inventaire'
            ], "\t");

            // Données
            foreach ($data as $row) {
                // Formater l'évaluation
                $evaluation = self::formatEvaluation($row['evaluation'] ?? '');

                // Formater les différences
                $differences = self::formatDifferences($row['difference'] ?? '');

                fputcsv($output, [
                    $row['machine_id'] ?? 'N/A',
                    $row['reference'] ?? 'N/A',
                    $row['type'] ?? 'N/A',
                    $row['current_location'] ?? 'Non défini',
                    $row['current_status'] ?? 'Non défini',
                    $row['inventory_maintainer'] ?? 'Non défini',
                    $row['current_maintainer'] ?? 'Non défini',
                    $evaluation,
                    $differences,
                    $row['inventory_date'] ?? 'N/A'
                ], "\t");
            }

            fclose($output);
            exit;
        } catch (\Exception $e) {
            error_log("Erreur export Excel: " . $e->getMessage());
            throw new \Exception("Erreur lors de l'export Excel");
        }
    }

    /**
     * Formate l'évaluation pour l'export
     */
    private static function formatEvaluation($evaluation)
    {
        switch ($evaluation) {
            case 'conforme':
                return 'Conforme';
            case 'non_conforme':
                return 'Non Conforme';
            case 'non_inventoriee':
                return 'Non Inventoriée';
            case 'ajouter':
                return 'Machine Ajoutée';
            default:
                return ucfirst($evaluation);
        }
    }

    /**
     * Formate les différences pour l'export
     */
    private static function formatDifferences($differences)
    {
        if (empty($differences) || $differences === 'non defini') {
            return 'Aucune différence';
        }

        // Remplacer les balises HTML par des retours à la ligne
        $formatted = str_replace(['<br>', '<br/>', '<br />'], "\n", $differences);

        // Nettoyer le HTML restant
        $formatted = strip_tags($formatted);

        return $formatted;
    }
}
