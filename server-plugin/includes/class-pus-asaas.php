<?php
/**
 * Classe para integração com o Asaas
 */

if (!defined('ABSPATH')) {
    exit;
}

class PUS_Asaas {

    private $api_key;
    private $api_url;
    private $sandbox;

    public function __construct() {
        $this->sandbox = get_option('pus_asaas_sandbox', true);
        $this->api_key = get_option('pus_asaas_api_key', '');
        $this->api_url = $this->sandbox 
            ? 'https://sandbox.asaas.com/api/v3/' 
            : 'https://api.asaas.com/v3/';
    }

    /**
     * Faz uma requisição para a API do Asaas
     */
    private function request($endpoint, $method = 'GET', $data = null) {
        $url = $this->api_url . $endpoint;

        $args = array(
            'method' => $method,
            'timeout' => 30,
            'headers' => array(
                'Content-Type' => 'application/json',
                'access_token' => $this->api_key
            )
        );

        if ($data && in_array($method, array('POST', 'PUT'))) {
            $args['body'] = json_encode($data);
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);

        $code = wp_remote_retrieve_response_code($response);
        
        if ($code >= 400) {
            $message = isset($result['errors'][0]['description']) 
                ? $result['errors'][0]['description'] 
                : 'Erro na API do Asaas';
            return new WP_Error('asaas_error', $message, $result);
        }

        return $result;
    }

    /**
     * Cria ou atualiza um cliente no Asaas
     */
    public function create_customer($data) {
        // Verifica se o cliente já existe pelo CPF/CNPJ
        $existing = $this->find_customer_by_document($data['cpfCnpj']);
        
        if ($existing && !is_wp_error($existing)) {
            return $existing;
        }

        return $this->request('customers', 'POST', array(
            'name' => $data['name'],
            'email' => $data['email'],
            'cpfCnpj' => preg_replace('/[^0-9]/', '', $data['cpfCnpj']),
            'phone' => isset($data['phone']) ? preg_replace('/[^0-9]/', '', $data['phone']) : '',
            'externalReference' => isset($data['externalReference']) ? $data['externalReference'] : ''
        ));
    }

    /**
     * Busca cliente por CPF/CNPJ
     */
    public function find_customer_by_document($cpfCnpj) {
        $cpfCnpj = preg_replace('/[^0-9]/', '', $cpfCnpj);
        $result = $this->request('customers?cpfCnpj=' . $cpfCnpj);

        if (is_wp_error($result)) {
            return $result;
        }

        if (!empty($result['data']) && count($result['data']) > 0) {
            return $result['data'][0];
        }

        return null;
    }

    /**
     * Cria uma cobrança única
     */
    public function create_payment($data) {
        return $this->request('payments', 'POST', array(
            'customer' => $data['customer_id'],
            'billingType' => $data['billing_type'], // BOLETO, CREDIT_CARD, PIX
            'value' => $data['value'],
            'dueDate' => $data['due_date'],
            'description' => $data['description'],
            'externalReference' => isset($data['external_reference']) ? $data['external_reference'] : ''
        ));
    }

    /**
     * Cria uma assinatura recorrente
     */
    public function create_subscription($data) {
        return $this->request('subscriptions', 'POST', array(
            'customer' => $data['customer_id'],
            'billingType' => $data['billing_type'],
            'value' => $data['value'],
            'nextDueDate' => $data['next_due_date'],
            'cycle' => $data['cycle'], // MONTHLY, YEARLY
            'description' => $data['description'],
            'externalReference' => isset($data['external_reference']) ? $data['external_reference'] : ''
        ));
    }

    /**
     * Cancela uma assinatura
     */
    public function cancel_subscription($subscription_id) {
        return $this->request('subscriptions/' . $subscription_id, 'DELETE');
    }

    /**
     * Obtém uma cobrança
     */
    public function get_payment($payment_id) {
        return $this->request('payments/' . $payment_id);
    }

    /**
     * Obtém o link de pagamento (checkout)
     */
    public function get_payment_link($payment_id) {
        $payment = $this->get_payment($payment_id);
        
        if (is_wp_error($payment)) {
            return $payment;
        }

        return isset($payment['invoiceUrl']) ? $payment['invoiceUrl'] : '';
    }

    /**
     * Obtém QR Code PIX
     */
    public function get_pix_qrcode($payment_id) {
        return $this->request('payments/' . $payment_id . '/pixQrCode');
    }

    /**
     * Obtém linha digitável do boleto
     */
    public function get_boleto_line($payment_id) {
        return $this->request('payments/' . $payment_id . '/identificationField');
    }

    /**
     * Processa webhook do Asaas
     */
    public function process_webhook($payload) {
        $event = isset($payload['event']) ? $payload['event'] : '';
        $payment = isset($payload['payment']) ? $payload['payment'] : array();

        switch ($event) {
            case 'PAYMENT_CONFIRMED':
            case 'PAYMENT_RECEIVED':
                return $this->handle_payment_confirmed($payment);
            
            case 'PAYMENT_OVERDUE':
                return $this->handle_payment_overdue($payment);
            
            case 'PAYMENT_REFUNDED':
                return $this->handle_payment_refunded($payment);
            
            default:
                return array('status' => 'ignored', 'event' => $event);
        }
    }

    /**
     * Processa pagamento confirmado
     */
    private function handle_payment_confirmed($payment) {
        global $wpdb;
        
        $external_ref = isset($payment['externalReference']) ? $payment['externalReference'] : '';
        
        if (empty($external_ref)) {
            return array('status' => 'error', 'message' => 'External reference not found');
        }

        // O external_reference deve ser o ID da licença
        $license_id = intval($external_ref);
        $table = $wpdb->prefix . 'pus_licenses';

        // Ativa a licença
        $updated = $wpdb->update(
            $table,
            array(
                'is_active' => 1,
                'payment_status' => 'paid',
                'payment_id' => $payment['id'],
                'paid_at' => current_time('mysql')
            ),
            array('id' => $license_id)
        );

        if ($updated) {
            // Envia e-mail de confirmação
            $license = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table WHERE id = %d",
                $license_id
            ));

            if ($license && $license->client_email) {
                $this->send_license_email($license);
            }

            do_action('pus_payment_confirmed', $license_id, $payment);

            return array('status' => 'success', 'license_id' => $license_id);
        }

        return array('status' => 'error', 'message' => 'License not found');
    }

    /**
     * Processa pagamento vencido
     */
    private function handle_payment_overdue($payment) {
        global $wpdb;
        
        $external_ref = isset($payment['externalReference']) ? $payment['externalReference'] : '';
        
        if (empty($external_ref)) {
            return array('status' => 'error', 'message' => 'External reference not found');
        }

        $license_id = intval($external_ref);
        $table = $wpdb->prefix . 'pus_licenses';

        $wpdb->update(
            $table,
            array('payment_status' => 'overdue'),
            array('id' => $license_id)
        );

        do_action('pus_payment_overdue', $license_id, $payment);

        return array('status' => 'success', 'license_id' => $license_id);
    }

    /**
     * Processa reembolso
     */
    private function handle_payment_refunded($payment) {
        global $wpdb;
        
        $external_ref = isset($payment['externalReference']) ? $payment['externalReference'] : '';
        
        if (empty($external_ref)) {
            return array('status' => 'error', 'message' => 'External reference not found');
        }

        $license_id = intval($external_ref);
        $table = $wpdb->prefix . 'pus_licenses';

        // Desativa a licença
        $wpdb->update(
            $table,
            array(
                'is_active' => 0,
                'payment_status' => 'refunded'
            ),
            array('id' => $license_id)
        );

        do_action('pus_payment_refunded', $license_id, $payment);

        return array('status' => 'success', 'license_id' => $license_id);
    }

    /**
     * Envia e-mail com dados da licença
     */
    private function send_license_email($license) {
        $to = $license->client_email;
        $subject = sprintf(__('[%s] Sua licença foi ativada!', 'premium-updates-server'), get_bloginfo('name'));
        
        $message = sprintf(
            __("Olá %s,\n\nSua licença foi ativada com sucesso!\n\n" .
            "Chave de Licença: %s\n" .
            "Site autorizado: %s\n\n" .
            "Guarde esta chave em local seguro.\n\n" .
            "Atenciosamente,\n%s", 'premium-updates-server'),
            $license->client_name,
            $license->license_key,
            $license->site_url,
            get_bloginfo('name')
        );

        wp_mail($to, $subject, $message);
    }

    /**
     * Cria cobrança para nova licença
     */
    public function create_license_payment($license_data, $plan) {
        // Cria o cliente
        $customer = $this->create_customer(array(
            'name' => $license_data['client_name'],
            'email' => $license_data['client_email'],
            'cpfCnpj' => $license_data['cpf_cnpj']
        ));

        if (is_wp_error($customer)) {
            return $customer;
        }

        // Cria a licença pendente no banco
        global $wpdb;
        $table = $wpdb->prefix . 'pus_licenses';

        $license_key = PUS_Database::generate_license_key();
        
        $wpdb->insert($table, array(
            'license_key' => $license_key,
            'client_name' => $license_data['client_name'],
            'client_email' => $license_data['client_email'],
            'site_url' => $license_data['site_url'],
            'is_active' => 0, // Ativa após pagamento
            'payment_status' => 'pending',
            'plan_id' => $plan['id'],
            'asaas_customer_id' => $customer['id'],
            'max_activations' => isset($plan['max_sites']) ? $plan['max_sites'] : 1,
            'expires_at' => $plan['type'] === 'yearly' 
                ? date('Y-m-d H:i:s', strtotime('+1 year')) 
                : null
        ));

        $license_id = $wpdb->insert_id;

        // Cria a cobrança
        if ($plan['type'] === 'recurring') {
            $payment = $this->create_subscription(array(
                'customer_id' => $customer['id'],
                'billing_type' => 'UNDEFINED', // Permite escolher no checkout
                'value' => $plan['price'],
                'next_due_date' => date('Y-m-d'),
                'cycle' => $plan['cycle'],
                'description' => $plan['name'] . ' - ' . $license_data['site_url'],
                'external_reference' => $license_id
            ));
        } else {
            $payment = $this->create_payment(array(
                'customer_id' => $customer['id'],
                'billing_type' => 'UNDEFINED',
                'value' => $plan['price'],
                'due_date' => date('Y-m-d', strtotime('+3 days')),
                'description' => $plan['name'] . ' - ' . $license_data['site_url'],
                'external_reference' => $license_id
            ));
        }

        if (is_wp_error($payment)) {
            // Remove a licença se falhou
            $wpdb->delete($table, array('id' => $license_id));
            return $payment;
        }

        // Atualiza a licença com o ID do pagamento
        $wpdb->update(
            $table,
            array('payment_id' => $payment['id']),
            array('id' => $license_id)
        );

        return array(
            'license_id' => $license_id,
            'license_key' => $license_key,
            'payment' => $payment,
            'checkout_url' => $payment['invoiceUrl']
        );
    }

    /**
     * Verifica se a API está configurada
     */
    public function is_configured() {
        return !empty($this->api_key);
    }

    /**
     * Testa a conexão com a API
     */
    public function test_connection() {
        $result = $this->request('finance/balance');
        
        if (is_wp_error($result)) {
            return $result;
        }

        return true;
    }
}
