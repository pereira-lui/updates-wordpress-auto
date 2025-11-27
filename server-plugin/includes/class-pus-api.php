<?php
/**
 * Classe para gerenciar a API REST do servidor
 */

if (!defined('ABSPATH')) {
    exit;
}

class PUS_API {

    /**
     * Registra as rotas da API
     */
    public static function register_routes() {
        $namespace = 'premium-updates/v1';

        // Verifica atualizações disponíveis
        register_rest_route($namespace, '/check-updates', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'check_updates'),
            'permission_callback' => '__return_true'
        ));

        // Retorna informações de um plugin
        register_rest_route($namespace, '/plugin-info/(?P<slug>[a-zA-Z0-9_-]+)', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'get_plugin_info'),
            'permission_callback' => '__return_true'
        ));

        // Download do plugin
        register_rest_route($namespace, '/download/(?P<slug>[a-zA-Z0-9_-]+)', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'download_plugin'),
            'permission_callback' => '__return_true'
        ));

        // Valida licença
        register_rest_route($namespace, '/validate-license', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'validate_license'),
            'permission_callback' => '__return_true'
        ));

        // Lista plugins disponíveis
        register_rest_route($namespace, '/plugins', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'list_plugins'),
            'permission_callback' => '__return_true'
        ));
    }

    /**
     * Verifica se a requisição tem licença válida
     */
    private static function verify_license($request) {
        $license_key = $request->get_param('license_key');
        $site_url = $request->get_param('site_url');

        if (empty($license_key) || empty($site_url)) {
            return new WP_Error('missing_params', 'Licença e URL do site são obrigatórios', array('status' => 400));
        }

        $result = PUS_Database::validate_license($license_key, $site_url);
        
        if (!$result['valid']) {
            return new WP_Error('invalid_license', $result['message'], array('status' => 403));
        }

        return $result['license'];
    }

    /**
     * Verifica atualizações disponíveis para os plugins
     */
    public static function check_updates($request) {
        $license = self::verify_license($request);
        if (is_wp_error($license)) {
            return $license;
        }

        $installed_plugins = $request->get_param('plugins');
        if (!is_array($installed_plugins)) {
            $installed_plugins = array();
        }

        $updates = array();
        $server_plugins = PUS_Database::get_plugins(true);

        foreach ($server_plugins as $plugin) {
            if (isset($installed_plugins[$plugin->plugin_slug])) {
                $installed_version = $installed_plugins[$plugin->plugin_slug];
                
                if (version_compare($plugin->plugin_version, $installed_version, '>')) {
                    $updates[$plugin->plugin_slug] = array(
                        'slug' => $plugin->plugin_slug,
                        'name' => $plugin->plugin_name,
                        'version' => $plugin->plugin_version,
                        'installed_version' => $installed_version,
                        'tested' => $plugin->tested_wp_version,
                        'requires' => $plugin->requires_wp_version,
                        'requires_php' => $plugin->requires_php,
                        'changelog' => $plugin->changelog,
                        'banner_url' => $plugin->banner_url,
                        'icon_url' => $plugin->icon_url,
                        'last_updated' => $plugin->last_updated
                    );
                }
            }
        }

        return rest_ensure_response(array(
            'success' => true,
            'updates' => $updates,
            'checked_at' => current_time('mysql')
        ));
    }

    /**
     * Retorna informações de um plugin específico
     */
    public static function get_plugin_info($request) {
        $license = self::verify_license($request);
        if (is_wp_error($license)) {
            return $license;
        }

        $slug = $request->get_param('slug');
        $plugin = PUS_Database::get_plugin_by_slug($slug);

        if (!$plugin) {
            return new WP_Error('not_found', 'Plugin não encontrado', array('status' => 404));
        }

        return rest_ensure_response(array(
            'success' => true,
            'plugin' => array(
                'slug' => $plugin->plugin_slug,
                'name' => $plugin->plugin_name,
                'version' => $plugin->plugin_version,
                'author' => $plugin->plugin_author,
                'description' => $plugin->plugin_description,
                'url' => $plugin->plugin_url,
                'tested' => $plugin->tested_wp_version,
                'requires' => $plugin->requires_wp_version,
                'requires_php' => $plugin->requires_php,
                'changelog' => $plugin->changelog,
                'banner_url' => $plugin->banner_url,
                'icon_url' => $plugin->icon_url,
                'last_updated' => $plugin->last_updated
            )
        ));
    }

    /**
     * Fornece o download do plugin
     */
    public static function download_plugin($request) {
        $license = self::verify_license($request);
        if (is_wp_error($license)) {
            return $license;
        }

        $slug = $request->get_param('slug');
        $current_version = $request->get_param('current_version') ?: '0.0.0';
        $site_url = $request->get_param('site_url');

        $plugin = PUS_Database::get_plugin_by_slug($slug);

        if (!$plugin) {
            return new WP_Error('not_found', 'Plugin não encontrado', array('status' => 404));
        }

        // Registra o log de atualização
        PUS_Database::log_update(
            $license->id,
            $plugin->id,
            $site_url,
            $current_version,
            $plugin->plugin_version
        );

        // Retorna a URL de download
        return rest_ensure_response(array(
            'success' => true,
            'download_url' => $plugin->package_url,
            'version' => $plugin->plugin_version
        ));
    }

    /**
     * Valida uma licença
     */
    public static function validate_license($request) {
        $license_key = $request->get_param('license_key');
        $site_url = $request->get_param('site_url');

        if (empty($license_key) || empty($site_url)) {
            return new WP_Error('missing_params', 'Licença e URL do site são obrigatórios', array('status' => 400));
        }

        $result = PUS_Database::validate_license($license_key, $site_url);

        return rest_ensure_response(array(
            'success' => $result['valid'],
            'message' => $result['message'] ?? 'Licença válida'
        ));
    }

    /**
     * Lista todos os plugins disponíveis
     */
    public static function list_plugins($request) {
        $license = self::verify_license($request);
        if (is_wp_error($license)) {
            return $license;
        }

        $plugins = PUS_Database::get_plugins(true);
        $list = array();

        foreach ($plugins as $plugin) {
            $list[] = array(
                'slug' => $plugin->plugin_slug,
                'name' => $plugin->plugin_name,
                'version' => $plugin->plugin_version,
                'description' => $plugin->plugin_description
            );
        }

        return rest_ensure_response(array(
            'success' => true,
            'plugins' => $list
        ));
    }
}
