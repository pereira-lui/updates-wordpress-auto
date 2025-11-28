<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\License;
use App\Models\Plan;
use App\Models\Payment;
use App\Services\AsaasService;

/**
 * Controller do Checkout Público
 */
class CheckoutController extends Controller {
    
    /**
     * Página de planos
     */
    public function plans() {
        $plans = Plan::all(true);
        
        return $this->view('public/plans', [
            'plans' => $plans
        ]);
    }
    
    /**
     * Formulário de checkout
     */
    public function show($planSlug) {
        $plan = Plan::findBySlug($planSlug);
        
        if (!$plan || !$plan->is_active) {
            flash('error', 'Plano não encontrado');
            redirect('/plans');
        }
        
        return $this->view('public/checkout', [
            'plan' => $plan
        ]);
    }
    
    /**
     * Processa o checkout
     */
    public function process($planSlug) {
        $plan = Plan::findBySlug($planSlug);
        
        if (!$plan || !$plan->is_active) {
            flash('error', 'Plano não encontrado');
            redirect('/plans');
        }
        
        $errors = $this->validate([
            'name' => 'required',
            'email' => 'required|email',
            'payment_method' => 'required'
        ]);
        
        if (!empty($errors)) {
            flash('error', 'Preencha todos os campos corretamente');
            redirect('/checkout/' . $planSlug);
        }
        
        // Cria licença pendente
        $licenseId = License::create([
            'client_name' => $_POST['name'],
            'client_email' => $_POST['email'],
            'client_document' => $_POST['document'] ?? null,
            'site_url' => $_POST['site_url'] ?? null,
            'type' => License::TYPE_PAID,
            'plan_id' => $plan->id,
            'status' => License::STATUS_PENDING
        ]);
        
        $license = License::find($licenseId);
        
        // Cria cobrança no Asaas
        $asaas = new AsaasService();
        
        $billingType = strtoupper($_POST['payment_method']);
        $payment = $asaas->createPaymentForLicense($license, $plan, $billingType);
        
        if (isset($payment['error'])) {
            flash('error', 'Erro ao criar cobrança: ' . ($payment['message'] ?? 'Erro desconhecido'));
            redirect('/checkout/' . $planSlug);
        }
        
        // Salva pagamento
        Payment::create([
            'license_id' => $licenseId,
            'asaas_id' => $payment['id'],
            'amount' => $plan->price,
            'status' => 'pending',
            'payment_method' => $_POST['payment_method'],
            'due_date' => $payment['dueDate'],
            'pix_code' => null,
            'boleto_url' => $payment['bankSlipUrl'] ?? null,
            'raw_data' => json_encode($payment)
        ]);
        
        // Redireciona para página de pagamento
        redirect('/checkout/payment/' . $licenseId);
    }
    
    /**
     * Página de pagamento
     */
    public function payment($licenseId) {
        $license = License::find($licenseId);
        
        if (!$license) {
            flash('error', 'Licença não encontrada');
            redirect('/plans');
        }
        
        // Busca pagamento
        $payments = Payment::all(['license_id' => $licenseId]);
        $payment = $payments[0] ?? null;
        
        if (!$payment) {
            flash('error', 'Pagamento não encontrado');
            redirect('/plans');
        }
        
        // Se for PIX, busca QR Code
        $pixQrCode = null;
        if ($payment->payment_method === 'pix' && $payment->asaas_id) {
            $asaas = new AsaasService();
            $pixData = $asaas->getPixQrCode($payment->asaas_id);
            $pixQrCode = $pixData ?? null;
        }
        
        $plan = Plan::find($license->plan_id);
        
        return $this->view('public/payment', [
            'license' => $license,
            'payment' => $payment,
            'plan' => $plan,
            'pixQrCode' => $pixQrCode
        ]);
    }
    
    /**
     * Verifica status do pagamento (AJAX)
     */
    public function checkStatus($licenseId) {
        $license = License::find($licenseId);
        
        if (!$license) {
            return $this->json(['error' => 'Licença não encontrada'], 404);
        }
        
        return $this->json([
            'status' => $license->status,
            'license_key' => $license->status === 'active' ? $license->license_key : null
        ]);
    }
    
    /**
     * Página de sucesso
     */
    public function success($licenseId) {
        $license = License::find($licenseId);
        
        if (!$license || $license->status !== 'active') {
            flash('error', 'Licença não encontrada ou não ativada');
            redirect('/plans');
        }
        
        $plan = Plan::find($license->plan_id);
        
        return $this->view('public/success', [
            'license' => $license,
            'plan' => $plan
        ]);
    }
}
