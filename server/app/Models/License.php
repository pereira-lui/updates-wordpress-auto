<?php

namespace App\Models;

use App\Core\Database;

/**
 * Model de Licenças
 */
class License {
    
    const STATUS_PENDING = 'pending';
    const STATUS_ACTIVE = 'active';
    const STATUS_EXPIRED = 'expired';
    const STATUS_CANCELLED = 'cancelled';
    
    const TYPE_PAID = 'paid';
    const TYPE_LIFETIME = 'lifetime';
    const TYPE_FRIEND = 'friend';
    const TYPE_TRIAL = 'trial';
    
    public static function all($filters = []) {
        $sql = "SELECT l.*, p.name as plan_name 
                FROM licenses l 
                LEFT JOIN plans p ON l.plan_id = p.id 
                WHERE 1=1";
        $params = [];
        
        if (!empty($filters['status'])) {
            $sql .= " AND l.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['type'])) {
            $sql .= " AND l.type = ?";
            $params[] = $filters['type'];
        }
        
        if (!empty($filters['search'])) {
            $sql .= " AND (l.client_name LIKE ? OR l.client_email LIKE ? OR l.license_key LIKE ? OR l.site_url LIKE ?)";
            $search = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$search, $search, $search, $search]);
        }
        
        $sql .= " ORDER BY l.created_at DESC";
        
        return Database::select($sql, $params);
    }
    
    public static function find($id) {
        return Database::selectOne(
            "SELECT l.*, p.name as plan_name 
             FROM licenses l 
             LEFT JOIN plans p ON l.plan_id = p.id 
             WHERE l.id = ?",
            [$id]
        );
    }
    
    public static function findByKey($key) {
        return Database::selectOne(
            "SELECT * FROM licenses WHERE license_key = ?",
            [$key]
        );
    }
    
    public static function create($data) {
        if (empty($data['license_key'])) {
            $data['license_key'] = generate_license_key();
        }
        $data['created_at'] = date('Y-m-d H:i:s');
        
        return Database::insert('licenses', $data);
    }
    
    public static function update($id, $data) {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return Database::update('licenses', $data, 'id = ?', [$id]);
    }
    
    public static function delete($id) {
        return Database::delete('licenses', 'id = ?', [$id]);
    }
    
    public static function validate($licenseKey, $siteUrl) {
        $license = self::findByKey($licenseKey);
        
        if (!$license) {
            return ['valid' => false, 'message' => 'Licença não encontrada'];
        }
        
        if ($license->status !== self::STATUS_ACTIVE) {
            return ['valid' => false, 'message' => 'Licença não está ativa'];
        }
        
        // Verifica expiração (exceto lifetime e friend)
        if (!in_array($license->type, [self::TYPE_LIFETIME, self::TYPE_FRIEND])) {
            if ($license->expires_at && strtotime($license->expires_at) < time()) {
                return ['valid' => false, 'message' => 'Licença expirada'];
            }
        }
        
        // Atualiza último check
        self::update($license->id, [
            'last_check_at' => date('Y-m-d H:i:s'),
            'last_check_ip' => $_SERVER['REMOTE_ADDR'] ?? ''
        ]);
        
        return ['valid' => true, 'license' => $license];
    }
    
    public static function countByStatus() {
        return Database::select(
            "SELECT status, COUNT(*) as total FROM licenses GROUP BY status"
        );
    }
    
    public static function countByType() {
        return Database::select(
            "SELECT type, COUNT(*) as total FROM licenses GROUP BY type"
        );
    }
    
    public static function recentActivity($limit = 10) {
        return Database::select(
            "SELECT * FROM licenses ORDER BY last_check_at DESC LIMIT ?",
            [$limit]
        );
    }
}
