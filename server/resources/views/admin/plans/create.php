<?php $this->layout('layouts/admin', ['title' => 'Novo Plano']); ?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-tags"></i> Criar Novo Plano
            </div>
            <div class="card-body">
                <form method="POST" action="<?= url('/admin/plans') ?>">
                    <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                    
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Nome do Plano *</label>
                            <input type="text" name="name" class="form-control" placeholder="Ex: Plano Básico" required>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Preço (R$) *</label>
                            <input type="number" name="price" class="form-control" step="0.01" min="0" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Período *</label>
                            <select name="period" class="form-select" required>
                                <option value="monthly">Mensal</option>
                                <option value="yearly">Anual</option>
                                <option value="lifetime">Vitalício (único)</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Ordem de Exibição</label>
                            <input type="number" name="sort_order" class="form-control" value="0">
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Descrição</label>
                            <textarea name="description" class="form-control" rows="2"></textarea>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Recursos (um por linha)</label>
                            <textarea name="features" class="form-control" rows="4" placeholder="Suporte prioritário
Atualizações vitalícias
Acesso a todos os plugins"></textarea>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Plugins Inclusos</label>
                            <div class="border rounded p-3" style="max-height: 200px; overflow-y: auto;">
                                <?php if (empty($plugins)): ?>
                                    <p class="text-muted mb-0">Nenhum plugin cadastrado</p>
                                <?php else: ?>
                                    <?php foreach ($plugins as $plugin): ?>
                                        <div class="form-check">
                                            <input type="checkbox" name="plugins[]" value="<?= $plugin->id ?>" class="form-check-input" id="plugin<?= $plugin->id ?>">
                                            <label class="form-check-label" for="plugin<?= $plugin->id ?>">
                                                <?= htmlspecialchars($plugin->name) ?>
                                                <small class="text-muted">(<?= $plugin->slug ?>)</small>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-check">
                                <input type="checkbox" name="is_active" class="form-check-input" id="isActive" checked>
                                <label class="form-check-label" for="isActive">Plano ativo</label>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-check">
                                <input type="checkbox" name="is_featured" class="form-check-input" id="isFeatured">
                                <label class="form-check-label" for="isFeatured">Destacar este plano</label>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg"></i> Criar Plano
                        </button>
                        <a href="<?= url('/admin/plans') ?>" class="btn btn-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
