<?php

namespace App\Services;

/**
 * Serviço de integração com Asaas
 */
class AsaasService {
    
    private $apiKey;
    private $baseUrl;
    
    public function __construct() {
        $this->apiKey = config('asaas.api_key');
        $this->baseUrl = config('asaas.sandbox') 
            ? 'https://sandbox.asaas.com/api/v3'
            : 'https://api.asaas.com/v3';
    }
    
    /**
     * Cria ou busca cliente no Asaas
     */
    public function createCustomer($data) {
        // Busca cliente por CPF/CNPJ
        if (!empty($data['cpfCnpj'])) {
            $existing = $this->request('GET', '/customers', [
                'cpfCnpj' => $data['cpfCnpj']
            ]);
            
            if (!empty($existing['data'][0])) {
                return $existing['data'][0];
            }
        }
        
        // Busca por email
        $existing = $this->request('GET', '/customers', [
            'email' => $data['email']
        ]);
        
        if (!empty($existing['data'][0])) {
            return $existing['data'][0];
        }
        
        // Cria novo cliente
        return $this->request('POST', '/customers', $data);
    }
    
    /**
     * Cria cobrança
     */
    public function createPayment($data) {
        return $this->request('POST', '/payments', $data);
    }
    
    /**
     * Busca cobrança
     */
    public function getPayment($id) {
        return $this->request('GET', '/payments/' . $id);
    }
    
    /**
     * Lista cobranças de um cliente
     */
    public function getPaymentsByCustomer($customerId) {
        return $this->request('GET', '/payments', [
            'customer' => $customerId
        ]);
    }
    
    /**
     * Cancela cobrança
     */
    public function cancelPayment($id) {
        return $this->request('DELETE', '/payments/' . $id);
    }
    
    /**
     * Reembolsa cobrança
     */
    public function refundPayment($id, $value = null) {
        $data = [];
        if ($value) {
            $data['value'] = $value;
        }
        return $this->request('POST', '/payments/' . $id . '/refund', $data);
    }
    
    /**
     * Obtém QR Code PIX
     */
    public function getPixQrCode($paymentId) {
        return $this->request('GET', '/payments/' . $paymentId . '/pixQrCode');
    }
    
    /**
     * Obtém linha digitável do boleto
     */
    public function getBoletoInfo($paymentId) {
        return $this->request('GET', '/payments/' . $paymentId . '/identificationField');
    }
    
    /**
     * Cria assinatura recorrente
     */
    public function createSubscription($data) {
        return $this->request('POST', '/subscriptions', $data);
    }
    
    /**
     * Cancela assinatura
     */
    public function cancelSubscription($id) {
        return $this->request('DELETE', '/subscriptions/' . $id);
    }
    
    /**
     * Faz requisição à API
     */
    private function request($method, $endpoint, $data = []) {
        $url = $this->baseUrl . $endpoint;
        
        if ($method === 'GET' && !empty($data)) {
            $url .= '?' . http_build_query($data);
        }
        
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'access_token: ' . $this->apiKey
            ]
        ]);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error) {
            return ['error' => $error];
        }
        
        $decoded = json_decode($response, true);
        
        if ($httpCode >= 400) {
            return [
                'error' => true,
                'httpCode' => $httpCode,
                'errors' => $decoded['errors'] ?? [],
                'message' => $decoded['errors'][0]['description'] ?? 'Erro desconhecido'
            ];
        }
        
        return $decoded;
    }
}
