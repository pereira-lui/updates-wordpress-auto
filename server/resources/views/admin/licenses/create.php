<?php $this->layout('layouts/admin', ['title' => 'Nova Licença']); ?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-key"></i> Criar Nova Licença
            </div>
            <div class="card-body">
                <form method="POST" action="<?= url('/admin/licenses') ?>">
                    <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nome do Cliente *</label>
                            <input type="text" name="client_name" class="form-control" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">E-mail *</label>
                            <input type="email" name="client_email" class="form-control" required>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">URL do Site</label>
                            <input type="url" name="site_url" class="form-control" placeholder="https://exemplo.com.br">
                            <small class="text-muted">Opcional. O cliente pode ativar depois.</small>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Tipo *</label>
                            <select name="type" class="form-select" id="licenseType" required>
                                <option value="paid">Paga</option>
                                <option value="lifetime">Vitalícia</option>
                                <option value="friend">Amigo (Cortesia)</option>
                                <option value="trial">Trial</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="active">Ativa</option>
                                <option value="pending">Pendente</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Plano</label>
                            <select name="plan_id" class="form-select">
                                <option value="">Sem plano (todos os plugins)</option>
                                <?php foreach ($plans as $plan): ?>
                                    <option value="<?= $plan->id ?>">
                                        <?= htmlspecialchars($plan->name) ?> - R$ <?= number_format($plan->price, 2, ',', '.') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6" id="expiresField">
                            <label class="form-label">Data de Expiração</label>
                            <input type="date" name="expires_at" class="form-control">
                            <small class="text-muted">Deixe vazio para usar período do plano</small>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Observações</label>
                            <textarea name="notes" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg"></i> Criar Licença
                        </button>
                        <a href="<?= url('/admin/licenses') ?>" class="btn btn-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php $this->section('scripts'); ?>
<script>
document.getElementById('licenseType').addEventListener('change', function() {
    const expiresField = document.getElementById('expiresField');
    if (this.value === 'lifetime' || this.value === 'friend') {
        expiresField.style.display = 'none';
    } else {
        expiresField.style.display = 'block';
    }
});
</script>
<?php $this->endSection(); ?>
