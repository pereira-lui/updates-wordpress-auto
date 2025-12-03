<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\License;
use App\Models\Payment;
use App\Core\Database;
use App\Services\AsaasService;

/**
 * Controller do Checkout Público (usado pelo plugin WordPress cliente)
 */
class CheckoutController extends Controller {
    
    /**
     * Página informativa (landing page simples)
     */
    public function index() {
        // Pega preços das configurações
        $prices = $this->getPrices();
        
        return $this->view('public/index', [
            'prices' => $prices
        ]);
    }
    
    /**
     * Obtém preços das configurações
     */
    private function getPrices() {
        $settings = Database::selectOne("SELECT value FROM settings WHERE `key` = 'general'");
        $data = $settings ? json_decode($settings->value, true) : [];
        
        return [
            'monthly' => floatval($data['price_monthly'] ?? 29),
            'quarterly' => floatval($data['price_quarterly'] ?? 79),
            'semiannual' => floatval($data['price_semiannual'] ?? 149),
            'yearly' => floatval($data['price_yearly'] ?? 249)
        ];
    }
    
    /**
     * API: Obtém preços disponíveis (para o plugin WP)
     * GET /api/v1/subscription/prices
     */
    public function getPricesApi() {
        $prices = $this->getPrices();
        
        $periodLabels = License::getPeriodLabels();
        
        $result = [];
        foreach ($prices as $period => $price) {
            $result[$period] = [
                'price' => $price,
                'label' => $periodLabels[$period] ?? $period,
                'days' => License::getPeriodDays($period)
            ];
        }
        
        return $this->json([
            'success' => true,
            'data' => $result
        ]);
    }
    
    /**
     * API: Inicia assinatura (cria licença + cobrança)
     * POST /api/v1/subscription/create
     */
    public function createSubscription() {
        $input = $this->getJsonInput();
        
        $name = $input['name'] ?? '';
        $email = $input['email'] ?? '';
        $document = $input['document'] ?? '';
        $siteUrl = $input['site_url'] ?? '';
        $period = $input['period'] ?? 'monthly';
        $paymentMethod = $input['payment_method'] ?? 'pix';
        $generateInvoice = !empty($input['generate_invoice']) ? 1 : 0;
        
        // Validação
        if (empty($name) || empty($email)) {
            return $this->json([
                'success' => false,
                'message' => 'Nome e email são obrigatórios'
            ], 400);
        }
        
        // Se solicitar nota fiscal, documento é obrigatório
        if ($generateInvoice && empty($document)) {
            return $this->json([
                'success' => false,
                'message' => 'CPF/CNPJ é obrigatório para emissão de nota fiscal'
            ], 400);
        }
        
        if (!in_array($period, ['monthly', 'quarterly', 'semiannual', 'yearly'])) {
            return $this->json([
                'success' => false,
                'message' => 'Período inválido'
            ], 400);
        }
        
        // Obtém preço
        $prices = $this->getPrices();
        $price = $prices[$period] ?? 29;
        
        // Cria cobrança no Asaas primeiro para garantir que funcione
        $asaas = new AsaasService();
        
        // Cria ou busca cliente
        $customer = $asaas->createCustomer([
            'name' => $name,
            'email' => $email,
            'cpfCnpj' => preg_replace('/[^0-9]/', '', $document)
        ]);
        
        if (isset($customer['error'])) {
            return $this->json([
                'success' => false,
                'message' => 'Erro ao criar cliente: ' . ($customer['message'] ?? 'Erro desconhecido')
            ], 500);
        }
        
        $periodLabels = License::getPeriodLabels();
        $description = 'Assinatura ' . ($periodLabels[$period] ?? $period) . ' - Luia Updates';
        
        $billingType = strtoupper($paymentMethod);
        
        // Cria licença pendente
        $licenseId = License::create([
            'client_name' => $name,
            'client_email' => $email,
            'client_document' => $document,
            'site_url' => $siteUrl,
            'period' => $period,
            'status' => License::STATUS_PENDING
        ]);
        
        $license = License::find($licenseId);
        
        // Cria cobrança
        $payment = $asaas->createPayment([
            'customer' => $customer['id'],
            'billingType' => $billingType,
            'value' => $price,
            'description' => $description,
            'externalReference' => (string) $licenseId,
            'dueDate' => date('Y-m-d', strtotime('+3 days'))
        ]);
        
        if (isset($payment['error']) || !isset($payment['id'])) {
            // Remove licença criada se falhar
            License::delete($licenseId);
            
            return $this->json([
                'success' => false,
                'message' => 'Erro ao criar cobrança: ' . ($payment['message'] ?? 'Erro desconhecido')
            ], 500);
        }
        
        // Salva pagamento
        Payment::create([
            'license_id' => $licenseId,
            'asaas_id' => $payment['id'],
            'amount' => $price,
            'status' => 'pending',
            'payment_method' => $paymentMethod,
            'due_date' => $payment['dueDate'],
            'pix_code' => null,
            'boleto_url' => $payment['bankSlipUrl'] ?? null,
            'generate_invoice' => $generateInvoice,
            'invoice_status' => $generateInvoice ? Payment::INVOICE_PENDING : null,
            'raw_data' => json_encode($payment)
        ]);
        
        // Se for PIX, busca QR Code
        $pixData = null;
        if ($paymentMethod === 'pix') {
            $pixData = $asaas->getPixQrCode($payment['id']);
        }
        
        return $this->json([
            'success' => true,
            'license_id' => $licenseId,
            'payment_id' => $payment['id'],
            'payment_url' => $payment['invoiceUrl'] ?? null,
            'boleto_url' => $payment['bankSlipUrl'] ?? null,
            'pix' => $pixData ? [
                'qrcode' => $pixData['encodedImage'] ?? null,
                'payload' => $pixData['payload'] ?? null,
                'expiration' => $pixData['expirationDate'] ?? null
            ] : null
        ]);
    }
    
    /**
     * API: Renova assinatura
     * POST /api/v1/subscription/renew
     */
    public function renewSubscription() {
        $input = $this->getJsonInput();
        
        $licenseKey = $input['license_key'] ?? '';
        $period = $input['period'] ?? null;
        $paymentMethod = $input['payment_method'] ?? 'pix';
        $generateInvoice = !empty($input['generate_invoice']) ? 1 : 0;
        
        if (empty($licenseKey)) {
            return $this->json([
                'success' => false,
                'message' => 'Chave de licença não informada'
            ], 400);
        }
        
        $license = License::findByKey($licenseKey);
        
        if (!$license) {
            return $this->json([
                'success' => false,
                'message' => 'Licença não encontrada'
            ], 404);
        }
        
        // Se solicitar nota fiscal, documento é obrigatório
        if ($generateInvoice && empty($license->client_document)) {
            return $this->json([
                'success' => false,
                'message' => 'CPF/CNPJ não cadastrado. Atualize seus dados para emitir nota fiscal.'
            ], 400);
        }
        
        // Usa o período atual se não especificado
        if (!$period) {
            $period = $license->period ?? 'monthly';
        }
        
        // Obtém preço
        $prices = $this->getPrices();
        $price = $prices[$period] ?? 29;
        
        // Cria cobrança no Asaas
        $asaas = new AsaasService();
        
        // Cria ou busca cliente
        $customer = $asaas->createCustomer([
            'name' => $license->client_name,
            'email' => $license->client_email,
            'cpfCnpj' => preg_replace('/[^0-9]/', '', $license->client_document ?? '')
        ]);
        
        if (isset($customer['error'])) {
            return $this->json([
                'success' => false,
                'message' => 'Erro ao criar cliente: ' . ($customer['message'] ?? 'Erro desconhecido')
            ], 500);
        }
        
        $periodLabels = License::getPeriodLabels();
        $description = 'Renovação ' . ($periodLabels[$period] ?? $period) . ' - Luia Updates';
        
        $billingType = strtoupper($paymentMethod);
        $payment = $asaas->createPayment([
            'customer' => $customer['id'],
            'billingType' => $billingType,
            'value' => $price,
            'description' => $description,
            'externalReference' => (string) $license->id,
            'dueDate' => date('Y-m-d', strtotime('+3 days'))
        ]);
        
        if (isset($payment['error']) || !isset($payment['id'])) {
            return $this->json([
                'success' => false,
                'message' => 'Erro ao criar cobrança: ' . ($payment['message'] ?? 'Erro desconhecido')
            ], 500);
        }
        
        // Atualiza período da licença se diferente
        if ($period !== $license->period) {
            License::update($license->id, ['period' => $period]);
        }
        
        // Salva pagamento
        Payment::create([
            'license_id' => $license->id,
            'asaas_id' => $payment['id'],
            'amount' => $price,
            'status' => 'pending',
            'payment_method' => $paymentMethod,
            'due_date' => $payment['dueDate'],
            'pix_code' => null,
            'boleto_url' => $payment['bankSlipUrl'] ?? null,
            'generate_invoice' => $generateInvoice,
            'invoice_status' => $generateInvoice ? Payment::INVOICE_PENDING : null,
            'raw_data' => json_encode($payment)
        ]);
        
        // Se for PIX, busca QR Code
        $pixData = null;
        if ($paymentMethod === 'pix') {
            $pixData = $asaas->getPixQrCode($payment['id']);
        }
        
        return $this->json([
            'success' => true,
            'payment_id' => $payment['id'],
            'payment_url' => $payment['invoiceUrl'] ?? null,
            'boleto_url' => $payment['bankSlipUrl'] ?? null,
            'pix' => $pixData ? [
                'qrcode' => $pixData['encodedImage'] ?? null,
                'payload' => $pixData['payload'] ?? null,
                'expiration' => $pixData['expirationDate'] ?? null
            ] : null
        ]);
    }
    
    /**
     * API: Verifica status de pagamento
     * GET /api/v1/subscription/status/{payment_id}
     */
    public function checkPaymentStatus($paymentId) {
        $payment = Payment::findByAsaasId($paymentId);
        
        if (!$payment) {
            return $this->json([
                'success' => false,
                'message' => 'Pagamento não encontrado'
            ], 404);
        }
        
        $license = License::find($payment->license_id);
        
        return $this->json([
            'success' => true,
            'status' => $payment->status,
            'license_status' => $license ? $license->status : null,
            'license_key' => ($license && $license->status === 'active') ? $license->license_key : null
        ]);
    }
    
    /**
     * Obtém dados JSON do body
     */
    private function getJsonInput() {
        $rawBody = file_get_contents('php://input');
        return json_decode($rawBody, true) ?? [];
    }
}
