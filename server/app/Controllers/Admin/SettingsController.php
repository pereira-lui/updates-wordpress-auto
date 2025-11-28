<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\User;
use App\Models\ActivityLog;

/**
 * Controller de Configurações
 */
class SettingsController extends Controller {
    
    /**
     * Página de configurações gerais
     */
    public function index() {
        $settings = $this->loadSettings();
        
        return $this->view('admin/settings/index', [
            'settings' => $settings
        ]);
    }
    
    /**
     * Salva configurações gerais
     */
    public function update() {
        if (!verify_csrf($_POST['_token'] ?? '')) {
            flash('error', 'Token de segurança inválido');
            redirect('/admin/settings');
        }
        
        $settings = [
            'site_name' => $_POST['site_name'] ?? 'Premium Updates',
            'site_url' => $_POST['site_url'] ?? '',
            'admin_email' => $_POST['admin_email'] ?? '',
            'price_monthly' => $_POST['price_monthly'] ?? '29.90',
            'price_quarterly' => $_POST['price_quarterly'] ?? '79.90',
            'price_semiannual' => $_POST['price_semiannual'] ?? '149.90',
            'price_yearly' => $_POST['price_yearly'] ?? '249.90',
            'asaas_api_key' => $_POST['asaas_api_key'] ?? '',
            'asaas_sandbox' => isset($_POST['asaas_sandbox']) ? '1' : '0',
            'smtp_host' => $_POST['smtp_host'] ?? '',
            'smtp_port' => $_POST['smtp_port'] ?? '587',
            'smtp_user' => $_POST['smtp_user'] ?? '',
            'smtp_pass' => $_POST['smtp_pass'] ?? '',
            'smtp_from' => $_POST['smtp_from'] ?? '',
            'smtp_from_name' => $_POST['smtp_from_name'] ?? ''
        ];
        
        $this->saveSettings($settings);
        
        ActivityLog::admin('Configurações atualizadas');
        
        flash('success', 'Configurações salvas com sucesso!');
        redirect('/admin/settings');
    }
    
    /**
     * Página de perfil do usuário
     */
    public function profile() {
        $user = User::find(auth()->id);
        
        return $this->view('admin/settings/profile', [
            'user' => $user
        ]);
    }
    
    /**
     * Atualiza perfil do usuário
     */
    public function updateProfile() {
        if (!verify_csrf($_POST['_token'] ?? '')) {
            flash('error', 'Token de segurança inválido');
            redirect('/admin/settings/profile');
        }
        
        $user = auth();
        
        $data = [
            'name' => $_POST['name'],
            'email' => $_POST['email']
        ];
        
        // Se informou nova senha
        if (!empty($_POST['new_password'])) {
            if (empty($_POST['current_password'])) {
                flash('error', 'Informe a senha atual');
                redirect('/admin/settings/profile');
            }
            
            $result = User::changePassword($user->id, $_POST['current_password'], $_POST['new_password']);
            
            if (!$result['success']) {
                flash('error', $result['message']);
                redirect('/admin/settings/profile');
            }
        }
        
        User::update($user->id, $data);
        
        // Atualiza sessão
        $_SESSION['user_name'] = $data['name'];
        $_SESSION['user_email'] = $data['email'];
        
        ActivityLog::admin('Perfil atualizado');
        
        flash('success', 'Perfil atualizado com sucesso!');
        redirect('/admin/settings/profile');
    }
    
    /**
     * Página de logs de atividade
     */
    public function logs() {
        $filters = [
            'type' => $_GET['type'] ?? '',
            'date_from' => $_GET['date_from'] ?? '',
            'date_to' => $_GET['date_to'] ?? ''
        ];
        
        $logs = ActivityLog::all($filters, 500);
        
        return $this->view('admin/settings/logs', [
            'logs' => $logs,
            'filters' => $filters
        ]);
    }
    
    /**
     * Limpa logs antigos
     */
    public function clearLogs() {
        if (!verify_csrf($_POST['_token'] ?? '')) {
            flash('error', 'Token de segurança inválido');
            redirect('/admin/settings/logs');
        }
        
        $days = intval($_POST['days'] ?? 90);
        ActivityLog::cleanup($days);
        
        ActivityLog::admin('Logs limpos', ['older_than_days' => $days]);
        
        flash('success', 'Logs mais antigos que ' . $days . ' dias foram removidos');
        redirect('/admin/settings/logs');
    }
    
    /**
     * Lista usuários administrativos
     */
    public function users() {
        $users = User::all();
        
        return $this->view('admin/settings/users', [
            'users' => $users
        ]);
    }
    
    /**
     * Formulário de criar usuário
     */
    public function createUser() {
        return $this->view('admin/settings/user-create');
    }
    
    /**
     * Salva novo usuário
     */
    public function storeUser() {
        if (!verify_csrf($_POST['_token'] ?? '')) {
            flash('error', 'Token de segurança inválido');
            redirect('/admin/settings/users/create');
        }
        
        $errors = $this->validate(input(), [
            'name' => 'required',
            'email' => 'required|email',
            'username' => 'required',
            'password' => 'required|min:6'
        ]);
        
        if (!empty($errors)) {
            flash('error', 'Preencha todos os campos corretamente');
            redirect('/admin/settings/users/create');
        }
        
        // Verifica duplicidade
        if (User::findByEmail($_POST['email'])) {
            flash('error', 'Este e-mail já está em uso');
            redirect('/admin/settings/users/create');
        }
        
        if (User::findByUsername($_POST['username'])) {
            flash('error', 'Este username já está em uso');
            redirect('/admin/settings/users/create');
        }
        
        User::create([
            'name' => $_POST['name'],
            'email' => $_POST['email'],
            'username' => $_POST['username'],
            'password' => $_POST['password'],
            'role' => $_POST['role'] ?? 'admin'
        ]);
        
        ActivityLog::admin('Usuário criado', ['email' => $_POST['email']]);
        
        flash('success', 'Usuário criado com sucesso!');
        redirect('/admin/settings/users');
    }
    
    /**
     * Exclui usuário
     */
    public function destroyUser($id) {
        if (!verify_csrf($_POST['_token'] ?? '')) {
            flash('error', 'Token de segurança inválido');
            redirect('/admin/settings/users');
        }
        
        // Não permite excluir a si mesmo
        if ($id == auth()->id) {
            flash('error', 'Você não pode excluir seu próprio usuário');
            redirect('/admin/settings/users');
        }
        
        $user = User::find($id);
        if ($user) {
            User::delete($id);
            ActivityLog::admin('Usuário excluído', ['email' => $user->email]);
        }
        
        flash('success', 'Usuário excluído');
        redirect('/admin/settings/users');
    }
    
    /**
     * Carrega configurações do arquivo
     */
    private function loadSettings() {
        $file = config('app.storage_path') . '/settings.json';
        
        if (file_exists($file)) {
            return json_decode(file_get_contents($file), true);
        }
        
        return [
            'site_name' => 'Premium Updates',
            'site_url' => '',
            'admin_email' => '',
            'asaas_api_key' => config('asaas.api_key'),
            'asaas_sandbox' => config('asaas.sandbox') ? '1' : '0'
        ];
    }
    
    /**
     * Salva configurações no arquivo
     */
    private function saveSettings($settings) {
        $dir = config('app.storage_path');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        $file = $dir . '/settings.json';
        file_put_contents($file, json_encode($settings, JSON_PRETTY_PRINT));
    }
}
