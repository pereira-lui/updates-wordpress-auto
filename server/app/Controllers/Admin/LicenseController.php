<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\License;
use App\Models\Plan;
use App\Models\ActivityLog;

/**
 * Controller de Licenças
 */
class LicenseController extends Controller {
    
    /**
     * Lista todas as licenças
     */
    public function index() {
        $filters = [
            'status' => $_GET['status'] ?? '',
            'type' => $_GET['type'] ?? '',
            'search' => $_GET['search'] ?? ''
        ];
        
        $licenses = License::all($filters);
        $plans = Plan::all();
        
        return $this->view('admin/licenses/index', [
            'licenses' => $licenses,
            'plans' => $plans,
            'filters' => $filters
        ]);
    }
    
    /**
     * Formulário de criação
     */
    public function create() {
        $plans = Plan::all(true);
        
        return $this->view('admin/licenses/create', [
            'plans' => $plans
        ]);
    }
    
    /**
     * Salva nova licença
     */
    public function store() {
        if (!verify_csrf($_POST['_token'] ?? '')) {
            flash('error', 'Token de segurança inválido');
            redirect('/admin/licenses/create');
        }
        
        $errors = $this->validate(input(), [
            'client_name' => 'required',
            'client_email' => 'required|email',
            'type' => 'required'
        ]);
        
        if (!empty($errors)) {
            flash('error', 'Preencha todos os campos obrigatórios');
            redirect('/admin/licenses/create');
        }
        
        $data = [
            'client_name' => $_POST['client_name'],
            'client_email' => $_POST['client_email'],
            'site_url' => $_POST['site_url'] ?? '',
            'type' => $_POST['type'],
            'plan_id' => $_POST['plan_id'] ?: null,
            'status' => $_POST['status'] ?? 'active',
            'notes' => $_POST['notes'] ?? ''
        ];
        
        // Define expiração baseada no tipo
        if ($data['type'] === License::TYPE_LIFETIME || $data['type'] === License::TYPE_FRIEND) {
            $data['expires_at'] = null;
        } else if (!empty($_POST['expires_at'])) {
            $data['expires_at'] = $_POST['expires_at'];
        } else if (!empty($_POST['plan_id'])) {
            $plan = Plan::find($_POST['plan_id']);
            if ($plan) {
                $data['expires_at'] = Plan::calculateExpiration($plan->period);
            }
        }
        
        $id = License::create($data);
        
        ActivityLog::admin('Licença criada', ['license_id' => $id]);
        
        flash('success', 'Licença criada com sucesso!');
        redirect('/admin/licenses');
    }
    
    /**
     * Exibe detalhes da licença
     */
    public function show($id) {
        $license = License::find($id);
        
        if (!$license) {
            flash('error', 'Licença não encontrada');
            redirect('/admin/licenses');
        }
        
        // Logs da licença
        $logs = ActivityLog::all(['license_id' => $id], 50);
        
        return $this->view('admin/licenses/show', [
            'license' => $license,
            'logs' => $logs
        ]);
    }
    
    /**
     * Formulário de edição
     */
    public function edit($id) {
        $license = License::find($id);
        
        if (!$license) {
            flash('error', 'Licença não encontrada');
            redirect('/admin/licenses');
        }
        
        $plans = Plan::all(true);
        
        return $this->view('admin/licenses/edit', [
            'license' => $license,
            'plans' => $plans
        ]);
    }
    
    /**
     * Atualiza licença
     */
    public function update($id) {
        if (!verify_csrf($_POST['_token'] ?? '')) {
            flash('error', 'Token de segurança inválido');
            redirect('/admin/licenses/' . $id . '/edit');
        }
        
        $license = License::find($id);
        
        if (!$license) {
            flash('error', 'Licença não encontrada');
            redirect('/admin/licenses');
        }
        
        $data = [
            'client_name' => $_POST['client_name'],
            'client_email' => $_POST['client_email'],
            'site_url' => $_POST['site_url'] ?? '',
            'type' => $_POST['type'],
            'plan_id' => $_POST['plan_id'] ?: null,
            'status' => $_POST['status'],
            'notes' => $_POST['notes'] ?? ''
        ];
        
        if (isset($_POST['expires_at'])) {
            $data['expires_at'] = $_POST['expires_at'] ?: null;
        }
        
        License::update($id, $data);
        
        ActivityLog::admin('Licença atualizada', ['license_id' => $id]);
        
        flash('success', 'Licença atualizada com sucesso!');
        redirect('/admin/licenses');
    }
    
    /**
     * Exclui licença
     */
    public function delete($id) {
        if (!verify_csrf($_POST['_token'] ?? '')) {
            flash('error', 'Token de segurança inválido');
            redirect('/admin/licenses');
        }
        
        $license = License::find($id);
        
        if (!$license) {
            flash('error', 'Licença não encontrada');
            redirect('/admin/licenses');
        }
        
        License::delete($id);
        
        ActivityLog::admin('Licença excluída', ['license_key' => $license->license_key]);
        
        flash('success', 'Licença excluída com sucesso!');
        redirect('/admin/licenses');
    }
    
    /**
     * Alterna status da licença
     */
    public function toggle($id) {
        $license = License::find($id);
        
        if (!$license) {
            return $this->json(['success' => false, 'message' => 'Licença não encontrada']);
        }
        
        $newStatus = $license->status === 'active' ? 'cancelled' : 'active';
        License::update($id, ['status' => $newStatus]);
        
        ActivityLog::admin('Status da licença alterado', [
            'license_id' => $id,
            'old_status' => $license->status,
            'new_status' => $newStatus
        ]);
        
        return $this->json([
            'success' => true,
            'status' => $newStatus,
            'message' => 'Status atualizado'
        ]);
    }
    
    /**
     * Regenera chave de licença
     */
    public function regenerateKey($id) {
        $license = License::find($id);
        
        if (!$license) {
            return $this->json(['success' => false, 'message' => 'Licença não encontrada']);
        }
        
        $newKey = generate_license_key();
        License::update($id, ['license_key' => $newKey]);
        
        ActivityLog::admin('Chave de licença regenerada', [
            'license_id' => $id,
            'old_key' => $license->license_key,
            'new_key' => $newKey
        ]);
        
        return $this->json([
            'success' => true,
            'license_key' => $newKey,
            'message' => 'Chave regenerada com sucesso'
        ]);
    }
    
    /**
     * Cria licença para amigo (vitalícia)
     */
    public function createFriend() {
        return $this->view('admin/licenses/friend', [
            'plans' => Plan::all(true)
        ]);
    }
    
    /**
     * Salva licença de amigo
     */
    public function storeFriend() {
        if (!verify_csrf($_POST['_token'] ?? '')) {
            flash('error', 'Token de segurança inválido');
            redirect('/admin/licenses/friend');
        }
        
        $data = [
            'client_name' => $_POST['client_name'],
            'client_email' => $_POST['client_email'],
            'site_url' => $_POST['site_url'] ?? '',
            'type' => License::TYPE_FRIEND,
            'plan_id' => $_POST['plan_id'] ?: null,
            'status' => 'active',
            'expires_at' => null,
            'notes' => 'Licença de cortesia - ' . ($_POST['notes'] ?? '')
        ];
        
        $id = License::create($data);
        
        ActivityLog::admin('Licença de amigo criada', ['license_id' => $id]);
        
        flash('success', 'Licença vitalícia criada para seu amigo!');
        redirect('/admin/licenses');
    }
}
