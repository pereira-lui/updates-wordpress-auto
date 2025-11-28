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
    
    // Períodos de assinatura
    const PERIOD_MONTHLY = 'monthly';
    const PERIOD_QUARTERLY = 'quarterly';
    const PERIOD_SEMIANNUAL = 'semiannual';
    const PERIOD_YEARLY = 'yearly';
    const PERIOD_LIFETIME = 'lifetime';
    
    /**
     * Retorna os labels dos períodos em português
     */
    public static function getPeriodLabels() {
        return [
            self::PERIOD_MONTHLY => 'Mensal',
            self::PERIOD_QUARTERLY => 'Trimestral',
            self::PERIOD_SEMIANNUAL => 'Semestral',
            self::PERIOD_YEARLY => 'Anual',
            self::PERIOD_LIFETIME => 'Vitalício'
        ];
    }
    
    /**
     * Retorna os dias de cada período
     */
    public static function getPeriodDays($period) {
        return match($period) {
            self::PERIOD_MONTHLY => 30,
            self::PERIOD_QUARTERLY => 90,
            self::PERIOD_SEMIANNUAL => 180,
            self::PERIOD_YEARLY => 365,
            self::PERIOD_LIFETIME => null,
            default => 30
        };
    }
    
    /**
     * Calcula data de expiração
     */
    public static function calculateExpiration($period) {
        $days = self::getPeriodDays($period);
        if ($days === null) {
            return null; // Vitalícia
        }
        return date('Y-m-d H:i:s', strtotime("+{$days} days"));
    }
    
    public static function all($filters = []) {
        $sql = "SELECT * FROM licenses WHERE 1=1";
        $params = [];
        
        if (!empty($filters['status'])) {
            $sql .= " AND status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['period'])) {
            $sql .= " AND period = ?";
            $params[] = $filters['period'];
        }
        
        if (!empty($filters['search'])) {
            $sql .= " AND (client_name LIKE ? OR client_email LIKE ? OR license_key LIKE ? OR site_url LIKE ?)";
            $search = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$search, $search, $search, $search]);
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        return Database::select($sql, $params);
    }
    
    public static function find($id) {
        return Database::selectOne("SELECT * FROM licenses WHERE id = ?", [$id]);
    }
    
    public static function findByKey($key) {
        return Database::selectOne("SELECT * FROM licenses WHERE license_key = ?", [$key]);
    }
    
    public static function findByEmail($email) {
        return Database::selectOne("SELECT * FROM licenses WHERE client_email = ? ORDER BY created_at DESC LIMIT 1", [$email]);
    }
    
    public static function findBySite($siteUrl) {
        $siteUrl = rtrim($siteUrl, '/');
        return Database::selectOne("SELECT * FROM licenses WHERE site_url = ? AND status = 'active'", [$siteUrl]);
    }
    
    public static function create($data) {
        if (empty($data['license_key'])) {
            $data['license_key'] = generate_license_key();
        }
        
        // Calcula expiração baseada no período
        if (!isset($data['expires_at']) && isset($data['period'])) {
            $data['expires_at'] = self::calculateExpiration($data['period']);
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
    
    /**
     * Renova uma licença por mais um período
     */
    public static function renew($id) {
        $license = self::find($id);
        if (!$license) return false;
        
        $baseDate = ($license->expires_at && strtotime($license->expires_at) > time()) 
            ? $license->expires_at 
            : date('Y-m-d H:i:s');
        
        $days = self::getPeriodDays($license->period);
        if ($days === null) {
            return true; // Vitalícia não precisa renovar
        }
        
        $newExpiration = date('Y-m-d H:i:s', strtotime($baseDate . " +{$days} days"));
        
        return self::update($id, [
            'expires_at' => $newExpiration,
            'status' => self::STATUS_ACTIVE
        ]);
    }
    
    /**
     * Valida uma licença
     */
    public static function validate($licenseKey, $siteUrl = null) {
        $license = self::findByKey($licenseKey);
        
        if (!$license) {
            return ['valid' => false, 'message' => 'Licença não encontrada'];
        }
        
        if ($license->status !== self::STATUS_ACTIVE) {
            return ['valid' => false, 'message' => 'Licença não está ativa'];
        }
        
        // Verifica expiração (exceto lifetime)
        if ($license->period !== self::PERIOD_LIFETIME) {
            if ($license->expires_at && strtotime($license->expires_at) < time()) {
                // Atualiza status para expirada
                self::update($license->id, ['status' => self::STATUS_EXPIRED]);
                return ['valid' => false, 'message' => 'Licença expirada'];
            }
        }
        
        // Atualiza site_url se informado e ainda não tem
        $updateData = [
            'last_check_at' => date('Y-m-d H:i:s'),
            'last_check_ip' => $_SERVER['REMOTE_ADDR'] ?? ''
        ];
        
        if ($siteUrl && empty($license->site_url)) {
            $updateData['site_url'] = rtrim($siteUrl, '/');
        }
        
        self::update($license->id, $updateData);
        
        return [
            'valid' => true, 
            'license' => $license,
            'expires_at' => $license->expires_at,
            'period' => $license->period
        ];
    }
    
    public static function countByStatus() {
        return Database::select("SELECT status, COUNT(*) as total FROM licenses GROUP BY status");
    }
    
    public static function countByPeriod() {
        return Database::select("SELECT period, COUNT(*) as total FROM licenses GROUP BY period");
    }
    
    public static function count() {
        $result = Database::selectOne("SELECT COUNT(*) as total FROM licenses");
        return $result ? $result->total : 0;
    }
    
    public static function countActive() {
        $result = Database::selectOne("SELECT COUNT(*) as total FROM licenses WHERE status = 'active'");
        return $result ? $result->total : 0;
    }
    
    public static function recentActivity($limit = 5) {
        return Database::select(
            "SELECT * FROM licenses 
             WHERE last_check_at IS NOT NULL 
             ORDER BY last_check_at DESC 
             LIMIT ?",
            [$limit]
        );
    }
}
