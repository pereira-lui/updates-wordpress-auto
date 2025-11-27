<?php
/**
 * Plugin Name: Premium Updates Client
 * Plugin URI: https://github.com/pereira-lui/updates-wordpress-auto
 * Description: Cliente para receber atualizações automáticas de plugins premium do servidor central.
 * Version: 1.0.0
 * Author: Lui Pereira
 * Author URI: https://github.com/pereira-lui
 * License: GPL v2 or later
 * Text Domain: premium-updates-client
 */

if (!defined('ABSPATH')) {
    exit;
}

define('PUC_VERSION', '1.0.0');
define('PUC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PUC_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Classe principal do plugin cliente
 */
class Premium_Updates_Client {

    private static $instance = null;
    private $server_url;
    private $license_key;
    private $managed_plugins = array();

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->server_url = get_option('puc_server_url', '');
        $this->license_key = get_option('puc_license_key', '');
        
        $this->init_hooks();
    }

    private function init_hooks() {
        // Admin
        add_action('admin_menu', array($this, 'add_settings_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Atualizações
        add_filter('pre_set_site_transient_update_plugins', array($this, 'check_for_updates'));
        add_filter('plugins_api', array($this, 'plugin_info'), 20, 3);
        add_filter('upgrader_source_selection', array($this, 'fix_plugin_folder_name'), 10, 4);
        
        // AJAX
        add_action('wp_ajax_puc_test_connection', array($this, 'ajax_test_connection'));
        add_action('wp_ajax_puc_sync_plugins', array($this, 'ajax_sync_plugins'));
        
        // Cron
        add_action('puc_check_updates', array($this, 'scheduled_check_updates'));
        
        if (!wp_next_scheduled('puc_check_updates')) {
            wp_schedule_event(time(), 'twicedaily', 'puc_check_updates');
        }
    }

    /**
     * Adiciona menu de configurações
     */
    public function add_settings_menu() {
        add_options_page(
            __('Premium Updates', 'premium-updates-client'),
            __('Premium Updates', 'premium-updates-client'),
            'manage_options',
            'premium-updates-client',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Registra as configurações
     */
    public function register_settings() {
        register_setting('puc_settings', 'puc_server_url', array(
            'sanitize_callback' => 'esc_url_raw'
        ));
        register_setting('puc_settings', 'puc_license_key', array(
            'sanitize_callback' => 'sanitize_text_field'
        ));
        register_setting('puc_settings', 'puc_managed_plugins', array(
            'sanitize_callback' => array($this, 'sanitize_managed_plugins')
        ));
    }

    /**
     * Sanitiza a lista de plugins gerenciados
     */
    public function sanitize_managed_plugins($input) {
        if (!is_array($input)) {
            return array();
        }
        return array_map('sanitize_text_field', $input);
    }

    /**
     * Enfileira scripts do admin
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'settings_page_premium-updates-client') {
            return;
        }

        wp_enqueue_style(
            'puc-admin-style',
            PUC_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            PUC_VERSION
        );

        wp_enqueue_script(
            'puc-admin-script',
            PUC_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            PUC_VERSION,
            true
        );

        wp_localize_script('puc-admin-script', 'pucAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('puc_admin_nonce'),
            'strings' => array(
                'testing' => __('Testando...', 'premium-updates-client'),
                'syncing' => __('Sincronizando...', 'premium-updates-client'),
                'success' => __('Sucesso!', 'premium-updates-client'),
                'error' => __('Erro!', 'premium-updates-client')
            )
        ));
    }

    /**
     * Renderiza a página de configurações
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $managed_plugins = get_option('puc_managed_plugins', array());
        $all_plugins = get_plugins();
        
        include PUC_PLUGIN_DIR . 'templates/settings.php';
    }

    /**
     * Faz requisição para o servidor
     */
    private function api_request($endpoint, $data = array()) {
        if (empty($this->server_url) || empty($this->license_key)) {
            return new WP_Error('not_configured', __('Plugin não configurado', 'premium-updates-client'));
        }

        $url = trailingslashit($this->server_url) . 'wp-json/premium-updates/v1/' . $endpoint;

        $data['license_key'] = $this->license_key;
        $data['site_url'] = home_url('/');

        $response = wp_remote_post($url, array(
            'timeout' => 30,
            'body' => $data,
            'sslverify' => true
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);

        if ($code !== 200) {
            $message = isset($result['message']) ? $result['message'] : __('Erro desconhecido', 'premium-updates-client');
            return new WP_Error('api_error', $message);
        }

        return $result;
    }

    /**
     * Verifica atualizações
     */
    public function check_for_updates($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }

        $managed_plugins = get_option('puc_managed_plugins', array());
        
        if (empty($managed_plugins)) {
            return $transient;
        }

        // Prepara lista de plugins instalados para verificação
        $plugins_to_check = array();
        foreach ($managed_plugins as $plugin_file) {
            if (isset($transient->checked[$plugin_file])) {
                // Extrai o slug do arquivo do plugin
                $parts = explode('/', $plugin_file);
                $slug = $parts[0];
                $plugins_to_check[$slug] = $transient->checked[$plugin_file];
            }
        }

        if (empty($plugins_to_check)) {
            return $transient;
        }

        // Consulta o servidor
        $result = $this->api_request('check-updates', array(
            'plugins' => $plugins_to_check
        ));

        if (is_wp_error($result) || empty($result['updates'])) {
            return $transient;
        }

        // Adiciona atualizações encontradas
        foreach ($result['updates'] as $slug => $update) {
            // Encontra o arquivo do plugin
            $plugin_file = $this->get_plugin_file_by_slug($slug);
            
            if ($plugin_file) {
                $transient->response[$plugin_file] = (object) array(
                    'id' => $slug,
                    'slug' => $slug,
                    'plugin' => $plugin_file,
                    'new_version' => $update['version'],
                    'url' => isset($update['url']) ? $update['url'] : '',
                    'package' => $this->get_download_url($slug),
                    'icons' => !empty($update['icon_url']) ? array('default' => $update['icon_url']) : array(),
                    'banners' => !empty($update['banner_url']) ? array('default' => $update['banner_url']) : array(),
                    'tested' => isset($update['tested']) ? $update['tested'] : '',
                    'requires' => isset($update['requires']) ? $update['requires'] : '',
                    'requires_php' => isset($update['requires_php']) ? $update['requires_php'] : ''
                );
            }
        }

        return $transient;
    }

    /**
     * Encontra o arquivo do plugin pelo slug
     */
    private function get_plugin_file_by_slug($slug) {
        $managed_plugins = get_option('puc_managed_plugins', array());
        
        foreach ($managed_plugins as $plugin_file) {
            if (strpos($plugin_file, $slug . '/') === 0) {
                return $plugin_file;
            }
        }
        
        return false;
    }

    /**
     * Gera URL de download
     */
    private function get_download_url($slug) {
        $url = trailingslashit($this->server_url) . 'wp-json/premium-updates/v1/download/' . $slug;
        
        return add_query_arg(array(
            'license_key' => $this->license_key,
            'site_url' => urlencode(home_url('/'))
        ), $url);
    }

    /**
     * Retorna informações do plugin para a tela de detalhes
     */
    public function plugin_info($result, $action, $args) {
        if ($action !== 'plugin_information') {
            return $result;
        }

        $managed_plugins = get_option('puc_managed_plugins', array());
        $slug = isset($args->slug) ? $args->slug : '';

        // Verifica se é um plugin gerenciado
        $is_managed = false;
        foreach ($managed_plugins as $plugin_file) {
            if (strpos($plugin_file, $slug . '/') === 0) {
                $is_managed = true;
                break;
            }
        }

        if (!$is_managed) {
            return $result;
        }

        // Busca informações do servidor
        $response = $this->api_request('plugin-info/' . $slug);

        if (is_wp_error($response) || empty($response['plugin'])) {
            return $result;
        }

        $plugin = $response['plugin'];

        return (object) array(
            'name' => $plugin['name'],
            'slug' => $plugin['slug'],
            'version' => $plugin['version'],
            'author' => $plugin['author'],
            'author_profile' => '',
            'requires' => $plugin['requires'],
            'tested' => $plugin['tested'],
            'requires_php' => $plugin['requires_php'],
            'sections' => array(
                'description' => $plugin['description'],
                'changelog' => $plugin['changelog']
            ),
            'download_link' => $this->get_download_url($slug),
            'banners' => !empty($plugin['banner_url']) ? array('default' => $plugin['banner_url']) : array(),
            'icons' => !empty($plugin['icon_url']) ? array('default' => $plugin['icon_url']) : array()
        );
    }

    /**
     * Corrige o nome da pasta do plugin após extração
     */
    public function fix_plugin_folder_name($source, $remote_source, $upgrader, $hook_extra) {
        global $wp_filesystem;

        if (!isset($hook_extra['plugin'])) {
            return $source;
        }

        $managed_plugins = get_option('puc_managed_plugins', array());
        
        if (!in_array($hook_extra['plugin'], $managed_plugins)) {
            return $source;
        }

        $parts = explode('/', $hook_extra['plugin']);
        $expected_folder = $parts[0];
        
        $corrected_source = trailingslashit($remote_source) . $expected_folder;
        
        if ($source !== $corrected_source && $wp_filesystem->exists($source)) {
            $wp_filesystem->move($source, $corrected_source);
            return $corrected_source;
        }

        return $source;
    }

    /**
     * Verifica atualizações agendadas
     */
    public function scheduled_check_updates() {
        delete_site_transient('update_plugins');
        wp_update_plugins();
    }

    /**
     * AJAX: Testa conexão com o servidor
     */
    public function ajax_test_connection() {
        check_ajax_referer('puc_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permissão negada', 'premium-updates-client'));
        }

        $server_url = isset($_POST['server_url']) ? esc_url_raw($_POST['server_url']) : '';
        $license_key = isset($_POST['license_key']) ? sanitize_text_field($_POST['license_key']) : '';

        if (empty($server_url) || empty($license_key)) {
            wp_send_json_error(__('URL do servidor e chave de licença são obrigatórios', 'premium-updates-client'));
        }

        // Temporariamente define as configurações para teste
        $this->server_url = $server_url;
        $this->license_key = $license_key;

        $result = $this->api_request('validate-license');

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        if (!empty($result['success'])) {
            wp_send_json_success(__('Conexão estabelecida com sucesso!', 'premium-updates-client'));
        } else {
            wp_send_json_error($result['message'] ?? __('Falha na validação', 'premium-updates-client'));
        }
    }

    /**
     * AJAX: Sincroniza lista de plugins
     */
    public function ajax_sync_plugins() {
        check_ajax_referer('puc_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permissão negada', 'premium-updates-client'));
        }

        $result = $this->api_request('plugins');

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        if (!empty($result['plugins'])) {
            wp_send_json_success(array(
                'message' => sprintf(__('%d plugins disponíveis no servidor', 'premium-updates-client'), count($result['plugins'])),
                'plugins' => $result['plugins']
            ));
        } else {
            wp_send_json_success(array(
                'message' => __('Nenhum plugin disponível no servidor', 'premium-updates-client'),
                'plugins' => array()
            ));
        }
    }
}

// Inicializa o plugin
Premium_Updates_Client::get_instance();

// Cleanup ao desativar
register_deactivation_hook(__FILE__, function() {
    wp_clear_scheduled_hook('puc_check_updates');
});
