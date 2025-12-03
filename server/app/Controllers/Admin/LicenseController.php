<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Models\License;
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
            'period' => $_GET['period'] ?? '',
            'search' => $_GET['search'] ?? '',
            'update_status' => $_GET['update_status'] ?? ''
        ];
        
        $licenses = License::all($filters);
        
        // Estatísticas de status de atualização
        $updateStats = $this->getUpdateStats();
        
        return $this->view('admin/licenses/index', [
            'licenses' => $licenses,
            'filters' => $filters,
            'updateStats' => $updateStats
        ]);
    }
    
    /**
     * Retorna estatísticas de atualização
     */
    private function getUpdateStats() {
        $stats = Database::selectOne(
            "SELECT 
                SUM(CASE WHEN update_status = 'ok' THEN 1 ELSE 0 END) as ok,
                SUM(CASE WHEN update_status = 'error' THEN 1 ELSE 0 END) as error,
                SUM(CASE WHEN update_status = 'rollback' THEN 1 ELSE 0 END) as rollback,
                SUM(CASE WHEN update_status = 'pending' THEN 1 ELSE 0 END) as pending
             FROM licenses 
             WHERE status = 'active'"
        );
        
        return [
            'ok' => $stats->ok ?? 0,
            'error' => $stats->error ?? 0,
            'rollback' => $stats->rollback ?? 0,
            'pending' => $stats->pending ?? 0
        ];
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
        
        // Busca histórico de atualizações
        $updateLogs = Database::select(
            "SELECT ul.*, p.name as plugin_name 
             FROM update_logs ul
             LEFT JOIN plugins p ON ul.plugin_slug = p.slug
             WHERE ul.license_id = ?
             ORDER BY ul.created_at DESC
             LIMIT 20",
            [$id]
        );
        
        return $this->view('admin/licenses/show', [
            'license' => $license,
            'updateLogs' => $updateLogs
        ]);
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
     * Formulário de licença para amigo (vitalícia)
     */
    public function createFriend() {
        return $this->view('admin/licenses/friend');
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
            'period' => License::PERIOD_LIFETIME,
            'status' => 'active',
            'expires_at' => null,
            'notes' => 'Licença de cortesia - ' . ($_POST['notes'] ?? '')
        ];
        
        $id = License::create($data);
        $license = License::find($id);
        
        ActivityLog::admin('Licença de amigo criada', ['license_id' => $id]);
        
        flash('success', 'Licença vitalícia criada! Chave: ' . $license->license_key);
        redirect('/admin/licenses');
    }
}
