<?php

/**
 * Classe pour l'export des données des machines en Excel
 */
class MachineStatusExport
{
    /**
     * Génère un fichier Excel avec les données des machines
     * 
     * @param array $machinesData Données des machines
     * @param bool $isAdmin Si l'utilisateur est admin
     * @return void
     */
    public static function generateExcelFile($machinesData, $isAdmin)
    {
        try {
            // Nettoyage buffer pour éviter toute sortie avant header
            if (ob_get_length()) ob_end_clean();
            // Vérifier si PhpSpreadsheet est disponible
            if (!class_exists('PhpOffice\\PhpSpreadsheet\\Spreadsheet')) {
                throw new \Exception('PhpSpreadsheet n\'est pas disponible');
            }

            // Créer un nouveau spreadsheet
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Définir le titre de la feuille
            $sheet->setTitle('Etat des machines');

            // En-têtes du fichier
            $headers = [
                'A1' => 'Machine ID',
                'B1' => 'Mainteneur',
                'C1' => 'Désignation',
                'D1' => 'Catégorie',
                'E1' => 'Emplacement',
                'E1' => 'État',
                'G1' => 'Date dernière action'
            ];

            // Écrire les en-têtes
            foreach ($headers as $cell => $value) {
                $sheet->setCellValue($cell, $value);
            }

            // Style des en-têtes
            $headerStyle = [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF']
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4']
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                ]
            ];

            $sheet->getStyle('A1:G1')->applyFromArray($headerStyle);

            // Données des machines
            $row = 2;
            if (is_array($machinesData) && count($machinesData) > 0) {
                foreach ($machinesData as $machine) {
                    $status = $machine['status_name_final'] ?? $machine['etat_machine'];

                    $sheet->setCellValue('A' . $row, $machine['machine_id'] ?? 'Non défini');
                    $sheet->setCellValue('B' . $row, isset($machine['maintener_id']) ?
                        ($machine['maintener_matricule'] . ' - ' . $machine['maintener_name']) :
                        'Non défini');
                    $sheet->setCellValue('C' . $row, $machine['designation'] ?? 'Non défini');
                    $sheet->setCellValue('D' . $row, $machine['category'] ?? 'Non défini');
                    $sheet->setCellValue('E' . $row, $machine['location'] ?? 'Non défini');
                    $sheet->setCellValue('F' . $row, $status ?? 'Non défini');
                    $sheet->setCellValue('G' . $row, $status == 'inactive' ?
                        ($machine['cur_date_time'] ?? 'Non défini') : 'Non défini');

                    $row++;
                }
            }

            // Ajuster la largeur des colonnes
            $sheet->getColumnDimension('A')->setWidth(15);
            $sheet->getColumnDimension('B')->setWidth(25);
            $sheet->getColumnDimension('C')->setWidth(25);
            $sheet->getColumnDimension('D')->setWidth(20);
            $sheet->getColumnDimension('E')->setWidth(20);
            $sheet->getColumnDimension('F')->setWidth(20);
            $sheet->getColumnDimension('G')->setWidth(25);

            // Style des bordures pour toutes les cellules
            $borderStyle = [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['rgb' => '000000']
                    ]
                ]
            ];

            $lastRow = $row - 1;
            if ($lastRow > 0) {
                $sheet->getStyle('A1:F' . $lastRow)->applyFromArray($borderStyle);
            }

            // Nom du fichier avec timestamp
            $filename = 'machines_etat_' . date('Ymd') . '.xlsx';

            // Headers pour le téléchargement
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Expires: 0');

            // Créer le writer Excel
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');

            exit;
        } catch (\Exception $e) {
            // En cas d'erreur, fallback vers CSV
            error_log('Erreur génération Excel: ' . $e->getMessage());
            self::generateCSVFile($machinesData, $isAdmin);
        }
    }

    /**
     * Génère un fichier CSV en fallback
     * 
     * @param array $machinesData Données des machines
     * @param bool $isAdmin Si l'utilisateur est admin
     * @return void
     */
    public static function generateCSVFile($machinesData, $isAdmin)
    {
        // Nettoyage buffer pour éviter toute sortie avant header
        if (ob_get_length()) ob_end_clean();
        // Nom du fichier avec timestamp
        $filename = 'machines_etat_' . date('Ymd') . '.csv';

        // Headers pour le téléchargement
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Expires: 0');

        // Créer le fichier CSV
        $output = fopen('php://output', 'w');

        // Ajouter BOM pour UTF-8 (pour Excel)
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // En-têtes du fichier
        $headers = [
            'Machine ID',
            'Mainteneur',
            'Désignation',
            'Emplacement',
            'État',
            'Date dernière action'
        ];

        fputcsv($output, $headers, ';');

        // Données des machines
        if (is_array($machinesData) && count($machinesData) > 0) {
            foreach ($machinesData as $machine) {
                $status = $machine['status_name_final'] ?? $machine['etat_machine'];

                $row = [
                    $machine['machine_id'] ?? 'Non défini',
                    isset($machine['maintener_id']) ?
                        ($machine['maintener_matricule'] . ' - ' . $machine['maintener_name']) :
                        'Non défini',
                    $machine['designation'] ?? 'Non défini',
                    $machine['location'] ?? 'Non défini',
                    $status ?? 'Non défini',
                    $status == 'inactive' ?  ($machine['cur_date_time'] ?? 'Non défini') : 'Non défini'
                ];

                fputcsv($output, $row, ';');
            }
        }

        fclose($output);
        exit;
    }
}
