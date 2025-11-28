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
     * Verifica atualizações disponíveis
     * POST /api/v1/check-updates
     */
    public function checkUpdates() {
        $licenseKey = $_POST['license_key'] ?? $_GET['license_key'] ?? '';
        $siteUrl = $_POST['site_url'] ?? $_GET['site_url'] ?? '';
        $plugins = $_POST['plugins'] ?? [];
        
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
        $availablePlugins = [];
        
        if ($license->plan_id) {
            $planPlugins = Plan::getPlugins($license->plan_id);
        } else {
            // Licença sem plano = todos os plugins ativos
            $planPlugins = Plugin::all(true);
        }
        
        $updates = [];
        
        foreach ($planPlugins as $plugin) {
            $pluginInfo = [
                'name' => $plugin->name,
                'slug' => $plugin->slug,
                'version' => $plugin->version,
                'new_version' => $plugin->version,
                'url' => $plugin->plugin_uri,
                'package' => url('/api/v1/download/' . $plugin->slug . '?license_key=' . $licenseKey),
                'icons' => [],
                'banners' => [],
                'requires' => $plugin->requires_wp,
                'tested' => $plugin->tested_wp,
                'requires_php' => $plugin->requires_php,
                'author' => $plugin->author,
                'author_uri' => $plugin->author_uri,
                'sections' => [
                    'description' => $plugin->description,
                    'changelog' => $plugin->changelog
                ]
            ];
            
            // Verifica se há atualização disponível
            if (is_array($plugins)) {
                foreach ($plugins as $clientPlugin) {
                    if (isset($clientPlugin['slug']) && $clientPlugin['slug'] === $plugin->slug) {
                        $clientVersion = $clientPlugin['version'] ?? '0.0.0';
                        if (version_compare($plugin->version, $clientVersion, '>')) {
                            $pluginInfo['update_available'] = true;
                        }
                        break;
                    }
                }
            }
            
            $availablePlugins[$plugin->slug] = $pluginInfo;
        }
        
        return $this->json([
            'success' => true,
            'license' => [
                'status' => $license->status,
                'type' => $license->type,
                'expires_at' => $license->expires_at
            ],
            'plugins' => $availablePlugins
        ]);
    }
    
    /**
     * Download do plugin
     * GET /api/v1/download/{slug}
     */
    public function download($slug) {
        $licenseKey = $_GET['license_key'] ?? '';
        $siteUrl = $_GET['site_url'] ?? '';
        
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
        $licenseKey = $_GET['license_key'] ?? '';
        
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
        $licenseKey = $_POST['license_key'] ?? '';
        $siteUrl = $_POST['site_url'] ?? '';
        
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
        $licenseKey = $_POST['license_key'] ?? '';
        
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
}
