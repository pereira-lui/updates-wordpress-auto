<?php $this->layout('layouts/admin', ['title' => 'Meu Perfil']); ?>

<div class="row">
    <div class="col-lg-3 mb-4">
        <div class="list-group">
            <a href="<?= url('/admin/settings') ?>" class="list-group-item list-group-item-action">
                <i class="bi bi-gear"></i> Geral
            </a>
            <a href="<?= url('/admin/settings/profile') ?>" class="list-group-item list-group-item-action active">
                <i class="bi bi-person"></i> Meu Perfil
            </a>
            <a href="<?= url('/admin/settings/users') ?>" class="list-group-item list-group-item-action">
                <i class="bi bi-people"></i> Usuários
            </a>
            <a href="<?= url('/admin/settings/logs') ?>" class="list-group-item list-group-item-action">
                <i class="bi bi-journal-text"></i> Logs
            </a>
        </div>
    </div>
    
    <div class="col-lg-9">
        <div class="card">
            <div class="card-header">Meu Perfil</div>
            <div class="card-body">
                <form method="POST" action="<?= url('/admin/settings/profile') ?>">
                    <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nome</label>
                            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user->name) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">E-mail</label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user->email) ?>" required>
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    
                    <h5 class="mb-3">Alterar Senha</h5>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Senha Atual</label>
                            <input type="password" name="current_password" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Nova Senha</label>
                            <input type="password" name="new_password" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Confirmar Nova Senha</label>
                            <input type="password" name="confirm_password" class="form-control">
                        </div>
                    </div>
                    <small class="text-muted">Deixe em branco para manter a senha atual</small>
                    
                    <hr class="my-4">
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg"></i> Salvar Perfil
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
