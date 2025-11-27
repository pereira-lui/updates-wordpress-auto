<?php
/**
 * Classe para processar webhooks do Asaas
 */

if (!defined('ABSPATH')) {
    exit;
}

class PUS_Webhook {

    public function __construct() {
        add_action('rest_api_init', array($this, 'register_webhook_route'));
    }

    /**
     * Registra a rota do webhook
     */
    public function register_webhook_route() {
        register_rest_route('premium-updates/v1', '/webhook/asaas', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_webhook'),
            'permission_callback' => array($this, 'verify_webhook')
        ));
    }

    /**
     * Verifica autenticidade do webhook
     */
    public function verify_webhook($request) {
        // O Asaas envia um token de verificação que pode ser configurado
        $webhook_token = get_option('pus_asaas_webhook_token', '');
        
        if (empty($webhook_token)) {
            return true; // Se não configurou token, aceita todos
        }

        $received_token = $request->get_header('asaas-access-token');
        
        return $received_token === $webhook_token;
    }

    /**
     * Processa o webhook
     */
    public function handle_webhook($request) {
        $payload = $request->get_json_params();

        if (empty($payload)) {
            return new WP_Error('invalid_payload', 'Payload inválido', array('status' => 400));
        }

        // Log do webhook
        $this->log_webhook($payload);

        // Processa o evento
        $asaas = new PUS_Asaas();
        $result = $asaas->process_webhook($payload);

        return rest_ensure_response($result);
    }

    /**
     * Registra log do webhook
     */
    private function log_webhook($payload) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'pus_webhook_logs';
        
        // Cria a tabela se não existir
        $this->maybe_create_log_table();

        $wpdb->insert($table, array(
            'event' => isset($payload['event']) ? $payload['event'] : 'unknown',
            'payment_id' => isset($payload['payment']['id']) ? $payload['payment']['id'] : '',
            'payload' => json_encode($payload),
            'created_at' => current_time('mysql')
        ));
    }

    /**
     * Cria tabela de logs se necessário
     */
    private function maybe_create_log_table() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'pus_webhook_logs';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE $table (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                event varchar(100) NOT NULL,
                payment_id varchar(100) DEFAULT '',
                payload longtext,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) $charset_collate;";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
    }
}
