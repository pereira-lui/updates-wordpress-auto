<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\Payment;
use App\Models\ActivityLog;

/**
 * Controller de Pagamentos
 */
class PaymentController extends Controller {
    
    /**
     * Lista todos os pagamentos
     */
    public function index() {
        $filters = [
            'status' => $_GET['status'] ?? '',
            'method' => $_GET['method'] ?? '',
            'date_from' => $_GET['date_from'] ?? '',
            'date_to' => $_GET['date_to'] ?? '',
            'search' => $_GET['search'] ?? ''
        ];
        
        $payments = Payment::all($filters);
        $statusStats = Payment::countByStatus();
        
        return $this->view('admin/payments/index', [
            'payments' => $payments,
            'filters' => $filters,
            'statusStats' => $statusStats
        ]);
    }
    
    /**
     * Exibe detalhes do pagamento
     */
    public function show($id) {
        $payment = Payment::find($id);
        
        if (!$payment) {
            flash('error', 'Pagamento não encontrado');
            redirect('/admin/payments');
        }
        
        return $this->view('admin/payments/show', [
            'payment' => $payment
        ]);
    }
    
    /**
     * Relatório de pagamentos
     */
    public function report() {
        $period = $_GET['period'] ?? 12;
        
        $revenueByMonth = Payment::revenueByMonth($period);
        $statusStats = Payment::countByStatus();
        $totalRevenue = Payment::sumByStatus();
        
        return $this->view('admin/payments/report', [
            'revenueByMonth' => $revenueByMonth,
            'statusStats' => $statusStats,
            'totalRevenue' => $totalRevenue,
            'period' => $period
        ]);
    }
    
    /**
     * Exportar pagamentos (CSV)
     */
    public function export() {
        $filters = [
            'status' => $_GET['status'] ?? '',
            'date_from' => $_GET['date_from'] ?? '',
            'date_to' => $_GET['date_to'] ?? ''
        ];
        
        $payments = Payment::all($filters);
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=pagamentos-' . date('Y-m-d') . '.csv');
        
        $output = fopen('php://output', 'w');
        
        // BOM para UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Cabeçalho
        fputcsv($output, [
            'ID', 'Data', 'Cliente', 'Email', 'Plano', 'Valor', 
            'Método', 'Status', 'ID Asaas'
        ], ';');
        
        foreach ($payments as $payment) {
            fputcsv($output, [
                $payment->id,
                date('d/m/Y H:i', strtotime($payment->created_at)),
                $payment->client_name ?? '-',
                $payment->client_email ?? '-',
                $payment->plan_name ?? '-',
                number_format($payment->amount, 2, ',', '.'),
                $payment->payment_method,
                $payment->status,
                $payment->asaas_id ?? '-'
            ], ';');
        }
        
        fclose($output);
        exit;
    }
}
