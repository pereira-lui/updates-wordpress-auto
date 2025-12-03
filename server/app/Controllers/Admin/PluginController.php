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
     * Salva o arquivo ZIP do plugin (mantém versões anteriores)
     */
    private function saveZipFile($id, $slug, $file, $version) {
        $uploadDir = STORAGE_PATH . '/plugins/' . $slug;
        
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                return ['success' => false, 'message' => 'Não foi possível criar diretório'];
            }
        }
        
        // Cria diretório de versões anteriores se não existir
        $versionsDir = $uploadDir . '/versions';
        if (!is_dir($versionsDir)) {
            mkdir($versionsDir, 0755, true);
        }
        
        // Move versão atual para o diretório de versões (se existir)
        $currentFiles = glob($uploadDir . '/*.zip');
        foreach ($currentFiles as $currentFile) {
            $filename = basename($currentFile);
            // Não mover se já existe nas versões
            if (!file_exists($versionsDir . '/' . $filename)) {
                rename($currentFile, $versionsDir . '/' . $filename);
            } else {
                // Remove duplicata
                unlink($currentFile);
            }
        }
        
        // Salva nova versão
        $filename = $slug . '-' . $version . '.zip';
        $destination = $uploadDir . '/' . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            Plugin::update($id, ['zip_file' => $filename]);
            
            // Salva também no histórico de versões
            $this->saveVersionHistory($id, $version, $filename);
            
            return ['success' => true, 'filename' => $filename];
        }
        
        return ['success' => false, 'message' => 'Erro ao mover arquivo'];
    }
    
    /**
     * Salva histórico de versão no banco de dados
     */
    private function saveVersionHistory($pluginId, $version, $filename) {
        $db = \App\Core\Database::getInstance();
        
        // Verifica se a versão já existe no histórico
        $existing = $db->query(
            "SELECT id FROM plugin_versions WHERE plugin_id = ? AND version = ?",
            [$pluginId, $version]
        )->fetch();
        
        if (!$existing) {
            $db->query(
                "INSERT INTO plugin_versions (plugin_id, version, zip_file, created_at) VALUES (?, ?, ?, NOW())",
                [$pluginId, $version, $filename]
            );
        }
    }
    
    /**
     * Lista versões de um plugin
     */
    public function versions($id) {
        $plugin = Plugin::find($id);
        
        if (!$plugin) {
            return $this->json(['success' => false, 'message' => 'Plugin não encontrado']);
        }
        
        $db = \App\Core\Database::getInstance();
        $versions = $db->query(
            "SELECT * FROM plugin_versions WHERE plugin_id = ? ORDER BY created_at DESC",
            [$id]
        )->fetchAll();
        
        // Também lista arquivos físicos na pasta versions
        $versionsDir = STORAGE_PATH . '/plugins/' . $plugin->slug . '/versions';
        $physicalVersions = [];
        
        if (is_dir($versionsDir)) {
            $files = glob($versionsDir . '/*.zip');
            foreach ($files as $file) {
                $filename = basename($file);
                // Extrai versão do nome do arquivo (slug-version.zip)
                if (preg_match('/-(\d+\.\d+\.\d+)\.zip$/', $filename, $matches)) {
                    $physicalVersions[] = [
                        'version' => $matches[1],
                        'filename' => $filename,
                        'size' => filesize($file),
                        'date' => date('Y-m-d H:i:s', filemtime($file))
                    ];
                }
            }
        }
        
        return $this->json([
            'success' => true,
            'plugin' => $plugin,
            'versions' => $versions,
            'physical_versions' => $physicalVersions
        ]);
    }
    
    /**
     * Restaura uma versão anterior do plugin
     */
    public function restoreVersion($id) {
        if (!verify_csrf($_POST['_token'] ?? '')) {
            return $this->json(['success' => false, 'message' => 'Token inválido']);
        }
        
        $version = $_POST['version'] ?? '';
        
        if (empty($version)) {
            return $this->json(['success' => false, 'message' => 'Versão não especificada']);
        }
        
        $plugin = Plugin::find($id);
        
        if (!$plugin) {
            return $this->json(['success' => false, 'message' => 'Plugin não encontrado']);
        }
        
        $uploadDir = STORAGE_PATH . '/plugins/' . $plugin->slug;
        $versionsDir = $uploadDir . '/versions';
        $versionFile = $versionsDir . '/' . $plugin->slug . '-' . $version . '.zip';
        
        if (!file_exists($versionFile)) {
            return $this->json(['success' => false, 'message' => 'Arquivo da versão não encontrado']);
        }
        
        // Move versão atual para versions
        $currentFile = $uploadDir . '/' . $plugin->zip_file;
        if (file_exists($currentFile) && !file_exists($versionsDir . '/' . $plugin->zip_file)) {
            rename($currentFile, $versionsDir . '/' . $plugin->zip_file);
        }
        
        // Copia versão restaurada para diretório principal
        $newFilename = $plugin->slug . '-' . $version . '.zip';
        copy($versionFile, $uploadDir . '/' . $newFilename);
        
        // Atualiza plugin no banco
        Plugin::update($id, [
            'version' => $version,
            'zip_file' => $newFilename
        ]);
        
        ActivityLog::admin('Versão do plugin restaurada', [
            'plugin_id' => $id,
            'plugin_name' => $plugin->name,
            'restored_version' => $version,
            'previous_version' => $plugin->version
        ]);
        
        return $this->json([
            'success' => true,
            'message' => "Plugin restaurado para versão {$version}!"
        ]);
    }
    
    /**
     * Exclui uma versão específica
     */
    public function deleteVersion($id) {
        if (!verify_csrf($_POST['_token'] ?? '')) {
            return $this->json(['success' => false, 'message' => 'Token inválido']);
        }
        
        $version = $_POST['version'] ?? '';
        
        if (empty($version)) {
            return $this->json(['success' => false, 'message' => 'Versão não especificada']);
        }
        
        $plugin = Plugin::find($id);
        
        if (!$plugin) {
            return $this->json(['success' => false, 'message' => 'Plugin não encontrado']);
        }
        
        // Não permite excluir versão atual
        if ($plugin->version === $version) {
            return $this->json(['success' => false, 'message' => 'Não é possível excluir a versão atual']);
        }
        
        $versionsDir = STORAGE_PATH . '/plugins/' . $plugin->slug . '/versions';
        $versionFile = $versionsDir . '/' . $plugin->slug . '-' . $version . '.zip';
        
        if (file_exists($versionFile)) {
            unlink($versionFile);
        }
        
        // Remove do banco
        $db = \App\Core\Database::getInstance();
        $db->query(
            "DELETE FROM plugin_versions WHERE plugin_id = ? AND version = ?",
            [$id, $version]
        );
        
        ActivityLog::admin('Versão do plugin excluída', [
            'plugin_id' => $id,
            'plugin_name' => $plugin->name,
            'deleted_version' => $version
        ]);
        
        return $this->json([
            'success' => true,
            'message' => "Versão {$version} excluída!"
        ]);
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
