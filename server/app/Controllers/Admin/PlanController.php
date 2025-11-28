<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\Plan;
use App\Models\Plugin;
use App\Models\ActivityLog;

/**
 * Controller de Planos
 */
class PlanController extends Controller {
    
    /**
     * Lista todos os planos
     */
    public function index() {
        $plans = Plan::all();
        
        // Carrega plugins de cada plano
        foreach ($plans as $plan) {
            $plan->plugins = Plan::getPlugins($plan->id);
        }
        
        return $this->view('admin/plans/index', [
            'plans' => $plans
        ]);
    }
    
    /**
     * Formulário de criação
     */
    public function create() {
        $plugins = Plugin::all(true);
        
        return $this->view('admin/plans/create', [
            'plugins' => $plugins
        ]);
    }
    
    /**
     * Salva novo plano
     */
    public function store() {
        if (!verify_csrf($_POST['_token'] ?? '')) {
            flash('error', 'Token de segurança inválido');
            redirect('/admin/plans/create');
        }
        
        $errors = $this->validate(input(), [
            'name' => 'required',
            'price' => 'required|numeric',
            'period' => 'required'
        ]);
        
        if (!empty($errors)) {
            flash('error', 'Preencha todos os campos obrigatórios');
            redirect('/admin/plans/create');
        }
        
        $data = [
            'name' => $_POST['name'],
            'slug' => slugify($_POST['name']),
            'description' => $_POST['description'] ?? '',
            'price' => floatval($_POST['price']),
            'period' => $_POST['period'],
            'features' => $_POST['features'] ?? '',
            'sort_order' => intval($_POST['sort_order'] ?? 0),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'is_featured' => isset($_POST['is_featured']) ? 1 : 0
        ];
        
        $id = Plan::create($data);
        
        // Associa plugins ao plano
        if (!empty($_POST['plugins']) && is_array($_POST['plugins'])) {
            Plan::syncPlugins($id, $_POST['plugins']);
        }
        
        ActivityLog::admin('Plano criado', ['plan_id' => $id, 'name' => $data['name']]);
        
        flash('success', 'Plano criado com sucesso!');
        redirect('/admin/plans');
    }
    
    /**
     * Formulário de edição
     */
    public function edit($id) {
        $plan = Plan::find($id);
        
        if (!$plan) {
            flash('error', 'Plano não encontrado');
            redirect('/admin/plans');
        }
        
        $plugins = Plugin::all(true);
        $planPlugins = Plan::getPlugins($id);
        $planPluginIds = array_map(fn($p) => $p->id, $planPlugins);
        
        return $this->view('admin/plans/edit', [
            'plan' => $plan,
            'plugins' => $plugins,
            'planPluginIds' => $planPluginIds
        ]);
    }
    
    /**
     * Atualiza plano
     */
    public function update($id) {
        if (!verify_csrf($_POST['_token'] ?? '')) {
            flash('error', 'Token de segurança inválido');
            redirect('/admin/plans/' . $id . '/edit');
        }
        
        $plan = Plan::find($id);
        
        if (!$plan) {
            flash('error', 'Plano não encontrado');
            redirect('/admin/plans');
        }
        
        $data = [
            'name' => $_POST['name'],
            'description' => $_POST['description'] ?? '',
            'price' => floatval($_POST['price']),
            'period' => $_POST['period'],
            'features' => $_POST['features'] ?? '',
            'sort_order' => intval($_POST['sort_order'] ?? 0),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'is_featured' => isset($_POST['is_featured']) ? 1 : 0
        ];
        
        Plan::update($id, $data);
        
        // Atualiza plugins do plano
        $plugins = $_POST['plugins'] ?? [];
        Plan::syncPlugins($id, $plugins);
        
        ActivityLog::admin('Plano atualizado', ['plan_id' => $id, 'name' => $data['name']]);
        
        flash('success', 'Plano atualizado com sucesso!');
        redirect('/admin/plans');
    }
    
    /**
     * Exclui plano
     */
    public function delete($id) {
        if (!verify_csrf($_POST['_token'] ?? '')) {
            flash('error', 'Token de segurança inválido');
            redirect('/admin/plans');
        }
        
        $plan = Plan::find($id);
        
        if (!$plan) {
            flash('error', 'Plano não encontrado');
            redirect('/admin/plans');
        }
        
        $result = Plan::delete($id);
        
        if (!$result) {
            flash('error', 'Não é possível excluir este plano pois há licenças associadas');
            redirect('/admin/plans');
        }
        
        ActivityLog::admin('Plano excluído', ['name' => $plan->name]);
        
        flash('success', 'Plano excluído com sucesso!');
        redirect('/admin/plans');
    }
    
    /**
     * Alterna status do plano
     */
    public function toggle($id) {
        $plan = Plan::find($id);
        
        if (!$plan) {
            return $this->json(['success' => false, 'message' => 'Plano não encontrado']);
        }
        
        $newStatus = $plan->is_active ? 0 : 1;
        Plan::update($id, ['is_active' => $newStatus]);
        
        ActivityLog::admin('Status do plano alterado', [
            'plan_id' => $id,
            'is_active' => $newStatus
        ]);
        
        return $this->json([
            'success' => true,
            'is_active' => $newStatus,
            'message' => $newStatus ? 'Plano ativado' : 'Plano desativado'
        ]);
    }
}
