<?php

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Models\License;
use App\Models\Plugin;
use App\Models\ActivityLog;
use App\Models\UpdateLog;

/**
 * Controller da API de Updates (para o plugin WordPress cliente)
 */
class UpdateController extends Controller {
    
    /**
     * Obtém dados do request (JSON ou POST)
     */
    private function getInput($key = null, $default = null) {
        static $jsonData = null;
        
        // Tenta ler JSON do body
        if ($jsonData === null) {
            $rawBody = file_get_contents('php://input');
            $jsonData = json_decode($rawBody, true) ?? [];
        }
        
        // Mescla com POST e GET
        $data = array_merge($_GET, $_POST, $jsonData);
        
        if ($key === null) {
            return $data;
        }
        
        return $data[$key] ?? $default;
    }
    
    /**
     * Valida licença
     * POST /api/v1/validate-license
     */
    public function validateLicense() {
        $licenseKey = $this->getInput('license_key');
        $siteUrl = $this->getInput('site_url');
        
        if (empty($licenseKey)) {
            return $this->json([
                'success' => false,
                'message' => 'Chave de licença não informada'
            ], 400);
        }
        
        // Valida licença
        $validation = License::validate($licenseKey, $siteUrl);
        
        if (!$validation['valid']) {
            return $this->json([
                'success' => false,
                'message' => $validation['message']
            ], 403);
        }
        
        $license = $validation['license'];
        $periodLabels = License::getPeriodLabels();
        
        return $this->json([
            'success' => true,
            'message' => 'Licença válida',
            'license' => [
                'status' => $license->status,
                'period' => $license->period,
                'period_label' => $periodLabels[$license->period] ?? $license->period,
                'expires_at' => $license->expires_at
            ]
        ]);
    }
    
    /**
     * Verifica atualizações disponíveis
     * POST /api/v1/check-updates
     */
    public function checkUpdates() {
        $licenseKey = $this->getInput('license_key');
        $siteUrl = $this->getInput('site_url');
        $plugins = $this->getInput('plugins', []);
        
        if (empty($licenseKey)) {
            return $this->json([
                'success' => false,
                'message' => 'Chave de licença não informada'
            ], 400);
        }
        
        // Valida licença
        $validation = License::validate($licenseKey, $siteUrl);
        
        if (!$validation['valid']) {
            ActivityLog::licenseCheck(null, $siteUrl, false);
            return $this->json([
                'success' => false,
                'message' => $validation['message']
            ], 403);
        }
        
        $license = $validation['license'];
        
        // Log de verificação
        ActivityLog::licenseCheck($license->id, $siteUrl, true);
        
        // Licença válida = acesso a todos os plugins ativos
        $availablePlugins = Plugin::all(true);
        
        $updates = [];
        
        // $plugins vem como array [slug => version] do cliente
        foreach ($availablePlugins as $plugin) {
            $clientVersion = null;
            
            // Verifica se o cliente tem este plugin e qual versão
            if (is_array($plugins)) {
                foreach ($plugins as $slug => $version) {
                    if ($slug === $plugin->slug) {
                        $clientVersion = $version;
                        break;
                    }
                }
            }
            
            // Se o cliente tem o plugin e há versão mais nova
            if ($clientVersion !== null && version_compare($plugin->version, $clientVersion, '>')) {
                $updates[$plugin->slug] = [
                    'name' => $plugin->name,
                    'slug' => $plugin->slug,
                    'version' => $plugin->version,
                    'url' => $plugin->plugin_uri,
                    'package' => url('/api/v1/download/' . $plugin->slug . '?license_key=' . $licenseKey),
                    'icon_url' => '',
                    'banner_url' => '',
                    'requires' => $plugin->requires_wp,
                    'tested' => $plugin->tested_wp,
                    'requires_php' => $plugin->requires_php
                ];
            }
        }
        
        return $this->json([
            'success' => true,
            'updates' => $updates,
            'license' => [
                'status' => $license->status,
                'period' => $license->period,
                'expires_at' => $license->expires_at
            ]
        ]);
    }
    
    /**
     * Download do plugin
     * GET /api/v1/download/{slug}
     */
    public function download($slug) {
        $licenseKey = $this->getInput('license_key');
        $siteUrl = $this->getInput('site_url');
        
        if (empty($licenseKey)) {
            return $this->json([
                'success' => false,
                'message' => 'Chave de licença não informada'
            ], 400);
        }
        
        // Valida licença
        $validation = License::validate($licenseKey, $siteUrl);
        
        if (!$validation['valid']) {
            return $this->json([
                'success' => false,
                'message' => $validation['message']
            ], 403);
        }
        
        $license = $validation['license'];
        
        // Verifica se o plugin está disponível
        $plugin = Plugin::findBySlug($slug);
        
        if (!$plugin || !$plugin->is_active) {
            return $this->json([
                'success' => false,
                'message' => 'Plugin não encontrado'
            ], 404);
        }
        
        // Obtém caminho do arquivo
        $zipPath = Plugin::getZipPath($slug);
        
        if (!$zipPath) {
            return $this->json([
                'success' => false,
                'message' => 'Arquivo do plugin não disponível'
            ], 404);
        }
        
        // Incrementa contador de downloads
        Plugin::incrementDownloads($plugin->id);
        
        // Log de download
        ActivityLog::download($license->id, $slug);
        
        // Envia arquivo
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $plugin->slug . '-' . $plugin->version . '.zip"');
        header('Content-Length: ' . filesize($zipPath));
        header('Cache-Control: no-cache, must-revalidate');
        
        readfile($zipPath);
        exit;
    }
    
    /**
     * Verifica status da licença
     * GET /api/v1/license/status
     */
    public function licenseStatus() {
        $licenseKey = $this->getInput('license_key');
        
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
        
        $periodLabels = License::getPeriodLabels();
        
        return $this->json([
            'success' => true,
            'license' => [
                'status' => $license->status,
                'period' => $license->period,
                'period_label' => $periodLabels[$license->period] ?? $license->period,
                'expires_at' => $license->expires_at
            ]
        ]);
    }
    
    /**
     * Ativa licença em um site
     * POST /api/v1/license/activate
     */
    public function activateLicense() {
        $licenseKey = $this->getInput('license_key');
        $siteUrl = $this->getInput('site_url');
        
        if (empty($licenseKey) || empty($siteUrl)) {
            return $this->json([
                'success' => false,
                'message' => 'Dados incompletos'
            ], 400);
        }
        
        $license = License::findByKey($licenseKey);
        
        if (!$license) {
            return $this->json([
                'success' => false,
                'message' => 'Licença não encontrada'
            ], 404);
        }
        
        if ($license->status !== 'active') {
            return $this->json([
                'success' => false,
                'message' => 'Licença não está ativa'
            ], 403);
        }
        
        // Atualiza URL do site
        License::update($license->id, [
            'site_url' => $siteUrl,
            'activated_at' => date('Y-m-d H:i:s')
        ]);
        
        return $this->json([
            'success' => true,
            'message' => 'Licença ativada com sucesso'
        ]);
    }
    
    /**
     * Desativa licença de um site
     * POST /api/v1/license/deactivate
     */
    public function deactivateLicense() {
        $licenseKey = $this->getInput('license_key');
        
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
        
        License::update($license->id, [
            'site_url' => null,
            'activated_at' => null
        ]);
        
        return $this->json([
            'success' => true,
            'message' => 'Licença desativada'
        ]);
    }
    
    /**
     * Lista plugins disponíveis para a licença
     * POST /api/v1/plugins
     */
    public function listPlugins() {
        $licenseKey = $this->getInput('license_key');
        $siteUrl = $this->getInput('site_url');
        
        if (empty($licenseKey)) {
            return $this->json([
                'success' => false,
                'message' => 'Chave de licença não informada'
            ], 400);
        }
        
        // Valida licença
        $validation = License::validate($licenseKey, $siteUrl);
        
        if (!$validation['valid']) {
            return $this->json([
                'success' => false,
                'message' => $validation['message']
            ], 403);
        }
        
        // Licença válida = acesso a todos os plugins ativos
        $plugins = Plugin::all(true);
        
        $result = [];
        foreach ($plugins as $plugin) {
            $result[] = [
                'name' => $plugin->name,
                'slug' => $plugin->slug,
                'version' => $plugin->version,
                'description' => $plugin->description
            ];
        }
        
        return $this->json([
            'success' => true,
            'plugins' => $result
        ]);
    }
    
    /**
     * Informações de um plugin específico
     * POST /api/v1/plugin-info/{slug}
     */
    public function pluginInfo($slug) {
        $licenseKey = $this->getInput('license_key');
        $siteUrl = $this->getInput('site_url');
        
        if (empty($licenseKey)) {
            return $this->json([
                'success' => false,
                'message' => 'Chave de licença não informada'
            ], 400);
        }
        
        // Valida licença
        $validation = License::validate($licenseKey, $siteUrl);
        
        if (!$validation['valid']) {
            return $this->json([
                'success' => false,
                'message' => $validation['message']
            ], 403);
        }
        
        $plugin = Plugin::findBySlug($slug);
        
        if (!$plugin || !$plugin->is_active) {
            return $this->json([
                'success' => false,
                'message' => 'Plugin não encontrado'
            ], 404);
        }
        
        return $this->json([
            'success' => true,
            'plugin' => [
                'name' => $plugin->name,
                'slug' => $plugin->slug,
                'version' => $plugin->version,
                'author' => $plugin->author,
                'description' => $plugin->description,
                'changelog' => $plugin->changelog,
                'requires' => $plugin->requires_wp,
                'tested' => $plugin->tested_wp,
                'requires_php' => $plugin->requires_php
            ]
        ]);
    }
    
    /**
     * Histórico de pagamentos do cliente
     * GET /api/v1/my/payments
     */
    public function myPayments() {
        $licenseKey = $this->getInput('license_key');
        
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
        
        // Busca pagamentos da licença
        $payments = \App\Models\Payment::findByLicense($license->id);
        
        $result = [];
        $periodLabels = License::getPeriodLabels();
        
        foreach ($payments as $payment) {
            $result[] = [
                'id' => $payment->id,
                'asaas_id' => $payment->asaas_id,
                'amount' => floatval($payment->amount),
                'status' => $payment->status,
                'status_label' => $this->getPaymentStatusLabel($payment->status),
                'payment_method' => $payment->payment_method,
                'payment_method_label' => $this->getPaymentMethodLabel($payment->payment_method),
                'period' => $payment->period ?? $license->period,
                'period_label' => $periodLabels[$payment->period ?? $license->period] ?? '',
                'due_date' => $payment->due_date,
                'paid_at' => $payment->paid_at,
                'boleto_url' => $payment->boleto_url,
                'invoice_url' => $payment->invoice_url,
                'created_at' => $payment->created_at
            ];
        }
        
        return $this->json([
            'success' => true,
            'payments' => $result,
            'total' => count($result)
        ]);
    }
    
    /**
     * Histórico de atualizações/downloads do cliente
     * GET /api/v1/my/updates
     */
    public function myUpdates() {
        $licenseKey = $this->getInput('license_key');
        
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
        
        // Busca logs de download da licença
        $logs = ActivityLog::getByLicense($license->id, ['download', 'update'], 50);
        
        $result = [];
        foreach ($logs as $log) {
            $result[] = [
                'action' => $log->action,
                'plugin_slug' => $log->plugin_slug,
                'plugin_name' => $log->plugin_name ?? $log->plugin_slug,
                'version' => $log->version,
                'ip_address' => $log->ip_address,
                'created_at' => $log->created_at
            ];
        }
        
        return $this->json([
            'success' => true,
            'updates' => $result,
            'total' => count($result)
        ]);
    }
    
    /**
     * Informações completas da conta/assinatura do cliente
     * GET /api/v1/my/account
     */
    public function myAccount() {
        $licenseKey = $this->getInput('license_key');
        
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
        
        $periodLabels = License::getPeriodLabels();
        
        // Calcular dias restantes
        $daysRemaining = null;
        $isExpired = false;
        
        if ($license->expires_at) {
            $expiresAt = strtotime($license->expires_at);
            $now = time();
            $daysRemaining = floor(($expiresAt - $now) / 86400);
            $isExpired = $daysRemaining < 0;
        }
        
        // Estatísticas
        $totalPayments = \App\Models\Payment::countByLicense($license->id, 'paid');
        $totalDownloads = ActivityLog::countByLicense($license->id, 'download');
        $lastPayment = \App\Models\Payment::lastByLicense($license->id, 'paid');
        
        // Plugins disponíveis
        $availablePlugins = Plugin::all(true);
        $plugins = [];
        foreach ($availablePlugins as $plugin) {
            $plugins[] = [
                'name' => $plugin->name,
                'slug' => $plugin->slug,
                'version' => $plugin->version
            ];
        }
        
        return $this->json([
            'success' => true,
            'account' => [
                'name' => $license->client_name,
                'email' => $license->client_email,
                'license_key' => $license->license_key,
                'status' => $license->status,
                'status_label' => $this->getLicenseStatusLabel($license->status),
                'period' => $license->period,
                'period_label' => $periodLabels[$license->period] ?? $license->period,
                'is_lifetime' => $license->period === 'lifetime',
                'created_at' => $license->created_at,
                'expires_at' => $license->expires_at,
                'days_remaining' => $daysRemaining,
                'is_expired' => $isExpired,
                'is_friend' => $license->is_friend ? true : false,
                'site_url' => $license->site_url
            ],
            'stats' => [
                'total_payments' => $totalPayments,
                'total_downloads' => $totalDownloads,
                'last_payment_date' => $lastPayment ? $lastPayment->paid_at : null,
                'last_payment_amount' => $lastPayment ? floatval($lastPayment->amount) : null
            ],
            'plugins' => $plugins
        ]);
    }
    
    /**
     * Registra uma atualização feita pelo cliente
     * POST /api/v1/my/log-update
     */
    public function logUpdate() {
        $licenseKey = $this->getInput('license_key');
        $pluginSlug = $this->getInput('plugin_slug');
        $fromVersion = $this->getInput('from_version');
        $toVersion = $this->getInput('to_version');
        
        if (empty($licenseKey) || empty($pluginSlug)) {
            return $this->json([
                'success' => false,
                'message' => 'Dados incompletos'
            ], 400);
        }
        
        $license = License::findByKey($licenseKey);
        
        if (!$license) {
            return $this->json([
                'success' => false,
                'message' => 'Licença não encontrada'
            ], 404);
        }
        
        // Registra atualização
        ActivityLog::update($license->id, $pluginSlug, $fromVersion, $toVersion);
        
        return $this->json([
            'success' => true,
            'message' => 'Atualização registrada'
        ]);
    }
    
    /**
     * Helpers para labels
     */
    private function getPaymentStatusLabel($status) {
        $labels = [
            'pending' => 'Pendente',
            'paid' => 'Pago',
            'confirmed' => 'Confirmado',
            'overdue' => 'Vencido',
            'refunded' => 'Estornado',
            'cancelled' => 'Cancelado'
        ];
        return $labels[$status] ?? $status;
    }
    
    private function getPaymentMethodLabel($method) {
        $labels = [
            'pix' => 'PIX',
            'boleto' => 'Boleto Bancário',
            'credit_card' => 'Cartão de Crédito'
        ];
        return $labels[$method] ?? $method;
    }
    
    private function getLicenseStatusLabel($status) {
        $labels = [
            'active' => 'Ativa',
            'pending' => 'Pendente',
            'expired' => 'Expirada',
            'suspended' => 'Suspensa',
            'cancelled' => 'Cancelada'
        ];
        return $labels[$status] ?? $status;
    }
    
    /**
     * Registra início de uma atualização
     * POST /api/v1/update/started
     */
    public function updateStarted() {
        $licenseKey = $this->getInput('license_key');
        $pluginSlug = $this->getInput('plugin_slug');
        $fromVersion = $this->getInput('from_version');
        $toVersion = $this->getInput('to_version');
        $siteUrl = $this->getInput('site_url');
        $wpVersion = $this->getInput('wp_version');
        $phpVersion = $this->getInput('php_version');
        
        if (empty($licenseKey) || empty($pluginSlug) || empty($toVersion)) {
            return $this->json([
                'success' => false,
                'message' => 'Dados incompletos'
            ], 400);
        }
        
        $license = License::findByKey($licenseKey);
        
        if (!$license) {
            return $this->json([
                'success' => false,
                'message' => 'Licença não encontrada'
            ], 404);
        }
        
        // Cria log de atualização
        $logId = UpdateLog::create([
            'license_id' => $license->id,
            'plugin_slug' => $pluginSlug,
            'from_version' => $fromVersion,
            'to_version' => $toVersion,
            'status' => UpdateLog::STATUS_STARTED,
            'site_url' => $siteUrl,
            'wp_version' => $wpVersion,
            'php_version' => $phpVersion
        ]);
        
        // Atualiza status na licença
        License::update($license->id, [
            'update_status' => 'pending',
            'update_status_at' => date('Y-m-d H:i:s')
        ]);
        
        return $this->json([
            'success' => true,
            'log_id' => $logId,
            'message' => 'Atualização iniciada'
        ]);
    }
    
    /**
     * Registra sucesso de uma atualização
     * POST /api/v1/update/success
     */
    public function updateSuccess() {
        $licenseKey = $this->getInput('license_key');
        $logId = $this->getInput('log_id');
        $pluginSlug = $this->getInput('plugin_slug');
        $toVersion = $this->getInput('to_version');
        $healthCheckPassed = $this->getInput('health_check_passed', true);
        
        if (empty($licenseKey)) {
            return $this->json([
                'success' => false,
                'message' => 'Dados incompletos'
            ], 400);
        }
        
        $license = License::findByKey($licenseKey);
        
        if (!$license) {
            return $this->json([
                'success' => false,
                'message' => 'Licença não encontrada'
            ], 404);
        }
        
        // Atualiza log se existir
        if ($logId) {
            UpdateLog::update($logId, [
                'status' => UpdateLog::STATUS_SUCCESS,
                'health_check_passed' => $healthCheckPassed ? 1 : 0,
                'completed_at' => date('Y-m-d H:i:s')
            ]);
        } else if ($pluginSlug && $toVersion) {
            // Cria novo log de sucesso
            UpdateLog::create([
                'license_id' => $license->id,
                'plugin_slug' => $pluginSlug,
                'to_version' => $toVersion,
                'status' => UpdateLog::STATUS_SUCCESS,
                'health_check_passed' => $healthCheckPassed ? 1 : 0,
                'completed_at' => date('Y-m-d H:i:s')
            ]);
        }
        
        // Atualiza status na licença
        License::update($license->id, [
            'update_status' => 'ok',
            'update_status_at' => date('Y-m-d H:i:s'),
            'last_error_message' => null
        ]);
        
        return $this->json([
            'success' => true,
            'message' => 'Atualização concluída com sucesso'
        ]);
    }
    
    /**
     * Registra erro em uma atualização
     * POST /api/v1/update/error
     */
    public function updateError() {
        $licenseKey = $this->getInput('license_key');
        $logId = $this->getInput('log_id');
        $pluginSlug = $this->getInput('plugin_slug');
        $toVersion = $this->getInput('to_version');
        $errorMessage = $this->getInput('error_message');
        $errorType = $this->getInput('error_type');
        $healthCheckPassed = $this->getInput('health_check_passed', false);
        
        if (empty($licenseKey)) {
            return $this->json([
                'success' => false,
                'message' => 'Dados incompletos'
            ], 400);
        }
        
        $license = License::findByKey($licenseKey);
        
        if (!$license) {
            return $this->json([
                'success' => false,
                'message' => 'Licença não encontrada'
            ], 404);
        }
        
        // Atualiza log se existir
        if ($logId) {
            UpdateLog::update($logId, [
                'status' => UpdateLog::STATUS_ERROR,
                'error_message' => $errorMessage,
                'error_type' => $errorType,
                'health_check_passed' => $healthCheckPassed ? 1 : 0,
                'completed_at' => date('Y-m-d H:i:s')
            ]);
        } else if ($pluginSlug) {
            // Cria novo log de erro
            UpdateLog::create([
                'license_id' => $license->id,
                'plugin_slug' => $pluginSlug,
                'to_version' => $toVersion,
                'status' => UpdateLog::STATUS_ERROR,
                'error_message' => $errorMessage,
                'error_type' => $errorType,
                'health_check_passed' => $healthCheckPassed ? 1 : 0,
                'completed_at' => date('Y-m-d H:i:s')
            ]);
        }
        
        // Atualiza status na licença
        License::update($license->id, [
            'update_status' => 'error',
            'update_status_at' => date('Y-m-d H:i:s'),
            'last_error_message' => $errorMessage
        ]);
        
        return $this->json([
            'success' => true,
            'message' => 'Erro registrado'
        ]);
    }
    
    /**
     * Registra rollback de uma atualização
     * POST /api/v1/update/rollback
     */
    public function updateRollback() {
        $licenseKey = $this->getInput('license_key');
        $logId = $this->getInput('log_id');
        $pluginSlug = $this->getInput('plugin_slug');
        $fromVersion = $this->getInput('from_version');
        $toVersion = $this->getInput('to_version');
        $errorMessage = $this->getInput('error_message');
        $automatic = $this->getInput('automatic', true);
        
        if (empty($licenseKey)) {
            return $this->json([
                'success' => false,
                'message' => 'Dados incompletos'
            ], 400);
        }
        
        $license = License::findByKey($licenseKey);
        
        if (!$license) {
            return $this->json([
                'success' => false,
                'message' => 'Licença não encontrada'
            ], 404);
        }
        
        // Atualiza log se existir
        if ($logId) {
            UpdateLog::update($logId, [
                'status' => UpdateLog::STATUS_ROLLBACK,
                'error_message' => $errorMessage,
                'rollback_performed' => 1,
                'completed_at' => date('Y-m-d H:i:s')
            ]);
        } else if ($pluginSlug) {
            // Cria novo log de rollback
            UpdateLog::create([
                'license_id' => $license->id,
                'plugin_slug' => $pluginSlug,
                'from_version' => $toVersion, // Voltou para...
                'to_version' => $fromVersion, // ...a versão anterior
                'status' => UpdateLog::STATUS_ROLLBACK,
                'error_message' => $errorMessage ?? 'Rollback ' . ($automatic ? 'automático' : 'manual'),
                'rollback_performed' => 1,
                'completed_at' => date('Y-m-d H:i:s')
            ]);
        }
        
        // Atualiza status na licença
        License::update($license->id, [
            'update_status' => 'rollback',
            'update_status_at' => date('Y-m-d H:i:s'),
            'last_error_message' => $errorMessage ?? 'Rollback realizado'
        ]);
        
        return $this->json([
            'success' => true,
            'message' => 'Rollback registrado'
        ]);
    }
}
