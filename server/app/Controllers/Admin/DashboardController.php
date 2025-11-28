<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\License;
use App\Models\Plugin;
use App\Models\Plan;
use App\Models\Payment;
use App\Models\ActivityLog;

/**
 * Controller do Dashboard
 */
class DashboardController extends Controller {
    
    /**
     * Página principal do dashboard
     */
    public function index() {
        // Estatísticas gerais
        $stats = [
            'licenses' => [
                'total' => count(License::all()),
                'active' => count(License::all(['status' => 'active'])),
                'by_status' => License::countByStatus(),
                'by_type' => License::countByType()
            ],
            'plugins' => [
                'total' => Plugin::count()
            ],
            'plans' => [
                'total' => Plan::count()
            ],
            'payments' => [
                'total' => Payment::count(),
                'revenue' => Payment::sumByStatus(),
                'by_status' => Payment::countByStatus()
            ]
        ];
        
        // Receita mensal (últimos 12 meses)
        $revenueByMonth = Payment::revenueByMonth(12);
        
        // Atividade recente
        $recentPayments = Payment::recent(5);
        $recentActivity = License::recentActivity(5);
        
        // Logs recentes
        $logs = ActivityLog::all([], 10);
        
        return $this->view('admin/dashboard', [
            'stats' => $stats,
            'revenueByMonth' => $revenueByMonth,
            'recentPayments' => $recentPayments,
            'recentActivity' => $recentActivity,
            'logs' => $logs
        ]);
    }
}
