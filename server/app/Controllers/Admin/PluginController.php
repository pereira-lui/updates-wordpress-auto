<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\Plugin;
use App\Models\ActivityLog;

/**
 * Controller de Plugins
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
     * Formulário de criação
     */
    public function create() {
        return $this->view('admin/plugins/create');
    }
    
    /**
     * Salva novo plugin
     */
    public function store() {
        if (!verify_csrf($_POST['_token'] ?? '')) {
            flash('error', 'Token de segurança inválido');
            redirect('/admin/plugins/create');
        }
        
        $errors = $this->validate([
            'name' => 'required',
            'slug' => 'required',
            'version' => 'required'
        ]);
        
        if (!empty($errors)) {
            flash('error', 'Preencha todos os campos obrigatórios');
            redirect('/admin/plugins/create');
        }
        
        // Verifica se slug já existe
        if (Plugin::findBySlug($_POST['slug'])) {
            flash('error', 'Já existe um plugin com este slug');
            redirect('/admin/plugins/create');
        }
        
        $data = [
            'name' => $_POST['name'],
            'slug' => slugify($_POST['slug']),
            'version' => $_POST['version'],
            'description' => $_POST['description'] ?? '',
            'changelog' => $_POST['changelog'] ?? '',
            'author' => $_POST['author'] ?? '',
            'author_uri' => $_POST['author_uri'] ?? '',
            'plugin_uri' => $_POST['plugin_uri'] ?? '',
            'requires_wp' => $_POST['requires_wp'] ?? '5.0',
            'tested_wp' => $_POST['tested_wp'] ?? '6.4',
            'requires_php' => $_POST['requires_php'] ?? '7.4',
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];
        
        $id = Plugin::create($data);
        
        // Upload do arquivo ZIP
        if (!empty($_FILES['zip_file']['tmp_name'])) {
            $result = Plugin::uploadZip($id, $_FILES['zip_file']);
            if (!$result['success']) {
                flash('warning', 'Plugin criado, mas houve erro no upload: ' . $result['message']);
            }
        }
        
        ActivityLog::admin('Plugin criado', ['plugin_id' => $id, 'name' => $data['name']]);
        
        flash('success', 'Plugin criado com sucesso!');
        redirect('/admin/plugins');
    }
    
    /**
     * Exibe detalhes do plugin
     */
    public function show($id) {
        $plugin = Plugin::find($id);
        
        if (!$plugin) {
            flash('error', 'Plugin não encontrado');
            redirect('/admin/plugins');
        }
        
        return $this->view('admin/plugins/show', [
            'plugin' => $plugin
        ]);
    }
    
    /**
     * Formulário de edição
     */
    public function edit($id) {
        $plugin = Plugin::find($id);
        
        if (!$plugin) {
            flash('error', 'Plugin não encontrado');
            redirect('/admin/plugins');
        }
        
        return $this->view('admin/plugins/edit', [
            'plugin' => $plugin
        ]);
    }
    
    /**
     * Atualiza plugin
     */
    public function update($id) {
        if (!verify_csrf($_POST['_token'] ?? '')) {
            flash('error', 'Token de segurança inválido');
            redirect('/admin/plugins/' . $id . '/edit');
        }
        
        $plugin = Plugin::find($id);
        
        if (!$plugin) {
            flash('error', 'Plugin não encontrado');
            redirect('/admin/plugins');
        }
        
        $data = [
            'name' => $_POST['name'],
            'version' => $_POST['version'],
            'description' => $_POST['description'] ?? '',
            'changelog' => $_POST['changelog'] ?? '',
            'author' => $_POST['author'] ?? '',
            'author_uri' => $_POST['author_uri'] ?? '',
            'plugin_uri' => $_POST['plugin_uri'] ?? '',
            'requires_wp' => $_POST['requires_wp'] ?? '5.0',
            'tested_wp' => $_POST['tested_wp'] ?? '6.4',
            'requires_php' => $_POST['requires_php'] ?? '7.4',
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];
        
        Plugin::update($id, $data);
        
        // Upload do novo arquivo ZIP
        if (!empty($_FILES['zip_file']['tmp_name'])) {
            $result = Plugin::uploadZip($id, $_FILES['zip_file']);
            if (!$result['success']) {
                flash('warning', 'Plugin atualizado, mas houve erro no upload: ' . $result['message']);
            }
        }
        
        ActivityLog::admin('Plugin atualizado', ['plugin_id' => $id, 'name' => $data['name']]);
        
        flash('success', 'Plugin atualizado com sucesso!');
        redirect('/admin/plugins');
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
        
        // Remove arquivo ZIP
        $zipPath = Plugin::getZipPath($plugin->slug);
        if ($zipPath && file_exists($zipPath)) {
            unlink($zipPath);
        }
        
        Plugin::delete($id);
        
        ActivityLog::admin('Plugin excluído', ['name' => $plugin->name]);
        
        flash('success', 'Plugin excluído com sucesso!');
        redirect('/admin/plugins');
    }
    
    /**
     * Alterna status do plugin
     */
    public function toggle($id) {
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
     * Upload de nova versão
     */
    public function uploadVersion($id) {
        $plugin = Plugin::find($id);
        
        if (!$plugin) {
            return $this->json(['success' => false, 'message' => 'Plugin não encontrado']);
        }
        
        if (empty($_FILES['zip_file']['tmp_name'])) {
            return $this->json(['success' => false, 'message' => 'Nenhum arquivo enviado']);
        }
        
        if (empty($_POST['version'])) {
            return $this->json(['success' => false, 'message' => 'Versão não informada']);
        }
        
        // Atualiza versão
        Plugin::update($id, ['version' => $_POST['version']]);
        
        // Upload do arquivo
        $result = Plugin::uploadZip($id, $_FILES['zip_file']);
        
        if ($result['success']) {
            ActivityLog::admin('Nova versão do plugin enviada', [
                'plugin_id' => $id,
                'version' => $_POST['version']
            ]);
        }
        
        return $this->json($result);
    }
}
