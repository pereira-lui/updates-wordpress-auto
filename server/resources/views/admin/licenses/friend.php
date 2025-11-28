<?php $this->layout('layouts/admin', ['title' => 'Licença para Amigo']); ?>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card border-pink">
            <div class="card-header bg-pink text-white">
                <i class="bi bi-heart-fill"></i> Criar Licença Vitalícia para Amigo
            </div>
            <div class="card-body">
                <p class="text-muted mb-4">
                    Crie uma licença de cortesia vitalícia para um amigo. Esta licença nunca expira e dá acesso a todos os plugins.
                </p>
                
                <form method="POST" action="<?= url('/admin/licenses/friend') ?>">
                    <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Nome do Amigo *</label>
                        <input type="text" name="client_name" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">E-mail *</label>
                        <input type="email" name="client_email" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">URL do Site</label>
                        <input type="url" name="site_url" class="form-control" placeholder="https://...">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Observação</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Ex: Amigo de infância, parceiro..."></textarea>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-pink">
                            <i class="bi bi-heart"></i> Criar Licença Vitalícia
                        </button>
                        <a href="<?= url('/admin/licenses') ?>" class="btn btn-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.border-pink { border-color: #ec4899 !important; }
.bg-pink { background-color: #ec4899 !important; }
.btn-pink {
    background-color: #ec4899;
    border-color: #ec4899;
    color: white;
}
.btn-pink:hover {
    background-color: #db2777;
    border-color: #db2777;
    color: white;
}
</style>
