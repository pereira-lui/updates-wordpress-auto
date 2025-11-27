<?php
/**
 * Classe para gerenciar o banco de dados do servidor
 */

if (!defined('ABSPATH')) {
    exit;
}

class PUS_Database {

    /**
     * Cria as tabelas necessárias
     */
    public static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Tabela de plugins
        $table_plugins = $wpdb->prefix . 'pus_plugins';
        $sql_plugins = "CREATE TABLE $table_plugins (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            plugin_slug varchar(255) NOT NULL,
            plugin_name varchar(255) NOT NULL,
            plugin_version varchar(50) NOT NULL,
            plugin_author varchar(255) DEFAULT '',
            plugin_description text,
            plugin_url varchar(500) DEFAULT '',
            package_url varchar(500) DEFAULT '',
            tested_wp_version varchar(20) DEFAULT '',
            requires_wp_version varchar(20) DEFAULT '',
            requires_php varchar(20) DEFAULT '',
            changelog text,
            banner_url varchar(500) DEFAULT '',
            icon_url varchar(500) DEFAULT '',
            last_updated datetime DEFAULT CURRENT_TIMESTAMP,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            is_active tinyint(1) DEFAULT 1,
            PRIMARY KEY (id),
            UNIQUE KEY plugin_slug (plugin_slug)
        ) $charset_collate;";

        // Tabela de licenças/sites autorizados
        $table_licenses = $wpdb->prefix . 'pus_licenses';
        $sql_licenses = "CREATE TABLE $table_licenses (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            license_key varchar(255) NOT NULL,
            client_name varchar(255) NOT NULL,
            client_email varchar(255) DEFAULT '',
            site_url varchar(500) NOT NULL,
            is_active tinyint(1) DEFAULT 0,
            max_activations int DEFAULT 1,
            current_activations int DEFAULT 0,
            expires_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            last_check datetime DEFAULT NULL,
            notes text,
            plan_id varchar(100) DEFAULT '',
            payment_status varchar(50) DEFAULT 'pending',
            payment_id varchar(255) DEFAULT '',
            asaas_customer_id varchar(255) DEFAULT '',
            paid_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY license_key (license_key)
        ) $charset_collate;";

        // Tabela de logs de atualizações
        $table_logs = $wpdb->prefix . 'pus_update_logs';
        $sql_logs = "CREATE TABLE $table_logs (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            license_id bigint(20) NOT NULL,
            plugin_id bigint(20) NOT NULL,
            site_url varchar(500) NOT NULL,
            old_version varchar(50) DEFAULT '',
            new_version varchar(50) NOT NULL,
            status varchar(50) DEFAULT 'success',
            ip_address varchar(45) DEFAULT '',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY license_id (license_id),
            KEY plugin_id (plugin_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_plugins);
        dbDelta($sql_licenses);
        dbDelta($sql_logs);

        // Gera uma chave secreta para a API se não existir
        if (!get_option('pus_api_secret_key')) {
            update_option('pus_api_secret_key', wp_generate_password(64, false));
        }
    }

    /**
     * Retorna todos os plugins
     */
    public static function get_plugins($active_only = true) {
        global $wpdb;
        $table = $wpdb->prefix . 'pus_plugins';
        
        $where = $active_only ? "WHERE is_active = 1" : "";
        return $wpdb->get_results("SELECT * FROM $table $where ORDER BY plugin_name ASC");
    }

    /**
     * Retorna um plugin pelo slug
     */
    public static function get_plugin_by_slug($slug) {
        global $wpdb;
        $table = $wpdb->prefix . 'pus_plugins';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE plugin_slug = %s",
            $slug
        ));
    }

    /**
     * Adiciona ou atualiza um plugin
     */
    public static function save_plugin($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'pus_plugins';

        $existing = self::get_plugin_by_slug($data['plugin_slug']);

        if ($existing) {
            $data['last_updated'] = current_time('mysql');
            return $wpdb->update($table, $data, array('id' => $existing->id));
        } else {
            return $wpdb->insert($table, $data);
        }
    }

    /**
     * Remove um plugin
     */
    public static function delete_plugin($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'pus_plugins';
        
        return $wpdb->delete($table, array('id' => $id));
    }

    /**
     * Retorna todas as licenças
     */
    public static function get_licenses($active_only = false) {
        global $wpdb;
        $table = $wpdb->prefix . 'pus_licenses';
        
        $where = $active_only ? "WHERE is_active = 1" : "";
        return $wpdb->get_results("SELECT * FROM $table $where ORDER BY created_at DESC");
    }

    /**
     * Retorna uma licença pela chave
     */
    public static function get_license_by_key($key) {
        global $wpdb;
        $table = $wpdb->prefix . 'pus_licenses';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE license_key = %s",
            $key
        ));
    }

    /**
     * Valida uma licença
     */
    public static function validate_license($license_key, $site_url) {
        global $wpdb;
        $table = $wpdb->prefix . 'pus_licenses';

        $license = self::get_license_by_key($license_key);

        if (!$license) {
            return array('valid' => false, 'message' => 'Licença não encontrada');
        }

        if (!$license->is_active) {
            return array('valid' => false, 'message' => 'Licença desativada');
        }

        if ($license->expires_at && strtotime($license->expires_at) < time()) {
            return array('valid' => false, 'message' => 'Licença expirada');
        }

        // Atualiza último check
        $wpdb->update($table, 
            array('last_check' => current_time('mysql')),
            array('id' => $license->id)
        );

        return array('valid' => true, 'license' => $license);
    }

    /**
     * Adiciona ou atualiza uma licença
     */
    public static function save_license($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'pus_licenses';

        if (!empty($data['id'])) {
            $id = $data['id'];
            unset($data['id']);
            return $wpdb->update($table, $data, array('id' => $id));
        } else {
            if (empty($data['license_key'])) {
                $data['license_key'] = self::generate_license_key();
            }
            return $wpdb->insert($table, $data);
        }
    }

    /**
     * Gera uma chave de licença única
     */
    public static function generate_license_key() {
        return strtoupper(sprintf(
            '%s-%s-%s-%s',
            wp_generate_password(4, false),
            wp_generate_password(4, false),
            wp_generate_password(4, false),
            wp_generate_password(4, false)
        ));
    }

    /**
     * Remove uma licença
     */
    public static function delete_license($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'pus_licenses';
        
        return $wpdb->delete($table, array('id' => $id));
    }

    /**
     * Registra um log de atualização
     */
    public static function log_update($license_id, $plugin_id, $site_url, $old_version, $new_version, $status = 'success') {
        global $wpdb;
        $table = $wpdb->prefix . 'pus_update_logs';

        return $wpdb->insert($table, array(
            'license_id' => $license_id,
            'plugin_id' => $plugin_id,
            'site_url' => $site_url,
            'old_version' => $old_version,
            'new_version' => $new_version,
            'status' => $status,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? ''
        ));
    }

    /**
     * Retorna logs de atualizações
     */
    public static function get_logs($limit = 100) {
        global $wpdb;
        $logs_table = $wpdb->prefix . 'pus_update_logs';
        $plugins_table = $wpdb->prefix . 'pus_plugins';
        $licenses_table = $wpdb->prefix . 'pus_licenses';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT l.*, p.plugin_name, lic.client_name 
             FROM $logs_table l
             LEFT JOIN $plugins_table p ON l.plugin_id = p.id
             LEFT JOIN $licenses_table lic ON l.license_id = lic.id
             ORDER BY l.created_at DESC
             LIMIT %d",
            $limit
        ));
    }
}
