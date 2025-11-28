<?php $this->layout('layouts/auth', ['title' => 'Recuperar Senha']); ?>

<p class="text-muted text-center mb-4">
    Informe seu e-mail para receber um link de recuperação de senha.
</p>

<form method="POST" action="<?= url('/forgot-password') ?>">
    <input type="hidden" name="_token" value="<?= csrf_token() ?>">
    
    <div class="mb-3">
        <label class="form-label">E-mail</label>
        <input type="email" name="email" class="form-control" placeholder="Seu e-mail" required autofocus>
    </div>
    
    <div class="d-grid mb-3">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-envelope"></i> Enviar Link
        </button>
    </div>
    
    <div class="text-center">
        <a href="<?= url('/login') ?>" class="text-muted text-decoration-none">
            <i class="bi bi-arrow-left"></i> Voltar ao login
        </a>
    </div>
</form>
