<?php

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Models\License;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\ActivityLog;

/**
 * Controller de Webhooks (Asaas)
 */
class WebhookController extends Controller {
    
    /**
     * Webhook do Asaas
     * POST /api/v1/webhook/asaas
     */
    public function asaas() {
        // Lê o payload
        $payload = file_get_contents('php://input');
        $data = json_decode($payload, true);
        
        if (!$data) {
            return $this->json(['error' => 'Payload inválido'], 400);
        }
        
        // Log do webhook
        ActivityLog::log(ActivityLog::TYPE_WEBHOOK, 'Webhook Asaas recebido', [
            'event' => $data['event'] ?? 'unknown',
            'payment_id' => $data['payment']['id'] ?? null
        ]);
        
        $event = $data['event'] ?? '';
        
        switch ($event) {
            case 'PAYMENT_CREATED':
                $this->handlePaymentCreated($data['payment']);
                break;
                
            case 'PAYMENT_CONFIRMED':
            case 'PAYMENT_RECEIVED':
                $this->handlePaymentConfirmed($data['payment']);
                break;
                
            case 'PAYMENT_OVERDUE':
                $this->handlePaymentOverdue($data['payment']);
                break;
                
            case 'PAYMENT_REFUNDED':
                $this->handlePaymentRefunded($data['payment']);
                break;
                
            case 'PAYMENT_DELETED':
            case 'PAYMENT_RESTORED':
                // Apenas log
                break;
        }
        
        return $this->json(['success' => true]);
    }
    
    /**
     * Pagamento criado
     */
    private function handlePaymentCreated($paymentData) {
        $asaasId = $paymentData['id'];
        
        // Verifica se já existe
        $existing = Payment::findByAsaasId($asaasId);
        if ($existing) {
            return;
        }
        
        // Busca licença pelo externalReference (license_id)
        $licenseId = $paymentData['externalReference'] ?? null;
        
        Payment::create([
            'license_id' => $licenseId,
            'asaas_id' => $asaasId,
            'amount' => $paymentData['value'],
            'status' => 'pending',
            'payment_method' => $this->mapPaymentMethod($paymentData['billingType']),
            'due_date' => $paymentData['dueDate'] ?? null,
            'pix_code' => $paymentData['pixQrCodeUrl'] ?? null,
            'boleto_url' => $paymentData['bankSlipUrl'] ?? null,
            'raw_data' => json_encode($paymentData)
        ]);
    }
    
    /**
     * Pagamento confirmado/recebido
     */
    private function handlePaymentConfirmed($paymentData) {
        $asaasId = $paymentData['id'];
        $payment = Payment::findByAsaasId($asaasId);
        
        if (!$payment) {
            // Cria o pagamento se não existir
            $this->handlePaymentCreated($paymentData);
            $payment = Payment::findByAsaasId($asaasId);
        }
        
        // Atualiza status
        Payment::updateByAsaasId($asaasId, [
            'status' => 'confirmed',
            'paid_at' => date('Y-m-d H:i:s'),
            'raw_data' => json_encode($paymentData)
        ]);
        
        // Ativa a licença
        if ($payment && $payment->license_id) {
            $license = License::find($payment->license_id);
            
            if ($license && $license->status === 'pending') {
                // Calcula expiração baseada no plano
                $expiresAt = null;
                if ($license->plan_id) {
                    $plan = Plan::find($license->plan_id);
                    if ($plan) {
                        $expiresAt = Plan::calculateExpiration($plan->period);
                    }
                }
                
                License::update($license->id, [
                    'status' => 'active',
                    'expires_at' => $expiresAt,
                    'activated_at' => date('Y-m-d H:i:s')
                ]);
                
                // Log
                ActivityLog::payment($license->id, $paymentData['value'], 'confirmed');
            }
        }
    }
    
    /**
     * Pagamento vencido
     */
    private function handlePaymentOverdue($paymentData) {
        $asaasId = $paymentData['id'];
        
        Payment::updateByAsaasId($asaasId, [
            'status' => 'overdue',
            'raw_data' => json_encode($paymentData)
        ]);
    }
    
    /**
     * Pagamento reembolsado
     */
    private function handlePaymentRefunded($paymentData) {
        $asaasId = $paymentData['id'];
        $payment = Payment::findByAsaasId($asaasId);
        
        Payment::updateByAsaasId($asaasId, [
            'status' => 'refunded',
            'raw_data' => json_encode($paymentData)
        ]);
        
        // Cancela a licença
        if ($payment && $payment->license_id) {
            License::update($payment->license_id, [
                'status' => 'cancelled'
            ]);
            
            ActivityLog::payment($payment->license_id, $paymentData['value'], 'refunded');
        }
    }
    
    /**
     * Mapeia método de pagamento do Asaas
     */
    private function mapPaymentMethod($billingType) {
        $map = [
            'BOLETO' => 'boleto',
            'CREDIT_CARD' => 'credit_card',
            'PIX' => 'pix',
            'UNDEFINED' => 'other'
        ];
        
        return $map[$billingType] ?? 'other';
    }
}
