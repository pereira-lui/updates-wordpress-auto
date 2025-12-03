<?php
/**
 * Plugin Name: Premium Updates Client
 * Plugin URI: https://github.com/pereira-lui/updates-wordpress-auto
 * Description: Cliente para receber atualizações automáticas de plugins premium. Inclui sistema de assinatura integrado e rollback automático em caso de erros.
 * Version: 3.4.0
 * Author: Lui Pereira
 * Author URI: https://github.com/pereira-lui
 * License: GPL v2 or later
 * Text Domain: premium-updates-client
 */

if (!defined('ABSPATH')) {
    exit;
}

define('PUC_VERSION', '3.4.0');
define('PUC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PUC_PLUGIN_URL', plugin_dir_url(__FILE__));

// Carrega o módulo de atualização segura
require_once PUC_PLUGIN_DIR . 'includes/class-safe-updater.php';

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
        add_action('wp_ajax_puc_get_prices', array($this, 'ajax_get_prices'));
        add_action('wp_ajax_puc_create_subscription', array($this, 'ajax_create_subscription'));
        add_action('wp_ajax_puc_renew_subscription', array($this, 'ajax_renew_subscription'));
        add_action('wp_ajax_puc_check_payment', array($this, 'ajax_check_payment'));
        add_action('wp_ajax_puc_check_license', array($this, 'ajax_check_license'));
        add_action('wp_ajax_puc_get_account', array($this, 'ajax_get_account'));
        add_action('wp_ajax_puc_get_payments', array($this, 'ajax_get_payments'));
        add_action('wp_ajax_puc_get_updates_history', array($this, 'ajax_get_updates_history'));
        add_action('wp_ajax_puc_get_notification_preferences', array($this, 'ajax_get_notification_preferences'));
        add_action('wp_ajax_puc_set_notification_preferences', array($this, 'ajax_set_notification_preferences'));
        
        // Hook para registrar atualizações de plugins
        add_action('upgrader_process_complete', array($this, 'log_plugin_update'), 10, 2);
        
        // Cron
        add_action('puc_check_updates', array($this, 'scheduled_check_updates'));
        add_action('puc_check_license_expiration', array($this, 'check_license_expiration'));
        
        if (!wp_next_scheduled('puc_check_updates')) {
            wp_schedule_event(time(), 'twicedaily', 'puc_check_updates');
        }
        
        if (!wp_next_scheduled('puc_check_license_expiration')) {
            wp_schedule_event(time(), 'daily', 'puc_check_license_expiration');
        }
        
        // Notificações
        add_action('admin_notices', array($this, 'admin_notices'));
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
            'adminEmail' => get_option('admin_email'),
            'strings' => array(
                'testing' => __('Testando...', 'premium-updates-client'),
                'syncing' => __('Sincronizando...', 'premium-updates-client'),
                'success' => __('Sucesso!', 'premium-updates-client'),
                'error' => __('Erro!', 'premium-updates-client'),
                'loading' => __('Carregando...', 'premium-updates-client'),
                'processing' => __('Processando...', 'premium-updates-client'),
                'copy_success' => __('Código copiado!', 'premium-updates-client')
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
        $license_status = get_option('puc_license_status', array());
        
        include PUC_PLUGIN_DIR . 'templates/settings.php';
    }

    /**
     * Faz requisição para o servidor
     */
    private function api_request($endpoint, $data = array(), $method = 'POST') {
        if (empty($this->server_url)) {
            return new WP_Error('not_configured', __('URL do servidor não configurada', 'premium-updates-client'));
        }

        $url = trailingslashit($this->server_url) . 'api/v1/' . $endpoint;

        $data['license_key'] = $this->license_key;
        $data['site_url'] = home_url('/');

        $args = array(
            'timeout' => 30,
            'headers' => array(
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ),
            'sslverify' => true
        );

        if ($method === 'POST') {
            $args['body'] = json_encode($data);
            $response = wp_remote_post($url, $args);
        } else {
            $url = add_query_arg($data, $url);
            $response = wp_remote_get($url, $args);
        }

        if (is_wp_error($response)) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);

        if ($code >= 400) {
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
        
        if (empty($managed_plugins) || empty($this->license_key)) {
            return $transient;
        }

        $plugins_to_check = array();
        foreach ($managed_plugins as $plugin_file) {
            if (isset($transient->checked[$plugin_file])) {
                $parts = explode('/', $plugin_file);
                $slug = $parts[0];
                $plugins_to_check[$slug] = $transient->checked[$plugin_file];
            }
        }

        if (empty($plugins_to_check)) {
            return $transient;
        }

        $result = $this->api_request('check-updates', array(
            'plugins' => $plugins_to_check
        ));

        if (is_wp_error($result) || empty($result['updates'])) {
            return $transient;
        }

        foreach ($result['updates'] as $slug => $update) {
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
        $url = trailingslashit($this->server_url) . 'api/v1/download/' . $slug;
        
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
     * Verifica expiração da licença
     */
    public function check_license_expiration() {
        if (empty($this->license_key)) {
            return;
        }

        $result = $this->api_request('license/status');

        if (!is_wp_error($result) && isset($result['license'])) {
            update_option('puc_license_status', $result['license']);
        }
    }

    /**
     * Exibe notificações no admin
     */
    public function admin_notices() {
        $license_status = get_option('puc_license_status', array());
        
        if (!empty($license_status['status']) && $license_status['status'] === 'expired') {
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p><strong>' . __('Premium Updates:', 'premium-updates-client') . '</strong> ';
            echo __('Sua licença expirou. Renove para continuar recebendo atualizações.', 'premium-updates-client');
            echo ' <a href="' . admin_url('options-general.php?page=premium-updates-client') . '">' . __('Renovar agora', 'premium-updates-client') . '</a></p>';
            echo '</div>';
        }
        
        if (!empty($license_status['expires_at'])) {
            $expires = strtotime($license_status['expires_at']);
            $days_left = floor(($expires - time()) / 86400);
            
            if ($days_left > 0 && $days_left <= 7) {
                echo '<div class="notice notice-info is-dismissible">';
                echo '<p><strong>' . __('Premium Updates:', 'premium-updates-client') . '</strong> ';
                echo sprintf(__('Sua licença expira em %d dias.', 'premium-updates-client'), $days_left);
                echo ' <a href="' . admin_url('options-general.php?page=premium-updates-client') . '">' . __('Renovar', 'premium-updates-client') . '</a></p>';
                echo '</div>';
            }
        }
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

        if (empty($server_url)) {
            wp_send_json_error(__('URL do servidor é obrigatória', 'premium-updates-client'));
        }

        $this->server_url = $server_url;
        $this->license_key = $license_key;

        if (!empty($license_key)) {
            $result = $this->api_request('validate-license');

            if (is_wp_error($result)) {
                wp_send_json_error($result->get_error_message());
            }

            if (!empty($result['success'])) {
                update_option('puc_license_status', $result['license'] ?? array());
                wp_send_json_success(array(
                    'message' => __('Conexão e licença validadas com sucesso!', 'premium-updates-client'),
                    'license' => $result['license'] ?? array()
                ));
            } else {
                wp_send_json_error($result['message'] ?? __('Falha na validação', 'premium-updates-client'));
            }
        } else {
            // Apenas testa conexão sem licença
            $result = $this->api_request('subscription/prices', array(), 'GET');
            
            if (is_wp_error($result)) {
                wp_send_json_error($result->get_error_message());
            }
            
            wp_send_json_success(array(
                'message' => __('Conexão estabelecida! Configure uma licença para receber atualizações.', 'premium-updates-client'),
                'needs_license' => true
            ));
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

    /**
     * AJAX: Obtém preços de assinatura
     */
    public function ajax_get_prices() {
        check_ajax_referer('puc_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permissão negada', 'premium-updates-client'));
        }

        $server_url = isset($_POST['server_url']) ? esc_url_raw($_POST['server_url']) : $this->server_url;
        
        if (empty($server_url)) {
            wp_send_json_error(__('URL do servidor não configurada', 'premium-updates-client'));
        }

        $this->server_url = $server_url;
        $result = $this->api_request('subscription/prices', array(), 'GET');

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        if (!empty($result['data'])) {
            wp_send_json_success($result['data']);
        } elseif (!empty($result['prices'])) {
            wp_send_json_success($result['prices']);
        } else {
            wp_send_json_error(__('Não foi possível obter os preços', 'premium-updates-client'));
        }
    }

    /**
     * AJAX: Cria nova assinatura
     */
    public function ajax_create_subscription() {
        check_ajax_referer('puc_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permissão negada', 'premium-updates-client'));
        }

        $server_url = isset($_POST['server_url']) ? esc_url_raw($_POST['server_url']) : $this->server_url;
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $document = isset($_POST['document']) ? sanitize_text_field($_POST['document']) : '';
        $period = isset($_POST['period']) ? sanitize_text_field($_POST['period']) : 'monthly';
        $payment_method = isset($_POST['payment_method']) ? sanitize_text_field($_POST['payment_method']) : 'pix';
        $generate_invoice = !empty($_POST['generate_invoice']) ? 1 : 0;

        if (empty($server_url) || empty($name) || empty($email)) {
            wp_send_json_error(__('Preencha todos os campos obrigatórios', 'premium-updates-client'));
        }

        $this->server_url = $server_url;
        
        $result = $this->api_request('subscription/create', array(
            'name' => $name,
            'email' => $email,
            'document' => $document,
            'site_url' => home_url('/'),
            'period' => $period,
            'payment_method' => $payment_method,
            'generate_invoice' => $generate_invoice
        ));

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        if (!empty($result['success'])) {
            // Salva informações temporárias do pagamento
            update_option('puc_pending_payment', array(
                'payment_id' => $result['payment_id'],
                'license_id' => $result['license_id'],
                'created_at' => time()
            ));
            
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['message'] ?? __('Erro ao criar assinatura', 'premium-updates-client'));
        }
    }

    /**
     * AJAX: Renova assinatura
     */
    public function ajax_renew_subscription() {
        check_ajax_referer('puc_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permissão negada', 'premium-updates-client'));
        }

        $period = isset($_POST['period']) ? sanitize_text_field($_POST['period']) : '';
        $payment_method = isset($_POST['payment_method']) ? sanitize_text_field($_POST['payment_method']) : 'pix';
        $generate_invoice = !empty($_POST['generate_invoice']) ? 1 : 0;

        if (empty($this->license_key)) {
            wp_send_json_error(__('Nenhuma licença configurada', 'premium-updates-client'));
        }

        $result = $this->api_request('subscription/renew', array(
            'license_key' => $this->license_key,
            'period' => $period,
            'payment_method' => $payment_method,
            'generate_invoice' => $generate_invoice
        ));

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        if (!empty($result['success'])) {
            update_option('puc_pending_payment', array(
                'payment_id' => $result['payment_id'],
                'created_at' => time()
            ));
            
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['message'] ?? __('Erro ao renovar assinatura', 'premium-updates-client'));
        }
    }

    /**
     * AJAX: Verifica status do pagamento
     */
    public function ajax_check_payment() {
        check_ajax_referer('puc_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permissão negada', 'premium-updates-client'));
        }

        $payment_id = isset($_POST['payment_id']) ? sanitize_text_field($_POST['payment_id']) : '';

        if (empty($payment_id)) {
            $pending = get_option('puc_pending_payment', array());
            $payment_id = $pending['payment_id'] ?? '';
        }

        if (empty($payment_id)) {
            wp_send_json_error(__('Nenhum pagamento pendente', 'premium-updates-client'));
        }

        $result = $this->api_request('subscription/status/' . $payment_id, array(), 'GET');

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        if (!empty($result['success'])) {
            // Se pagamento confirmado, salva a licença
            if ($result['status'] === 'confirmed' && !empty($result['license_key'])) {
                update_option('puc_license_key', $result['license_key']);
                $this->license_key = $result['license_key'];
                delete_option('puc_pending_payment');
                
                // Atualiza status da licença
                $this->check_license_expiration();
            }
            
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['message'] ?? __('Erro ao verificar pagamento', 'premium-updates-client'));
        }
    }

    /**
     * AJAX: Verifica status da licença
     */
    public function ajax_check_license() {
        check_ajax_referer('puc_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permissão negada', 'premium-updates-client'));
        }

        if (empty($this->license_key)) {
            wp_send_json_error(__('Nenhuma licença configurada', 'premium-updates-client'));
        }

        $result = $this->api_request('license/status');

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        if (!empty($result['success'])) {
            update_option('puc_license_status', $result['license']);
            wp_send_json_success($result['license']);
        } else {
            wp_send_json_error($result['message'] ?? __('Erro ao verificar licença', 'premium-updates-client'));
        }
    }

    /**
     * AJAX: Obtém informações da conta
     */
    public function ajax_get_account() {
        check_ajax_referer('puc_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permissão negada', 'premium-updates-client'));
        }

        if (empty($this->license_key)) {
            wp_send_json_error(__('Nenhuma licença configurada', 'premium-updates-client'));
        }

        $result = $this->api_request('my/account');

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        if (!empty($result['success'])) {
            wp_send_json_success($result['data']);
        } else {
            wp_send_json_error($result['message'] ?? __('Erro ao obter dados da conta', 'premium-updates-client'));
        }
    }

    /**
     * AJAX: Obtém histórico de pagamentos
     */
    public function ajax_get_payments() {
        check_ajax_referer('puc_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permissão negada', 'premium-updates-client'));
        }

        if (empty($this->license_key)) {
            wp_send_json_error(__('Nenhuma licença configurada', 'premium-updates-client'));
        }

        $result = $this->api_request('my/payments');

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        if (!empty($result['success'])) {
            wp_send_json_success($result['data']);
        } else {
            wp_send_json_error($result['message'] ?? __('Erro ao obter pagamentos', 'premium-updates-client'));
        }
    }

    /**
     * AJAX: Obtém histórico de atualizações
     */
    public function ajax_get_updates_history() {
        check_ajax_referer('puc_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permissão negada', 'premium-updates-client'));
        }

        if (empty($this->license_key)) {
            wp_send_json_error(__('Nenhuma licença configurada', 'premium-updates-client'));
        }

        $result = $this->api_request('my/updates');

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        if (!empty($result['success'])) {
            wp_send_json_success($result['data']);
        } else {
            wp_send_json_error($result['message'] ?? __('Erro ao obter histórico', 'premium-updates-client'));
        }
    }

    /**
     * Registra atualização de plugin no servidor
     */
    public function log_plugin_update($upgrader, $hook_extra) {
        // Verifica se é atualização de plugin
        if (!isset($hook_extra['type']) || $hook_extra['type'] !== 'plugin') {
            return;
        }

        if (!isset($hook_extra['plugins']) || !is_array($hook_extra['plugins'])) {
            return;
        }

        $managed_plugins = get_option('puc_managed_plugins', array());
        
        foreach ($hook_extra['plugins'] as $plugin_file) {
            // Só registra plugins gerenciados
            if (!in_array($plugin_file, $managed_plugins)) {
                continue;
            }

            $parts = explode('/', $plugin_file);
            $slug = $parts[0];

            // Obtém dados do plugin
            $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin_file);
            $new_version = $plugin_data['Version'] ?? '';

            // Obtém versão anterior (do cache de transient)
            $update_plugins = get_site_transient('update_plugins');
            $old_version = '';
            if (isset($update_plugins->response[$plugin_file])) {
                // A versão anterior era a atual do site antes da atualização
                $old_version = $update_plugins->checked[$plugin_file] ?? '';
            }

            // Envia para o servidor
            if (!empty($slug) && !empty($new_version)) {
                $this->api_request('my/log-update', array(
                    'plugin_slug' => $slug,
                    'from_version' => $old_version,
                    'to_version' => $new_version
                ));
            }
        }
    }

    /**
     * AJAX: Obtém preferências de notificação
     */
    public function ajax_get_notification_preferences() {
        check_ajax_referer('puc_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permissão negada', 'premium-updates-client'));
        }

        if (empty($this->license_key)) {
            wp_send_json_error(__('Nenhuma licença configurada', 'premium-updates-client'));
        }

        $result = $this->api_request('my/notifications');

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        if (!empty($result['success'])) {
            wp_send_json_success($result['data']);
        } else {
            wp_send_json_error($result['message'] ?? __('Erro ao obter preferências', 'premium-updates-client'));
        }
    }

    /**
     * AJAX: Define preferências de notificação
     */
    public function ajax_set_notification_preferences() {
        check_ajax_referer('puc_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permissão negada', 'premium-updates-client'));
        }

        if (empty($this->license_key)) {
            wp_send_json_error(__('Nenhuma licença configurada', 'premium-updates-client'));
        }

        $notification_email = sanitize_email($_POST['notification_email'] ?? '');
        $notify_on_update = intval($_POST['notify_on_update'] ?? 0);
        $notify_on_error = intval($_POST['notify_on_error'] ?? 0);
        $notify_on_rollback = intval($_POST['notify_on_rollback'] ?? 0);

        $result = $this->api_request('my/notifications', array(
            'notification_email' => $notification_email,
            'notify_on_update' => $notify_on_update,
            'notify_on_error' => $notify_on_error,
            'notify_on_rollback' => $notify_on_rollback
        ));

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        if (!empty($result['success'])) {
            wp_send_json_success($result['data']);
        } else {
            wp_send_json_error($result['message'] ?? __('Erro ao salvar preferências', 'premium-updates-client'));
        }
    }
}

// Inicializa o plugin
Premium_Updates_Client::get_instance();

// Cleanup ao desativar
register_deactivation_hook(__FILE__, function() {
    wp_clear_scheduled_hook('puc_check_updates');
    wp_clear_scheduled_hook('puc_check_license_expiration');
});
