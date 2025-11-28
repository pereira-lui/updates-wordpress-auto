<?php $this->layout('layouts/admin', ['title' => 'Novo Usuário']); ?>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-person-plus"></i> Criar Novo Usuário
            </div>
            <div class="card-body">
                <form method="POST" action="<?= url('/admin/settings/users') ?>">
                    <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Nome *</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Username *</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">E-mail *</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Senha *</label>
                        <input type="password" name="password" class="form-control" minlength="6" required>
                        <small class="text-muted">Mínimo 6 caracteres</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Função</label>
                        <select name="role" class="form-select">
                            <option value="admin">Administrador</option>
                        </select>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg"></i> Criar Usuário
                        </button>
                        <a href="<?= url('/admin/settings/users') ?>" class="btn btn-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
