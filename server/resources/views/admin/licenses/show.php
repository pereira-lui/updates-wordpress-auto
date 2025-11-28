<?php $this->layout('layouts/admin', ['title' => 'Detalhes da Licença']); ?>

<div class="row">
    <div class="col-lg-8">
        <!-- Info Principal -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Informações da Licença</span>
                <a href="<?= url('/admin/licenses/' . $license->id . '/edit') ?>" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-pencil"></i> Editar
                </a>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-6">
                        <h6 class="text-muted mb-1">Cliente</h6>
                        <p class="mb-0 fs-5"><?= htmlspecialchars($license->client_name) ?></p>
                        <small class="text-muted"><?= htmlspecialchars($license->client_email) ?></small>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted mb-1">Chave de Licença</h6>
                        <code class="fs-5 user-select-all"><?= htmlspecialchars($license->license_key) ?></code>
                        <button class="btn btn-sm btn-link copy-btn" data-copy="<?= htmlspecialchars($license->license_key) ?>">
                            <i class="bi bi-clipboard"></i>
                        </button>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted mb-1">Site Ativo</h6>
                        <p class="mb-0">
                            <?php if ($license->site_url): ?>
                                <a href="<?= htmlspecialchars($license->site_url) ?>" target="_blank">
                                    <?= htmlspecialchars($license->site_url) ?>
                                    <i class="bi bi-box-arrow-up-right"></i>
                                </a>
                            <?php else: ?>
                                <span class="text-muted">Não ativado</span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted mb-1">Plano</h6>
                        <p class="mb-0"><?= htmlspecialchars($license->plan_name ?? 'Todos os plugins') ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Histórico -->
        <div class="card">
            <div class="card-header">Histórico de Atividade</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Ação</th>
                                <th>Detalhes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4">
                                        Nenhum registro de atividade
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td><?= date('d/m/Y H:i', strtotime($log->created_at)) ?></td>
                                        <td><?= htmlspecialchars($log->message) ?></td>
                                        <td>
                                            <small class="text-muted"><?= htmlspecialchars($log->ip_address ?? '') ?></small>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <!-- Status -->
        <div class="card mb-4">
            <div class="card-body text-center">
                <span class="badge badge-<?= $license->status ?> fs-5 mb-3">
                    <?= ucfirst($license->status) ?>
                </span>
                <br>
                <span class="badge badge-<?= $license->type ?>">
                    Tipo: <?= ucfirst($license->type) ?>
                </span>
            </div>
        </div>
        
        <!-- Datas -->
        <div class="card mb-4">
            <div class="card-header">Datas</div>
            <div class="card-body">
                <p class="mb-2">
                    <i class="bi bi-calendar-plus text-muted"></i>
                    <strong>Criada:</strong><br>
                    <?= date('d/m/Y H:i', strtotime($license->created_at)) ?>
                </p>
                <p class="mb-2">
                    <i class="bi bi-calendar-check text-muted"></i>
                    <strong>Ativada:</strong><br>
                    <?= $license->activated_at ? date('d/m/Y H:i', strtotime($license->activated_at)) : '-' ?>
                </p>
                <p class="mb-2">
                    <i class="bi bi-calendar-x text-muted"></i>
                    <strong>Expira:</strong><br>
                    <?php if ($license->expires_at): ?>
                        <?= date('d/m/Y', strtotime($license->expires_at)) ?>
                        <?php if (strtotime($license->expires_at) < time()): ?>
                            <span class="badge bg-danger">Expirada</span>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="text-success">Vitalícia</span>
                    <?php endif; ?>
                </p>
                <p class="mb-0">
                    <i class="bi bi-activity text-muted"></i>
                    <strong>Último check:</strong><br>
                    <?= $license->last_check_at ? date('d/m/Y H:i', strtotime($license->last_check_at)) : 'Nunca' ?>
                </p>
            </div>
        </div>
        
        <!-- Ações -->
        <div class="card">
            <div class="card-header">Ações</div>
            <div class="card-body d-grid gap-2">
                <button type="button" class="btn btn-outline-primary" id="regenerateKey">
                    <i class="bi bi-arrow-repeat"></i> Regenerar Chave
                </button>
                <button type="button" class="btn btn-outline-<?= $license->status === 'active' ? 'warning' : 'success' ?>" id="toggleStatus">
                    <i class="bi bi-<?= $license->status === 'active' ? 'pause' : 'play' ?>"></i>
                    <?= $license->status === 'active' ? 'Desativar' : 'Ativar' ?>
                </button>
            </div>
        </div>
    </div>
</div>

<?php $this->section('scripts'); ?>
<script>
// Copiar
document.querySelectorAll('.copy-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        navigator.clipboard.writeText(btn.dataset.copy);
        btn.innerHTML = '<i class="bi bi-check text-success"></i>';
        setTimeout(() => btn.innerHTML = '<i class="bi bi-clipboard"></i>', 2000);
    });
});

// Regenerar chave
document.getElementById('regenerateKey').addEventListener('click', async () => {
    if (!confirm('Regenerar a chave de licença? O cliente precisará atualizar a chave no site.')) return;
    
    const res = await fetch('<?= url('/admin/licenses/' . $license->id . '/regenerate-key') ?>', { method: 'POST' });
    const data = await res.json();
    
    if (data.success) {
        alert('Nova chave: ' + data.license_key);
        location.reload();
    }
});

// Toggle status
document.getElementById('toggleStatus').addEventListener('click', async () => {
    const res = await fetch('<?= url('/admin/licenses/' . $license->id . '/toggle') ?>', { method: 'POST' });
    const data = await res.json();
    
    if (data.success) {
        location.reload();
    }
});
</script>
<?php $this->endSection(); ?>
