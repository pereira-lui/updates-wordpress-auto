<?php

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Models\License;
use App\Models\Plugin;
use App\Models\Plan;
use App\Models\ActivityLog;

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
        
        return $this->json([
            'success' => true,
            'message' => 'Licença válida',
            'license' => [
                'status' => $license->status,
                'type' => $license->type,
                'expires_at' => $license->expires_at,
                'plan' => $license->plan_id ? (Plan::find($license->plan_id)->name ?? null) : null
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
        
        // Obtém plugins do plano da licença
        if ($license->plan_id) {
            $planPlugins = Plan::getPlugins($license->plan_id);
        } else {
            // Licença sem plano = todos os plugins ativos
            $planPlugins = Plugin::all(true);
        }
        
        $updates = [];
        
        // $plugins vem como array [slug => version] do cliente
        foreach ($planPlugins as $plugin) {
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
                'type' => $license->type,
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
        
        // Verifica se o plugin está disponível para o plano
        $plugin = Plugin::findBySlug($slug);
        
        if (!$plugin || !$plugin->is_active) {
            return $this->json([
                'success' => false,
                'message' => 'Plugin não encontrado'
            ], 404);
        }
        
        // Verifica se o plano da licença inclui este plugin
        if ($license->plan_id) {
            $planPlugins = Plan::getPlugins($license->plan_id);
            $hasAccess = false;
            
            foreach ($planPlugins as $p) {
                if ($p->slug === $slug) {
                    $hasAccess = true;
                    break;
                }
            }
            
            if (!$hasAccess) {
                return $this->json([
                    'success' => false,
                    'message' => 'Seu plano não inclui este plugin'
                ], 403);
            }
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
        
        return $this->json([
            'success' => true,
            'license' => [
                'status' => $license->status,
                'type' => $license->type,
                'expires_at' => $license->expires_at,
                'plan' => $license->plan_id ? Plan::find($license->plan_id)->name ?? null : null
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
        
        $license = $validation['license'];
        
        // Obtém plugins do plano da licença
        if ($license->plan_id) {
            $plugins = Plan::getPlugins($license->plan_id);
        } else {
            $plugins = Plugin::all(true);
        }
        
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
}
