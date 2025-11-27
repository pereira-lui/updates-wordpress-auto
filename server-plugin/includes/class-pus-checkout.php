<?php
/**
 * Classe para a página pública de checkout
 */

if (!defined('ABSPATH')) {
    exit;
}

class PUS_Checkout {

    public function __construct() {
        add_shortcode('pus_pricing', array($this, 'render_pricing_shortcode'));
        add_shortcode('pus_checkout', array($this, 'render_checkout_shortcode'));
        
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_pus_process_checkout', array($this, 'process_checkout'));
        add_action('wp_ajax_nopriv_pus_process_checkout', array($this, 'process_checkout'));
    }

    /**
     * Enfileira scripts e estilos
     */
    public function enqueue_scripts() {
        global $post;
        
        if (!$post || (
            !has_shortcode($post->post_content, 'pus_pricing') && 
            !has_shortcode($post->post_content, 'pus_checkout')
        )) {
            return;
        }

        wp_enqueue_style(
            'pus-checkout-style',
            PUS_PLUGIN_URL . 'assets/css/checkout.css',
            array(),
            PUS_VERSION
        );

        wp_enqueue_script(
            'pus-checkout-script',
            PUS_PLUGIN_URL . 'assets/js/checkout.js',
            array('jquery'),
            PUS_VERSION,
            true
        );

        wp_localize_script('pus-checkout-script', 'pusCheckout', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pus_checkout_nonce'),
            'strings' => array(
                'processing' => __('Processando...', 'premium-updates-server'),
                'error' => __('Erro ao processar. Tente novamente.', 'premium-updates-server'),
                'required' => __('Este campo é obrigatório', 'premium-updates-server'),
                'invalid_email' => __('E-mail inválido', 'premium-updates-server'),
                'invalid_cpf' => __('CPF/CNPJ inválido', 'premium-updates-server'),
                'invalid_url' => __('URL inválida', 'premium-updates-server')
            )
        ));
    }

    /**
     * Shortcode de tabela de preços
     */
    public function render_pricing_shortcode($atts) {
        $atts = shortcode_atts(array(
            'checkout_url' => ''
        ), $atts);

        $plans = PUS_Plans::get_plans();
        
        ob_start();
        include PUS_PLUGIN_DIR . 'templates/public-pricing.php';
        return ob_get_clean();
    }

    /**
     * Shortcode de checkout
     */
    public function render_checkout_shortcode($atts) {
        $atts = shortcode_atts(array(
            'plan' => ''
        ), $atts);

        $selected_plan = isset($_GET['plan']) ? sanitize_text_field($_GET['plan']) : $atts['plan'];
        $plan = PUS_Plans::get_plan($selected_plan);
        $plans = PUS_Plans::get_plans();

        ob_start();
        include PUS_PLUGIN_DIR . 'templates/public-checkout.php';
        return ob_get_clean();
    }

    /**
     * Processa o checkout via AJAX
     */
    public function process_checkout() {
        check_ajax_referer('pus_checkout_nonce', 'nonce');

        $plan_id = isset($_POST['plan_id']) ? sanitize_text_field($_POST['plan_id']) : '';
        $client_name = isset($_POST['client_name']) ? sanitize_text_field($_POST['client_name']) : '';
        $client_email = isset($_POST['client_email']) ? sanitize_email($_POST['client_email']) : '';
        $cpf_cnpj = isset($_POST['cpf_cnpj']) ? sanitize_text_field($_POST['cpf_cnpj']) : '';
        $site_url = isset($_POST['site_url']) ? esc_url_raw($_POST['site_url']) : '';

        // Validações
        if (empty($plan_id) || empty($client_name) || empty($client_email) || empty($cpf_cnpj) || empty($site_url)) {
            wp_send_json_error(__('Todos os campos são obrigatórios', 'premium-updates-server'));
        }

        if (!is_email($client_email)) {
            wp_send_json_error(__('E-mail inválido', 'premium-updates-server'));
        }

        if (!$this->validate_cpf_cnpj($cpf_cnpj)) {
            wp_send_json_error(__('CPF/CNPJ inválido', 'premium-updates-server'));
        }

        if (!filter_var($site_url, FILTER_VALIDATE_URL)) {
            wp_send_json_error(__('URL do site inválida', 'premium-updates-server'));
        }

        $plan = PUS_Plans::get_plan($plan_id);
        if (!$plan) {
            wp_send_json_error(__('Plano não encontrado', 'premium-updates-server'));
        }

        // Cria a cobrança no Asaas
        $asaas = new PUS_Asaas();
        
        if (!$asaas->is_configured()) {
            wp_send_json_error(__('Sistema de pagamento não configurado', 'premium-updates-server'));
        }

        $result = $asaas->create_license_payment(
            array(
                'client_name' => $client_name,
                'client_email' => $client_email,
                'cpf_cnpj' => $cpf_cnpj,
                'site_url' => $site_url
            ),
            $plan
        );

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success(array(
            'message' => __('Redirecionando para pagamento...', 'premium-updates-server'),
            'checkout_url' => $result['checkout_url'],
            'license_key' => $result['license_key']
        ));
    }

    /**
     * Valida CPF ou CNPJ
     */
    private function validate_cpf_cnpj($value) {
        $value = preg_replace('/[^0-9]/', '', $value);
        
        if (strlen($value) === 11) {
            return $this->validate_cpf($value);
        } elseif (strlen($value) === 14) {
            return $this->validate_cnpj($value);
        }

        return false;
    }

    /**
     * Valida CPF
     */
    private function validate_cpf($cpf) {
        if (preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }

        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) {
                return false;
            }
        }

        return true;
    }

    /**
     * Valida CNPJ
     */
    private function validate_cnpj($cnpj) {
        if (preg_match('/(\d)\1{13}/', $cnpj)) {
            return false;
        }

        $b = array(6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2);
        
        for ($i = 0, $n = 0; $i < 12; $n += $cnpj[$i] * $b[++$i]);
        
        if ($cnpj[12] != ((($n %= 11) < 2) ? 0 : 11 - $n)) {
            return false;
        }

        for ($i = 0, $n = 0; $i <= 12; $n += $cnpj[$i] * $b[$i++]);
        
        if ($cnpj[13] != ((($n %= 11) < 2) ? 0 : 11 - $n)) {
            return false;
        }

        return true;
    }
}
