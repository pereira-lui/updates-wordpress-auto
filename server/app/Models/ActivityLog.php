<?php

namespace App\Models;

use App\Core\Database;

/**
 * Model de Log de Atividades
 */
class ActivityLog {
    
    const TYPE_LOGIN = 'login';
    const TYPE_LICENSE_CHECK = 'license_check';
    const TYPE_DOWNLOAD = 'download';
    const TYPE_PAYMENT = 'payment';
    const TYPE_WEBHOOK = 'webhook';
    const TYPE_ADMIN = 'admin';
    
    public static function all($filters = [], $limit = 100) {
        $sql = "SELECT * FROM activity_logs WHERE 1=1";
        $params = [];
        
        if (!empty($filters['type'])) {
            $sql .= " AND type = ?";
            $params[] = $filters['type'];
        }
        
        if (!empty($filters['license_id'])) {
            $sql .= " AND license_id = ?";
            $params[] = $filters['license_id'];
        }
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(created_at) >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(created_at) <= ?";
            $params[] = $filters['date_to'];
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT ?";
        $params[] = $limit;
        
        return Database::select($sql, $params);
    }
    
    public static function log($type, $message, $data = []) {
        return Database::insert('activity_logs', [
            'type' => $type,
            'message' => $message,
            'data' => json_encode($data),
            'license_id' => $data['license_id'] ?? null,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    public static function licenseCheck($licenseId, $siteUrl, $success) {
        return self::log(self::TYPE_LICENSE_CHECK, 
            $success ? 'Verificação de licença bem sucedida' : 'Verificação de licença falhou',
            [
                'license_id' => $licenseId,
                'site_url' => $siteUrl,
                'success' => $success
            ]
        );
    }
    
    public static function download($licenseId, $pluginSlug) {
        return self::log(self::TYPE_DOWNLOAD,
            "Download do plugin: {$pluginSlug}",
            [
                'license_id' => $licenseId,
                'plugin_slug' => $pluginSlug
            ]
        );
    }
    
    public static function payment($licenseId, $amount, $status) {
        return self::log(self::TYPE_PAYMENT,
            "Pagamento {$status}: R$ " . number_format($amount, 2, ',', '.'),
            [
                'license_id' => $licenseId,
                'amount' => $amount,
                'status' => $status
            ]
        );
    }
    
    public static function admin($action, $details = []) {
        $user = auth();
        return self::log(self::TYPE_ADMIN,
            $action,
            array_merge($details, [
                'user_id' => $user ? $user->id : null,
                'user_name' => $user ? $user->name : 'Sistema'
            ])
        );
    }
    
    public static function cleanup($days = 90) {
        return Database::query(
            "DELETE FROM activity_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)",
            [$days]
        );
    }
    
    public static function stats($days = 30) {
        return Database::select(
            "SELECT type, COUNT(*) as total
             FROM activity_logs
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
             GROUP BY type",
            [$days]
        );
    }
}
