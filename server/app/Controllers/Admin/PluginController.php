<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\Plugin;
use App\Models\ActivityLog;
use ZipArchive;

/**
 * Controller de Plugins - Upload automático
 */
class PluginController extends Controller {
    
    /**
     * Lista todos os plugins
     */
    public function index() {
        $plugins = Plugin::all();
        
        return $this->view('admin/plugins/index', [
            'plugins' => $plugins
        ]);
    }
    
    /**
     * Upload automático de plugin (novo ou atualização)
     */
    public function upload() {
        if (!verify_csrf($_POST['_token'] ?? '')) {
            return $this->json(['success' => false, 'message' => 'Token de segurança inválido']);
        }
        
        if (empty($_FILES['zip_file']['tmp_name'])) {
            return $this->json(['success' => false, 'message' => 'Nenhum arquivo enviado']);
        }
        
        $file = $_FILES['zip_file'];
        
        // Verifica se é um arquivo ZIP válido
        if ($file['type'] !== 'application/zip' && $file['type'] !== 'application/x-zip-compressed') {
            // Tenta verificar pela extensão
            if (pathinfo($file['name'], PATHINFO_EXTENSION) !== 'zip') {
                return $this->json(['success' => false, 'message' => 'O arquivo deve ser um ZIP']);
            }
        }
        
        // Extrai informações do plugin
        $pluginInfo = $this->extractPluginInfo($file['tmp_name']);
        
        if (!$pluginInfo) {
            return $this->json(['success' => false, 'message' => 'Não foi possível extrair informações do plugin. Verifique se o ZIP contém um plugin WordPress válido.']);
        }
        
        // Verifica se é uma atualização de plugin existente
        $updateId = $_POST['update_id'] ?? null;
        $existingPlugin = null;
        
        if ($updateId) {
            $existingPlugin = Plugin::find($updateId);
        } else {
            $existingPlugin = Plugin::findBySlug($pluginInfo['slug']);
        }
        
        if ($existingPlugin) {
            // Atualiza plugin existente
            $data = [
                'name' => $pluginInfo['name'],
                'version' => $pluginInfo['version'],
                'description' => $pluginInfo['description'],
                'author' => $pluginInfo['author'],
                'author_uri' => $pluginInfo['author_uri'],
                'plugin_uri' => $pluginInfo['plugin_uri'],
                'requires_wp' => $pluginInfo['requires_wp'],
                'tested_wp' => $pluginInfo['tested_wp'],
                'requires_php' => $pluginInfo['requires_php']
            ];
            
            Plugin::update($existingPlugin->id, $data);
            
            // Upload do arquivo
            $result = $this->saveZipFile($existingPlugin->id, $existingPlugin->slug, $file, $pluginInfo['version']);
            
            if (!$result['success']) {
                return $this->json(['success' => false, 'message' => 'Plugin atualizado, mas erro no upload: ' . $result['message']]);
            }
            
            ActivityLog::admin('Plugin atualizado via upload', [
                'plugin_id' => $existingPlugin->id,
                'name' => $pluginInfo['name'],
                'version' => $pluginInfo['version']
            ]);
            
            return $this->json([
                'success' => true,
                'message' => "Plugin '{$pluginInfo['name']}' atualizado para v{$pluginInfo['version']}!"
            ]);
        } else {
            // Cria novo plugin
            $data = [
                'name' => $pluginInfo['name'],
                'slug' => $pluginInfo['slug'],
                'version' => $pluginInfo['version'],
                'description' => $pluginInfo['description'],
                'changelog' => "= {$pluginInfo['version']} =\n* Versão inicial",
                'author' => $pluginInfo['author'],
                'author_uri' => $pluginInfo['author_uri'],
                'plugin_uri' => $pluginInfo['plugin_uri'],
                'requires_wp' => $pluginInfo['requires_wp'],
                'tested_wp' => $pluginInfo['tested_wp'],
                'requires_php' => $pluginInfo['requires_php'],
                'is_active' => 1
            ];
            
            $id = Plugin::create($data);
            
            // Upload do arquivo
            $result = $this->saveZipFile($id, $pluginInfo['slug'], $file, $pluginInfo['version']);
            
            if (!$result['success']) {
                return $this->json(['success' => false, 'message' => 'Plugin criado, mas erro no upload: ' . $result['message']]);
            }
            
            ActivityLog::admin('Plugin criado via upload', [
                'plugin_id' => $id,
                'name' => $pluginInfo['name'],
                'version' => $pluginInfo['version']
            ]);
            
            return $this->json([
                'success' => true,
                'message' => "Plugin '{$pluginInfo['name']}' v{$pluginInfo['version']} adicionado com sucesso!"
            ]);
        }
    }
    
    /**
     * Extrai informações do cabeçalho do plugin WordPress
     */
    private function extractPluginInfo($zipPath) {
        $zip = new ZipArchive();
        
        if ($zip->open($zipPath) !== true) {
            return null;
        }
        
        $pluginInfo = null;
        $pluginSlug = null;
        
        // Procura pelo arquivo principal do plugin
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            
            // Pega o slug da pasta raiz
            $parts = explode('/', $filename);
            if (count($parts) >= 1 && !$pluginSlug) {
                $pluginSlug = $parts[0];
            }
            
            // Procura por arquivos PHP na raiz da pasta do plugin
            if (preg_match('#^[^/]+/[^/]+\.php$#', $filename)) {
                $content = $zip->getFromIndex($i);
                
                // Verifica se é o arquivo principal (contém Plugin Name:)
                if (stripos($content, 'Plugin Name:') !== false) {
                    $pluginInfo = $this->parsePluginHeader($content);
                    $pluginInfo['slug'] = $pluginSlug;
                    break;
                }
            }
        }
        
        $zip->close();
        
        // Se não encontrou, tenta usar o nome do arquivo
        if (!$pluginInfo && $pluginSlug) {
            $pluginInfo = [
                'name' => ucwords(str_replace(['-', '_'], ' ', $pluginSlug)),
                'slug' => $pluginSlug,
                'version' => '1.0.0',
                'description' => '',
                'author' => '',
                'author_uri' => '',
                'plugin_uri' => '',
                'requires_wp' => '5.0',
                'tested_wp' => '6.4',
                'requires_php' => '7.4'
            ];
        }
        
        return $pluginInfo;
    }
    
    /**
     * Faz o parse do cabeçalho padrão de plugins WordPress
     */
    private function parsePluginHeader($content) {
        $headers = [
            'name' => 'Plugin Name',
            'plugin_uri' => 'Plugin URI',
            'version' => 'Version',
            'description' => 'Description',
            'author' => 'Author',
            'author_uri' => 'Author URI',
            'requires_wp' => 'Requires at least',
            'tested_wp' => 'Tested up to',
            'requires_php' => 'Requires PHP'
        ];
        
        $result = [
            'name' => '',
            'slug' => '',
            'version' => '1.0.0',
            'description' => '',
            'author' => '',
            'author_uri' => '',
            'plugin_uri' => '',
            'requires_wp' => '5.0',
            'tested_wp' => '6.4',
            'requires_php' => '7.4'
        ];
        
        foreach ($headers as $key => $header) {
            if (preg_match('/^[\s\*]*' . preg_quote($header, '/') . ':\s*(.+)$/mi', $content, $match)) {
                $result[$key] = trim($match[1]);
            }
        }
        
        // Gera slug se não tiver
        if (empty($result['slug']) && !empty($result['name'])) {
            $result['slug'] = sanitize_slug($result['name']);
        }
        
        return $result;
    }
    
    /**
     * Salva o arquivo ZIP do plugin
     */
    private function saveZipFile($id, $slug, $file, $version) {
        $uploadDir = STORAGE_PATH . '/plugins/' . $slug;
        
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                return ['success' => false, 'message' => 'Não foi possível criar diretório'];
            }
        }
        
        // Remove arquivos antigos
        $files = glob($uploadDir . '/*.zip');
        foreach ($files as $oldFile) {
            unlink($oldFile);
        }
        
        $filename = $slug . '-' . $version . '.zip';
        $destination = $uploadDir . '/' . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            Plugin::update($id, ['zip_file' => $filename]);
            return ['success' => true, 'filename' => $filename];
        }
        
        return ['success' => false, 'message' => 'Erro ao mover arquivo'];
    }
    
    /**
     * Alterna status do plugin
     */
    public function toggle($id) {
        if (!verify_csrf($_POST['_token'] ?? '')) {
            return $this->json(['success' => false, 'message' => 'Token inválido']);
        }
        
        $plugin = Plugin::find($id);
        
        if (!$plugin) {
            return $this->json(['success' => false, 'message' => 'Plugin não encontrado']);
        }
        
        $newStatus = $plugin->is_active ? 0 : 1;
        Plugin::update($id, ['is_active' => $newStatus]);
        
        ActivityLog::admin('Status do plugin alterado', [
            'plugin_id' => $id,
            'is_active' => $newStatus
        ]);
        
        return $this->json([
            'success' => true,
            'is_active' => $newStatus,
            'message' => $newStatus ? 'Plugin ativado' : 'Plugin desativado'
        ]);
    }
    
    /**
     * Exclui plugin
     */
    public function destroy($id) {
        if (!verify_csrf($_POST['_token'] ?? '')) {
            flash('error', 'Token de segurança inválido');
            redirect('/admin/plugins');
        }
        
        $plugin = Plugin::find($id);
        
        if (!$plugin) {
            flash('error', 'Plugin não encontrado');
            redirect('/admin/plugins');
        }
        
        // Remove pasta do plugin
        $pluginDir = STORAGE_PATH . '/plugins/' . $plugin->slug;
        if (is_dir($pluginDir)) {
            $files = glob($pluginDir . '/*');
            foreach ($files as $file) {
                unlink($file);
            }
            rmdir($pluginDir);
        }
        
        Plugin::delete($id);
        
        ActivityLog::admin('Plugin excluído', ['name' => $plugin->name]);
        
        flash('success', 'Plugin excluído com sucesso!');
        redirect('/admin/plugins');
    }
}
