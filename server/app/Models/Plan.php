<?php

namespace App\Models;

use App\Core\Database;

/**
 * Model de Planos
 */
class Plan {
    
    const PERIOD_MONTHLY = 'monthly';
    const PERIOD_YEARLY = 'yearly';
    const PERIOD_LIFETIME = 'lifetime';
    
    public static function all($activeOnly = false) {
        $sql = "SELECT * FROM plans";
        if ($activeOnly) {
            $sql .= " WHERE is_active = 1";
        }
        $sql .= " ORDER BY sort_order ASC, price ASC";
        
        return Database::select($sql);
    }
    
    public static function find($id) {
        return Database::selectOne("SELECT * FROM plans WHERE id = ?", [$id]);
    }
    
    public static function findBySlug($slug) {
        return Database::selectOne("SELECT * FROM plans WHERE slug = ?", [$slug]);
    }
    
    public static function create($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        if (empty($data['slug'])) {
            $data['slug'] = slugify($data['name']);
        }
        return Database::insert('plans', $data);
    }
    
    public static function update($id, $data) {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return Database::update('plans', $data, 'id = ?', [$id]);
    }
    
    public static function delete($id) {
        // Verifica se há licenças usando este plano
        $count = Database::selectOne(
            "SELECT COUNT(*) as total FROM licenses WHERE plan_id = ?",
            [$id]
        );
        
        if ($count && $count->total > 0) {
            return false;
        }
        
        return Database::delete('plans', 'id = ?', [$id]);
    }
    
    public static function getPlugins($planId) {
        return Database::select(
            "SELECT p.* FROM plugins p
             INNER JOIN plan_plugins pp ON p.id = pp.plugin_id
             WHERE pp.plan_id = ?",
            [$planId]
        );
    }
    
    public static function syncPlugins($planId, $pluginIds) {
        // Remove associações existentes
        Database::delete('plan_plugins', 'plan_id = ?', [$planId]);
        
        // Adiciona novas
        foreach ($pluginIds as $pluginId) {
            Database::insert('plan_plugins', [
                'plan_id' => $planId,
                'plugin_id' => $pluginId
            ]);
        }
        
        return true;
    }
    
    public static function calculateExpiration($period) {
        switch ($period) {
            case self::PERIOD_MONTHLY:
                return date('Y-m-d H:i:s', strtotime('+1 month'));
            case self::PERIOD_YEARLY:
                return date('Y-m-d H:i:s', strtotime('+1 year'));
            case self::PERIOD_LIFETIME:
                return null;
            default:
                return date('Y-m-d H:i:s', strtotime('+1 month'));
        }
    }
    
    public static function count() {
        $result = Database::selectOne("SELECT COUNT(*) as total FROM plans");
        return $result ? $result->total : 0;
    }
}
