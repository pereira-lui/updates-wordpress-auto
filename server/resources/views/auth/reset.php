<?php $this->layout('layouts/auth', ['title' => 'Redefinir Senha']); ?>

<p class="text-muted text-center mb-4">
    Defina sua nova senha.
</p>

<form method="POST" action="<?= url('/reset-password') ?>">
    <input type="hidden" name="_token" value="<?= csrf_token() ?>">
    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
    
    <div class="mb-3">
        <label class="form-label">Nova Senha</label>
        <input type="password" name="password" class="form-control" placeholder="Mínimo 6 caracteres" required autofocus>
    </div>
    
    <div class="mb-3">
        <label class="form-label">Confirmar Senha</label>
        <input type="password" name="password_confirmation" class="form-control" placeholder="Repita a senha" required>
    </div>
    
    <div class="d-grid mb-3">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-lg"></i> Redefinir Senha
        </button>
    </div>
    
    <div class="text-center">
        <a href="<?= url('/login') ?>" class="text-muted text-decoration-none">
            <i class="bi bi-arrow-left"></i> Voltar ao login
        </a>
    </div>
</form>
