<?php

namespace App\Models;

use PDO;
use PDOException;

class PresenceMachine
{
    /**
     * Liste paginée de l'historique de présence.
     *
     * @param array $filters
     * @param int $perPage
     * @param int $page
     * @return array{data: array<int, array<string, mixed>>, total: int, page: int, per_page: int, total_pages: int}
     */
    public static function getHistory(array $filters, int $perPage = 25, int $page = 1): array
    {
        $db = Database::getInstance('db_digitex');
        $conn = $db->getConnection();

        $conditions = [];
        $params = [];

        if (!empty($filters['machine_id'])) {
            $conditions[] = 'p.machine_id = :machine_id';
            $params[':machine_id'] = $filters['machine_id'];
        }
        if ($filters['state'] !== null && $filters['state'] !== '') {
            $conditions[] = 'p.p_state = :state';
            $params[':state'] = $filters['state'];
        }
        if (!empty($filters['start_date'])) {
            $conditions[] = 'p.cur_date >= :start_date';
            $params[':start_date'] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $conditions[] = 'p.cur_date <= :end_date';
            $params[':end_date'] = $filters['end_date'];
        }
        if (!empty($filters['prod_line'])) {
            $conditions[] = 'p.prod_line = :prod_line';
            $params[':prod_line'] = $filters['prod_line'];
        }

        $whereClause = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
        $countSql = "SELECT COUNT(*) FROM prod__presence p {$whereClause}";
        $countStmt = $conn->prepare($countSql);
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $offset = max(0, ($page - 1) * $perPage);
        $dataSql = "
            SELECT 
                p.id,
                p.operator,
                p.prod_line,
                p.machine_id,
                p.smartbox,
                p.p_state,
                p.cur_date,
                p.cur_time,
                emp.first_name AS operator_first_name,
                emp.last_name AS operator_last_name,
                emp.matricule AS operator_matricule
            FROM prod__presence p
            LEFT JOIN init__employee emp ON emp.matricule = p.operator
            {$whereClause}
            ORDER BY cur_date DESC, cur_time DESC
            LIMIT :limit OFFSET :offset
        ";
        $stmt = $conn->prepare($dataSql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $totalPages = (int)ceil($total / $perPage);

        return [
            'data' => $rows,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => $totalPages,
        ];
    }
}
