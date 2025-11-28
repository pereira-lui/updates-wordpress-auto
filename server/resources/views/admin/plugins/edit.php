<?php $this->layout('layouts/admin', ['title' => 'Editar Plugin']); ?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-puzzle"></i> Editar Plugin</span>
                <span class="badge bg-primary">v<?= htmlspecialchars($plugin->version) ?></span>
            </div>
            <div class="card-body">
                <form method="POST" action="<?= url('/admin/plugins/' . $plugin->id) ?>" enctype="multipart/form-data">
                    <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="_method" value="PUT">
                    
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Nome do Plugin *</label>
                            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($plugin->name) ?>" required>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Versão *</label>
                            <input type="text" name="version" class="form-control" value="<?= htmlspecialchars($plugin->version) ?>" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Slug</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($plugin->slug) ?>" disabled>
                            <small class="text-muted">O slug não pode ser alterado</small>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Novo Arquivo ZIP</label>
                            <input type="file" name="zip_file" class="form-control" accept=".zip">
                            <?php if ($plugin->zip_file): ?>
                                <small class="text-success">
                                    <i class="bi bi-check-circle"></i> <?= htmlspecialchars($plugin->zip_file) ?>
                                </small>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Descrição</label>
                            <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($plugin->description ?? '') ?></textarea>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Changelog</label>
                            <textarea name="changelog" class="form-control" rows="4"><?= htmlspecialchars($plugin->changelog ?? '') ?></textarea>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Autor</label>
                            <input type="text" name="author" class="form-control" value="<?= htmlspecialchars($plugin->author ?? '') ?>">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Site do Autor</label>
                            <input type="url" name="author_uri" class="form-control" value="<?= htmlspecialchars($plugin->author_uri ?? '') ?>">
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Site do Plugin</label>
                            <input type="url" name="plugin_uri" class="form-control" value="<?= htmlspecialchars($plugin->plugin_uri ?? '') ?>">
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Requer WP</label>
                            <input type="text" name="requires_wp" class="form-control" value="<?= htmlspecialchars($plugin->requires_wp ?? '5.0') ?>">
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Testado até</label>
                            <input type="text" name="tested_wp" class="form-control" value="<?= htmlspecialchars($plugin->tested_wp ?? '6.4') ?>">
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Requer PHP</label>
                            <input type="text" name="requires_php" class="form-control" value="<?= htmlspecialchars($plugin->requires_php ?? '7.4') ?>">
                        </div>
                        
                        <div class="col-12">
                            <div class="form-check">
                                <input type="checkbox" name="is_active" class="form-check-input" id="isActive" <?= $plugin->is_active ? 'checked' : '' ?>>
                                <label class="form-check-label" for="isActive">Plugin ativo</label>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-between">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg"></i> Salvar
                            </button>
                            <a href="<?= url('/admin/plugins') ?>" class="btn btn-secondary">Cancelar</a>
                        </div>
                        
                        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
                            <i class="bi bi-trash"></i> Excluir
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Estatísticas -->
        <div class="card mt-4">
            <div class="card-header">Estatísticas</div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col">
                        <div class="fs-3 fw-bold text-primary"><?= number_format($plugin->downloads ?? 0) ?></div>
                        <small class="text-muted">Downloads</small>
                    </div>
                    <div class="col">
                        <div class="fs-3 fw-bold"><?= $plugin->created_at ? date('d/m/Y', strtotime($plugin->created_at)) : '-' ?></div>
                        <small class="text-muted">Criado em</small>
                    </div>
                    <div class="col">
                        <div class="fs-3 fw-bold"><?= $plugin->updated_at ? date('d/m/Y', strtotime($plugin->updated_at)) : '-' ?></div>
                        <small class="text-muted">Atualizado</small>
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
                <p>Tem certeza que deseja excluir o plugin <strong><?= htmlspecialchars($plugin->name) ?></strong>?</p>
                <p class="text-danger mb-0">Esta ação não pode ser desfeita.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form method="POST" action="<?= url('/admin/plugins/' . $plugin->id) ?>" class="d-inline">
                    <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="_method" value="DELETE">
                    <button type="submit" class="btn btn-danger">Excluir</button>
                </form>
            </div>
        </div>
    </div>
</div>
