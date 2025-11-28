<?php $this->layout('layouts/admin', ['title' => 'Editar Licença']); ?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-key"></i> Editar Licença</span>
                <code><?= htmlspecialchars($license->license_key) ?></code>
            </div>
            <div class="card-body">
                <form method="POST" action="<?= url('/admin/licenses/' . $license->id) ?>">
                    <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="_method" value="PUT">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nome do Cliente *</label>
                            <input type="text" name="client_name" class="form-control" value="<?= htmlspecialchars($license->client_name) ?>" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">E-mail *</label>
                            <input type="email" name="client_email" class="form-control" value="<?= htmlspecialchars($license->client_email) ?>" required>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">URL do Site</label>
                            <input type="url" name="site_url" class="form-control" value="<?= htmlspecialchars($license->site_url ?? '') ?>">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Tipo *</label>
                            <select name="type" class="form-select" required>
                                <option value="paid" <?= $license->type === 'paid' ? 'selected' : '' ?>>Paga</option>
                                <option value="lifetime" <?= $license->type === 'lifetime' ? 'selected' : '' ?>>Vitalícia</option>
                                <option value="friend" <?= $license->type === 'friend' ? 'selected' : '' ?>>Amigo (Cortesia)</option>
                                <option value="trial" <?= $license->type === 'trial' ? 'selected' : '' ?>>Trial</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Status *</label>
                            <select name="status" class="form-select" required>
                                <option value="active" <?= $license->status === 'active' ? 'selected' : '' ?>>Ativa</option>
                                <option value="pending" <?= $license->status === 'pending' ? 'selected' : '' ?>>Pendente</option>
                                <option value="expired" <?= $license->status === 'expired' ? 'selected' : '' ?>>Expirada</option>
                                <option value="cancelled" <?= $license->status === 'cancelled' ? 'selected' : '' ?>>Cancelada</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Plano</label>
                            <select name="plan_id" class="form-select">
                                <option value="">Sem plano (todos os plugins)</option>
                                <?php foreach ($plans as $plan): ?>
                                    <option value="<?= $plan->id ?>" <?= $license->plan_id == $plan->id ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($plan->name) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Data de Expiração</label>
                            <input type="date" name="expires_at" class="form-control" value="<?= $license->expires_at ? date('Y-m-d', strtotime($license->expires_at)) : '' ?>">
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Observações</label>
                            <textarea name="notes" class="form-control" rows="2"><?= htmlspecialchars($license->notes ?? '') ?></textarea>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-between">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg"></i> Salvar
                            </button>
                            <a href="<?= url('/admin/licenses') ?>" class="btn btn-secondary">Cancelar</a>
                        </div>
                        
                        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
                            <i class="bi bi-trash"></i> Excluir
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Info Card -->
        <div class="card mt-4">
            <div class="card-header">Informações</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Criada em:</strong> <?= date('d/m/Y H:i', strtotime($license->created_at)) ?></p>
                        <p><strong>Última atualização:</strong> <?= $license->updated_at ? date('d/m/Y H:i', strtotime($license->updated_at)) : '-' ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Último check:</strong> <?= $license->last_check_at ? date('d/m/Y H:i', strtotime($license->last_check_at)) : 'Nunca' ?></p>
                        <p><strong>IP do último check:</strong> <?= htmlspecialchars($license->last_check_ip ?? '-') ?></p>
                    </div>
                </div>
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
                <p>Tem certeza que deseja excluir esta licença?</p>
                <p class="text-danger mb-0">Esta ação não pode ser desfeita.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form method="POST" action="<?= url('/admin/licenses/' . $license->id) ?>" class="d-inline">
                    <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="_method" value="DELETE">
                    <button type="submit" class="btn btn-danger">Excluir</button>
                </form>
            </div>
        </div>
    </div>
</div>
