<?php
/**
 * Classe para gerenciar o painel administrativo do servidor
 */

if (!defined('ABSPATH')) {
    exit;
}

class PUS_Admin {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_init', array($this, 'handle_actions'));
    }

    /**
     * Adiciona o menu no admin
     */
    public function add_menu() {
        add_menu_page(
            __('Premium Updates', 'premium-updates-server'),
            __('Premium Updates', 'premium-updates-server'),
            'manage_options',
            'premium-updates',
            array($this, 'render_plugins_page'),
            'dashicons-update',
            65
        );

        add_submenu_page(
            'premium-updates',
            __('Plugins', 'premium-updates-server'),
            __('Plugins', 'premium-updates-server'),
            'manage_options',
            'premium-updates',
            array($this, 'render_plugins_page')
        );

        add_submenu_page(
            'premium-updates',
            __('Licenças', 'premium-updates-server'),
            __('Licenças', 'premium-updates-server'),
            'manage_options',
            'premium-updates-licenses',
            array($this, 'render_licenses_page')
        );

        add_submenu_page(
            'premium-updates',
            __('Planos', 'premium-updates-server'),
            __('Planos', 'premium-updates-server'),
            'manage_options',
            'premium-updates-plans',
            array($this, 'render_plans_page')
        );

        add_submenu_page(
            'premium-updates',
            __('Pagamentos (Asaas)', 'premium-updates-server'),
            __('Pagamentos', 'premium-updates-server'),
            'manage_options',
            'premium-updates-asaas',
            array($this, 'render_asaas_page')
        );

        add_submenu_page(
            'premium-updates',
            __('Logs', 'premium-updates-server'),
            __('Logs', 'premium-updates-server'),
            'manage_options',
            'premium-updates-logs',
            array($this, 'render_logs_page')
        );

        add_submenu_page(
            'premium-updates',
            __('Configurações', 'premium-updates-server'),
            __('Configurações', 'premium-updates-server'),
            'manage_options',
            'premium-updates-settings',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Enfileira scripts e estilos
     */
    public function enqueue_scripts($hook) {
        if (strpos($hook, 'premium-updates') === false) {
            return;
        }

        wp_enqueue_style(
            'pus-admin-style',
            PUS_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            PUS_VERSION
        );

        wp_enqueue_script(
            'pus-admin-script',
            PUS_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            PUS_VERSION,
            true
        );

        wp_localize_script('pus-admin-script', 'pusAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pus_admin_nonce')
        ));
    }

    /**
     * Processa ações do admin
     */
    public function handle_actions() {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Salvar plugin
        if (isset($_POST['pus_save_plugin']) && wp_verify_nonce($_POST['pus_nonce'], 'pus_save_plugin')) {
            $this->save_plugin();
        }

        // Deletar plugin
        if (isset($_GET['action']) && $_GET['action'] === 'delete_plugin' && isset($_GET['id'])) {
            if (wp_verify_nonce($_GET['_wpnonce'], 'delete_plugin_' . $_GET['id'])) {
                PUS_Database::delete_plugin(intval($_GET['id']));
                wp_redirect(admin_url('admin.php?page=premium-updates&deleted=1'));
                exit;
            }
        }

        // Salvar licença
        if (isset($_POST['pus_save_license']) && wp_verify_nonce($_POST['pus_nonce'], 'pus_save_license')) {
            $this->save_license();
        }

        // Deletar licença
        if (isset($_GET['action']) && $_GET['action'] === 'delete_license' && isset($_GET['id'])) {
            if (wp_verify_nonce($_GET['_wpnonce'], 'delete_license_' . $_GET['id'])) {
                PUS_Database::delete_license(intval($_GET['id']));
                wp_redirect(admin_url('admin.php?page=premium-updates-licenses&deleted=1'));
                exit;
            }
        }

        // Salvar configurações
        if (isset($_POST['pus_save_settings']) && wp_verify_nonce($_POST['pus_nonce'], 'pus_save_settings')) {
            $this->save_settings();
        }

        // Salvar configurações Asaas
        if (isset($_POST['pus_save_asaas']) && wp_verify_nonce($_POST['pus_nonce'], 'pus_save_asaas')) {
            $this->save_asaas_settings();
        }

        // Salvar plano
        if (isset($_POST['pus_save_plan']) && wp_verify_nonce($_POST['pus_nonce'], 'pus_save_plan')) {
            $this->save_plan();
        }

        // Deletar plano
        if (isset($_GET['page']) && $_GET['page'] === 'premium-updates-plans' && isset($_GET['delete'])) {
            $plan_id = sanitize_text_field($_GET['delete']);
            if (wp_verify_nonce($_GET['_wpnonce'], 'delete_plan_' . $plan_id)) {
                PUS_Plans::delete_plan($plan_id);
                wp_redirect(admin_url('admin.php?page=premium-updates-plans&deleted=1'));
                exit;
            }
        }
    }

    /**
     * Salva um plugin
     */
    private function save_plugin() {
        $data = array(
            'plugin_slug' => sanitize_title($_POST['plugin_slug']),
            'plugin_name' => sanitize_text_field($_POST['plugin_name']),
            'plugin_version' => sanitize_text_field($_POST['plugin_version']),
            'plugin_author' => sanitize_text_field($_POST['plugin_author']),
            'plugin_description' => sanitize_textarea_field($_POST['plugin_description']),
            'plugin_url' => esc_url_raw($_POST['plugin_url']),
            'package_url' => esc_url_raw($_POST['package_url']),
            'tested_wp_version' => sanitize_text_field($_POST['tested_wp_version']),
            'requires_wp_version' => sanitize_text_field($_POST['requires_wp_version']),
            'requires_php' => sanitize_text_field($_POST['requires_php']),
            'changelog' => wp_kses_post($_POST['changelog']),
            'banner_url' => esc_url_raw($_POST['banner_url']),
            'icon_url' => esc_url_raw($_POST['icon_url']),
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        );

        PUS_Database::save_plugin($data);
        wp_redirect(admin_url('admin.php?page=premium-updates&saved=1'));
        exit;
    }

    /**
     * Salva uma licença
     */
    private function save_license() {
        $data = array(
            'client_name' => sanitize_text_field($_POST['client_name']),
            'client_email' => sanitize_email($_POST['client_email']),
            'site_url' => esc_url_raw($_POST['site_url']),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'max_activations' => intval($_POST['max_activations']),
            'expires_at' => !empty($_POST['expires_at']) ? sanitize_text_field($_POST['expires_at']) : null,
            'notes' => sanitize_textarea_field($_POST['notes'])
        );

        if (!empty($_POST['license_id'])) {
            $data['id'] = intval($_POST['license_id']);
        }

        if (!empty($_POST['license_key'])) {
            $data['license_key'] = sanitize_text_field($_POST['license_key']);
        }

        PUS_Database::save_license($data);
        wp_redirect(admin_url('admin.php?page=premium-updates-licenses&saved=1'));
        exit;
    }

    /**
     * Salva configurações
     */
    private function save_settings() {
        if (isset($_POST['regenerate_api_key'])) {
            update_option('pus_api_secret_key', wp_generate_password(64, false));
        }
        
        wp_redirect(admin_url('admin.php?page=premium-updates-settings&saved=1'));
        exit;
    }

    /**
     * Salva configurações do Asaas
     */
    private function save_asaas_settings() {
        update_option('pus_asaas_sandbox', isset($_POST['pus_asaas_sandbox']) ? 1 : 0);
        update_option('pus_asaas_api_key', sanitize_text_field($_POST['pus_asaas_api_key']));
        update_option('pus_asaas_webhook_token', sanitize_text_field($_POST['pus_asaas_webhook_token']));
        
        wp_redirect(admin_url('admin.php?page=premium-updates-asaas&saved=1'));
        exit;
    }

    /**
     * Salva um plano
     */
    private function save_plan() {
        $features = array_filter(array_map('trim', explode("\n", $_POST['plan_features'])));
        
        $plan_data = array(
            'id' => sanitize_title($_POST['plan_id']),
            'name' => sanitize_text_field($_POST['plan_name']),
            'description' => sanitize_text_field($_POST['plan_description']),
            'price' => floatval($_POST['plan_price']),
            'type' => sanitize_text_field($_POST['plan_type']),
            'cycle' => sanitize_text_field($_POST['plan_cycle']),
            'max_sites' => intval($_POST['plan_max_sites']),
            'features' => array_map('sanitize_text_field', $features)
        );

        PUS_Plans::save_plan($plan_data);
        
        wp_redirect(admin_url('admin.php?page=premium-updates-plans&saved=1'));
        exit;
    }

    /**
     * Renderiza a página de plugins
     */
    public function render_plugins_page() {
        $action = isset($_GET['action']) ? $_GET['action'] : 'list';
        $plugin = null;

        if ($action === 'edit' && isset($_GET['id'])) {
            global $wpdb;
            $table = $wpdb->prefix . 'pus_plugins';
            $plugin = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", intval($_GET['id'])));
        }

        include PUS_PLUGIN_DIR . 'templates/admin-plugins.php';
    }

    /**
     * Renderiza a página de licenças
     */
    public function render_licenses_page() {
        $action = isset($_GET['action']) ? $_GET['action'] : 'list';
        $license = null;

        if ($action === 'edit' && isset($_GET['id'])) {
            global $wpdb;
            $table = $wpdb->prefix . 'pus_licenses';
            $license = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", intval($_GET['id'])));
        }

        include PUS_PLUGIN_DIR . 'templates/admin-licenses.php';
    }

    /**
     * Renderiza a página de logs
     */
    public function render_logs_page() {
        $logs = PUS_Database::get_logs(100);
        include PUS_PLUGIN_DIR . 'templates/admin-logs.php';
    }

    /**
     * Renderiza a página de configurações
     */
    public function render_settings_page() {
        include PUS_PLUGIN_DIR . 'templates/admin-settings.php';
    }

    /**
     * Renderiza a página do Asaas
     */
    public function render_asaas_page() {
        include PUS_PLUGIN_DIR . 'templates/admin-asaas.php';
    }

    /**
     * Renderiza a página de planos
     */
    public function render_plans_page() {
        include PUS_PLUGIN_DIR . 'templates/admin-plans.php';
    }
}
