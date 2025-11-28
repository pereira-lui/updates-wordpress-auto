<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Models\ActivityLog;

/**
 * Controller de logs de atividade
 */
class LogController extends Controller {
    
    /**
     * Lista todos os logs
     */
    public function index() {
        $page = (int) ($_GET['page'] ?? 1);
        $perPage = 50;
        $offset = ($page - 1) * $perPage;
        
        // Filtros
        $action = $_GET['action'] ?? '';
        $entity = $_GET['entity'] ?? '';
        $dateFrom = $_GET['date_from'] ?? '';
        $dateTo = $_GET['date_to'] ?? '';
        
        // Monta a query
        $where = [];
        $params = [];
        
        if ($action) {
            $where[] = 'action = ?';
            $params[] = $action;
        }
        
        if ($entity) {
            $where[] = 'entity_type = ?';
            $params[] = $entity;
        }
        
        if ($dateFrom) {
            $where[] = 'created_at >= ?';
            $params[] = $dateFrom . ' 00:00:00';
        }
        
        if ($dateTo) {
            $where[] = 'created_at <= ?';
            $params[] = $dateTo . ' 23:59:59';
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        // Total de registros
        $total = Database::selectOne(
            "SELECT COUNT(*) as total FROM activity_logs {$whereClause}",
            $params
        )->total;
        
        // Busca os logs
        $logs = Database::select(
            "SELECT l.*, u.name as user_name 
             FROM activity_logs l 
             LEFT JOIN users u ON l.user_id = u.id 
             {$whereClause} 
             ORDER BY l.created_at DESC 
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );
        
        // Lista de ações únicas para filtro
        $actions = Database::select("SELECT DISTINCT action FROM activity_logs ORDER BY action");
        
        // Lista de entidades únicas para filtro
        $entities = Database::select("SELECT DISTINCT entity_type FROM activity_logs ORDER BY entity_type");
        
        $totalPages = ceil($total / $perPage);
        
        $this->view('admin/settings/logs', [
            'logs' => $logs,
            'actions' => $actions,
            'entities' => $entities,
            'filters' => [
                'action' => $action,
                'entity' => $entity,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
            'pagination' => [
                'current' => $page,
                'total' => $totalPages,
                'showing' => count($logs),
                'total_records' => $total,
            ],
        ]);
    }
}
