<?php

namespace App\Models;

use App\Core\Database;

/**
 * Model de Pagamentos
 */
class Payment {
    
    const STATUS_PENDING = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_RECEIVED = 'received';
    const STATUS_OVERDUE = 'overdue';
    const STATUS_REFUNDED = 'refunded';
    const STATUS_CANCELLED = 'cancelled';
    
    const METHOD_PIX = 'pix';
    const METHOD_BOLETO = 'boleto';
    const METHOD_CREDIT_CARD = 'credit_card';
    
    // Status da nota fiscal
    const INVOICE_PENDING = 'pending';
    const INVOICE_ISSUED = 'issued';
    const INVOICE_ERROR = 'error';
    const INVOICE_CANCELLED = 'cancelled';
    
    public static function all($filters = []) {
        $sql = "SELECT p.*, l.client_name, l.client_email, l.license_key, l.period
                FROM payments p
                LEFT JOIN licenses l ON p.license_id = l.id
                WHERE 1=1";
        $params = [];
        
        if (!empty($filters['status'])) {
            $sql .= " AND p.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['method'])) {
            $sql .= " AND p.payment_method = ?";
            $params[] = $filters['method'];
        }
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(p.created_at) >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(p.created_at) <= ?";
            $params[] = $filters['date_to'];
        }
        
        if (!empty($filters['search'])) {
            $sql .= " AND (l.client_name LIKE ? OR l.client_email LIKE ? OR p.asaas_id LIKE ?)";
            $search = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$search, $search, $search]);
        }
        
        $sql .= " ORDER BY p.created_at DESC";
        
        return Database::select($sql, $params);
    }
    
    public static function find($id) {
        return Database::selectOne(
            "SELECT p.*, l.client_name, l.client_email, l.license_key, l.period
             FROM payments p
             LEFT JOIN licenses l ON p.license_id = l.id
             WHERE p.id = ?",
            [$id]
        );
    }
    
    public static function findByAsaasId($asaasId) {
        return Database::selectOne(
            "SELECT * FROM payments WHERE asaas_id = ?",
            [$asaasId]
        );
    }
    
    public static function findByLicense($licenseId) {
        return Database::select(
            "SELECT * FROM payments WHERE license_id = ? ORDER BY created_at DESC",
            [$licenseId]
        );
    }
    
    public static function countByLicense($licenseId, $status = null) {
        if ($status) {
            $result = Database::selectOne(
                "SELECT COUNT(*) as total FROM payments WHERE license_id = ? AND status = ?",
                [$licenseId, $status]
            );
        } else {
            $result = Database::selectOne(
                "SELECT COUNT(*) as total FROM payments WHERE license_id = ?",
                [$licenseId]
            );
        }
        return $result ? $result->total : 0;
    }
    
    public static function lastByLicense($licenseId, $status = null) {
        if ($status) {
            return Database::selectOne(
                "SELECT * FROM payments WHERE license_id = ? AND status = ? ORDER BY created_at DESC LIMIT 1",
                [$licenseId, $status]
            );
        }
        return Database::selectOne(
            "SELECT * FROM payments WHERE license_id = ? ORDER BY created_at DESC LIMIT 1",
            [$licenseId]
        );
    }
    
    public static function create($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        return Database::insert('payments', $data);
    }
    
    public static function update($id, $data) {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return Database::update('payments', $data, 'id = ?', [$id]);
    }
    
    public static function updateByAsaasId($asaasId, $data) {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return Database::update('payments', $data, 'asaas_id = ?', [$asaasId]);
    }
    
    public static function delete($id) {
        return Database::delete('payments', 'id = ?', [$id]);
    }
    
    public static function sumByStatus($status = null) {
        if ($status) {
            $result = Database::selectOne(
                "SELECT SUM(amount) as total FROM payments WHERE status = ?",
                [$status]
            );
        } else {
            $result = Database::selectOne(
                "SELECT SUM(amount) as total FROM payments WHERE status IN ('confirmed', 'received')"
            );
        }
        return $result ? floatval($result->total) : 0;
    }
    
    public static function countByStatus() {
        return Database::select(
            "SELECT status, COUNT(*) as total, SUM(amount) as amount 
             FROM payments GROUP BY status"
        );
    }
    
    public static function revenueByMonth($months = 12) {
        return Database::select(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') as month,
                    SUM(amount) as total,
                    COUNT(*) as count
             FROM payments 
             WHERE status IN ('confirmed', 'received')
               AND created_at >= DATE_SUB(NOW(), INTERVAL ? MONTH)
             GROUP BY DATE_FORMAT(created_at, '%Y-%m')
             ORDER BY month ASC",
            [$months]
        );
    }
    
    public static function recent($limit = 10) {
        return Database::select(
            "SELECT p.*, l.client_name, l.client_email
             FROM payments p
             LEFT JOIN licenses l ON p.license_id = l.id
             ORDER BY p.created_at DESC
             LIMIT ?",
            [$limit]
        );
    }
    
    public static function count() {
        $result = Database::selectOne("SELECT COUNT(*) as total FROM payments");
        return $result ? $result->total : 0;
    }
    
    /**
     * Busca pagamentos que precisam de nota fiscal
     */
    public static function pendingInvoices() {
        return Database::select(
            "SELECT p.*, l.client_name, l.client_email, l.client_document
             FROM payments p
             LEFT JOIN licenses l ON p.license_id = l.id
             WHERE p.generate_invoice = 1 
               AND p.status IN ('confirmed', 'received')
               AND (p.invoice_status IS NULL OR p.invoice_status = 'pending')
             ORDER BY p.paid_at ASC"
        );
    }
    
    /**
     * Atualiza informações da nota fiscal
     */
    public static function updateInvoice($id, $invoiceNumber, $pdfUrl, $status = self::INVOICE_ISSUED) {
        return self::update($id, [
            'invoice_status' => $status,
            'invoice_number' => $invoiceNumber,
            'invoice_pdf_url' => $pdfUrl
        ]);
    }
    
    /**
     * Retorna labels de status da nota fiscal
     */
    public static function getInvoiceStatusLabel($status) {
        $labels = [
            self::INVOICE_PENDING => 'Pendente',
            self::INVOICE_ISSUED => 'Emitida',
            self::INVOICE_ERROR => 'Erro',
            self::INVOICE_CANCELLED => 'Cancelada'
        ];
        return $labels[$status] ?? $status;
    }
    
    /**
     * Conta pagamentos com nota fiscal
     */
    public static function countWithInvoice() {
        $result = Database::selectOne(
            "SELECT COUNT(*) as total FROM payments WHERE generate_invoice = 1"
        );
        return $result ? $result->total : 0;
    }
    
    /**
     * Conta notas fiscais por status
     */
    public static function countInvoicesByStatus() {
        return Database::select(
            "SELECT invoice_status, COUNT(*) as total 
             FROM payments 
             WHERE generate_invoice = 1 
             GROUP BY invoice_status"
        );
    }
}
