<?php $this->layout('layouts/admin', ['title' => 'Editar Plano']); ?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-tags"></i> Editar Plano
            </div>
            <div class="card-body">
                <form method="POST" action="<?= url('/admin/plans/' . $plan->id) ?>">
                    <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="_method" value="PUT">
                    
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Nome do Plano *</label>
                            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($plan->name) ?>" required>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Preço (R$) *</label>
                            <input type="number" name="price" class="form-control" step="0.01" min="0" value="<?= $plan->price ?>" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Período *</label>
                            <select name="period" class="form-select" required>
                                <option value="monthly" <?= $plan->period === 'monthly' ? 'selected' : '' ?>>Mensal</option>
                                <option value="yearly" <?= $plan->period === 'yearly' ? 'selected' : '' ?>>Anual</option>
                                <option value="lifetime" <?= $plan->period === 'lifetime' ? 'selected' : '' ?>>Vitalício (único)</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Ordem de Exibição</label>
                            <input type="number" name="sort_order" class="form-control" value="<?= $plan->sort_order ?? 0 ?>">
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Descrição</label>
                            <textarea name="description" class="form-control" rows="2"><?= htmlspecialchars($plan->description ?? '') ?></textarea>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Recursos (um por linha)</label>
                            <textarea name="features" class="form-control" rows="4"><?= htmlspecialchars($plan->features ?? '') ?></textarea>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Plugins Inclusos</label>
                            <div class="border rounded p-3" style="max-height: 200px; overflow-y: auto;">
                                <?php if (empty($plugins)): ?>
                                    <p class="text-muted mb-0">Nenhum plugin cadastrado</p>
                                <?php else: ?>
                                    <?php foreach ($plugins as $plugin): ?>
                                        <div class="form-check">
                                            <input type="checkbox" name="plugins[]" value="<?= $plugin->id ?>" class="form-check-input" id="plugin<?= $plugin->id ?>" <?= in_array($plugin->id, $planPluginIds) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="plugin<?= $plugin->id ?>">
                                                <?= htmlspecialchars($plugin->name) ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-check">
                                <input type="checkbox" name="is_active" class="form-check-input" id="isActive" <?= $plan->is_active ? 'checked' : '' ?>>
                                <label class="form-check-label" for="isActive">Plano ativo</label>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-check">
                                <input type="checkbox" name="is_featured" class="form-check-input" id="isFeatured" <?= $plan->is_featured ? 'checked' : '' ?>>
                                <label class="form-check-label" for="isFeatured">Destacar este plano</label>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-between">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg"></i> Salvar
                            </button>
                            <a href="<?= url('/admin/plans') ?>" class="btn btn-secondary">Cancelar</a>
                        </div>
                        
                        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
                            <i class="bi bi-trash"></i> Excluir
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal de exclusão -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Exclusão</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja excluir o plano <strong><?= htmlspecialchars($plan->name) ?></strong>?</p>
                <p class="text-warning mb-0"><i class="bi bi-exclamation-triangle"></i> Não será possível excluir se houver licenças associadas.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form method="POST" action="<?= url('/admin/plans/' . $plan->id) ?>" class="d-inline">
                    <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="_method" value="DELETE">
                    <button type="submit" class="btn btn-danger">Excluir</button>
                </form>
            </div>
        </div>
    </div>
</div>
