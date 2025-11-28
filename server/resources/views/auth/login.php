<?php $this->layout('layouts/auth', ['title' => 'Login']); ?>

<form method="POST" action="<?= url('/login') ?>">
    <input type="hidden" name="_token" value="<?= csrf_token() ?>">
    
    <div class="mb-3">
        <label class="form-label">Usuário ou E-mail</label>
        <input type="text" name="login" class="form-control" placeholder="Seu usuário ou e-mail" required autofocus>
    </div>
    
    <div class="mb-3">
        <label class="form-label">Senha</label>
        <input type="password" name="password" class="form-control" placeholder="Sua senha" required>
    </div>
    
    <div class="d-grid mb-3">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-box-arrow-in-right"></i> Entrar
        </button>
    </div>
    
    <div class="text-center">
        <a href="<?= url('/forgot-password') ?>" class="text-muted text-decoration-none">
            Esqueceu sua senha?
        </a>
    </div>
</form>
