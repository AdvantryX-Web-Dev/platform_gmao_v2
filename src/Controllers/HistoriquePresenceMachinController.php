<?php

namespace App\Controllers;

use App\Models\PresenceMachine;

class HistoriquePresenceMachinController
{
    private const DEFAULT_PER_PAGE = 10;
    private const MAX_PER_PAGE = 100;

    /**
     * Affiche l'historique de présence des machines avec filtres et pagination.
     */
    public function index(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = (int)($_GET['per_page'] ?? self::DEFAULT_PER_PAGE);
        $perPage = max(10, min(self::MAX_PER_PAGE, $perPage));

        $machineId = trim($_GET['machine_id'] ?? '');
        //   $prodLine = trim($_GET['prod_line'] ?? '');
        $stateFilter = trim($_GET['state'] ?? '');
        $stateMap = [
            'active' => 1,
            'inactive' => 0,
        ];
        $stateValue = null;
        if ($stateFilter !== '' && array_key_exists($stateFilter, $stateMap)) {
            $stateValue = $stateMap[$stateFilter];
        }

        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;

        if (empty($startDate) && empty($endDate)) {
            $endDate = date('Y-m-d');
            $startDate = date('Y-m-d', strtotime('-1 month', strtotime($endDate)));
        }

        if (!empty($startDate) && !empty($endDate) && strtotime($startDate) > strtotime($endDate)) {
            $_SESSION['flash_message'] = [
                'type' => 'error',
                'text' => 'La date de début ne peut pas être postérieure à la date de fin.',
            ];
            $tmp = $startDate;
            $startDate = $endDate;
            $endDate = $tmp;
        }

        $filters = [
            'machine_id' => $machineId,
            //  'prod_line' => $prodLine,
            'state' => $stateValue,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];

        $history = PresenceMachine::getHistory($filters, $perPage, $page);

        $stateOptions = [
            '' => 'Tous les états',
            'active' => 'Active',
            'inactive' => 'Inactive',
        ];

        $activeFilters = [
            'machine_id' => $machineId,
            // 'prod_line' => $prodLine,
            'state' => $stateFilter,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'per_page' => $perPage,
        ];

        include __DIR__ . '/../views/G_machines/G_machines_status/historiquePresenceMachine.php';
    }
}
