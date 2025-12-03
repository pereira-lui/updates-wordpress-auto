<?php

namespace App\Models;

use App\Core\Database;

/**
 * Model de Log de Atualizações
 */
class UpdateLog {
    
    const STATUS_STARTED = 'started';
    const STATUS_SUCCESS = 'success';
    const STATUS_ERROR = 'error';
    const STATUS_ROLLBACK = 'rollback';
    
    /**
     * Cria um novo registro de atualização
     */
    public static function create($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        return Database::insert('update_logs', $data);
    }
    
    /**
     * Atualiza um registro
     */
    public static function update($id, $data) {
        return Database::update('update_logs', $data, 'id = ?', [$id]);
    }
    
    /**
     * Busca por ID
     */
    public static function find($id) {
        return Database::selectOne("SELECT * FROM update_logs WHERE id = ?", [$id]);
    }
    
    /**
     * Busca o último registro de uma licença/plugin
     */
    public static function findLastByLicensePlugin($licenseId, $pluginSlug) {
        return Database::selectOne(
            "SELECT * FROM update_logs 
             WHERE license_id = ? AND plugin_slug = ? 
             ORDER BY created_at DESC LIMIT 1",
            [$licenseId, $pluginSlug]
        );
    }
    
    /**
     * Lista logs de uma licença
     */
    public static function getByLicense($licenseId, $limit = 20) {
        return Database::select(
            "SELECT ul.*, p.name as plugin_name 
             FROM update_logs ul
             LEFT JOIN plugins p ON ul.plugin_slug = p.slug
             WHERE ul.license_id = ? 
             ORDER BY ul.created_at DESC 
             LIMIT ?",
            [$licenseId, $limit]
        );
    }
    
    /**
     * Lista logs recentes com erros
     */
    public static function getRecentErrors($limit = 20) {
        return Database::select(
            "SELECT ul.*, l.client_name, l.client_email, l.site_url, p.name as plugin_name 
             FROM update_logs ul
             JOIN licenses l ON ul.license_id = l.id
             LEFT JOIN plugins p ON ul.plugin_slug = p.slug
             WHERE ul.status IN ('error', 'rollback')
             ORDER BY ul.created_at DESC 
             LIMIT ?",
            [$limit]
        );
    }
    
    /**
     * Lista logs recentes de sucesso
     */
    public static function getRecentSuccess($limit = 20) {
        return Database::select(
            "SELECT ul.*, l.client_name, l.client_email, l.site_url, p.name as plugin_name 
             FROM update_logs ul
             JOIN licenses l ON ul.license_id = l.id
             LEFT JOIN plugins p ON ul.plugin_slug = p.slug
             WHERE ul.status = 'success'
             ORDER BY ul.created_at DESC 
             LIMIT ?",
            [$limit]
        );
    }
    
    /**
     * Estatísticas gerais
     */
    public static function getStats($days = 30) {
        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        return Database::selectOne(
            "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as success,
                SUM(CASE WHEN status = 'error' THEN 1 ELSE 0 END) as errors,
                SUM(CASE WHEN status = 'rollback' THEN 1 ELSE 0 END) as rollbacks,
                SUM(CASE WHEN status = 'started' THEN 1 ELSE 0 END) as pending
             FROM update_logs 
             WHERE created_at >= ?",
            [$since]
        );
    }
    
    /**
     * Contagem por status
     */
    public static function countByStatus() {
        return Database::select(
            "SELECT status, COUNT(*) as total FROM update_logs GROUP BY status"
        );
    }
    
    /**
     * Licenças com problemas (último update com erro)
     */
    public static function getLicensesWithErrors() {
        return Database::select(
            "SELECT DISTINCT l.*, ul.status as update_status, ul.error_message, ul.created_at as error_at
             FROM licenses l
             JOIN update_logs ul ON l.id = ul.license_id
             WHERE ul.id = (
                 SELECT MAX(ul2.id) FROM update_logs ul2 WHERE ul2.license_id = l.id
             )
             AND ul.status IN ('error', 'rollback')
             ORDER BY ul.created_at DESC"
        );
    }
    
    /**
     * Licenças OK (último update com sucesso)
     */
    public static function getLicensesOk() {
        return Database::select(
            "SELECT DISTINCT l.*, ul.status as update_status, ul.created_at as success_at
             FROM licenses l
             JOIN update_logs ul ON l.id = ul.license_id
             WHERE ul.id = (
                 SELECT MAX(ul2.id) FROM update_logs ul2 WHERE ul2.license_id = l.id
             )
             AND ul.status = 'success'
             ORDER BY ul.created_at DESC"
        );
    }
}
