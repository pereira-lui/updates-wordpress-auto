<?php $this->layout('layouts/admin', ['title' => 'Licenças']); ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex gap-2">
        <a href="<?= url('/admin/licenses/create') ?>" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Nova Licença
        </a>
        <a href="<?= url('/admin/licenses/friend') ?>" class="btn btn-outline-pink">
            <i class="bi bi-heart"></i> Licença para Amigo
        </a>
    </div>
</div>

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">Todos os status</option>
                    <option value="active" <?= ($filters['status'] ?? '') === 'active' ? 'selected' : '' ?>>Ativa</option>
                    <option value="pending" <?= ($filters['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pendente</option>
                    <option value="expired" <?= ($filters['status'] ?? '') === 'expired' ? 'selected' : '' ?>>Expirada</option>
                    <option value="cancelled" <?= ($filters['status'] ?? '') === 'cancelled' ? 'selected' : '' ?>>Cancelada</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="type" class="form-select">
                    <option value="">Todos os tipos</option>
                    <option value="paid" <?= ($filters['type'] ?? '') === 'paid' ? 'selected' : '' ?>>Paga</option>
                    <option value="lifetime" <?= ($filters['type'] ?? '') === 'lifetime' ? 'selected' : '' ?>>Vitalícia</option>
                    <option value="friend" <?= ($filters['type'] ?? '') === 'friend' ? 'selected' : '' ?>>Amigo</option>
                    <option value="trial" <?= ($filters['type'] ?? '') === 'trial' ? 'selected' : '' ?>>Trial</option>
                </select>
            </div>
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Buscar por nome, email, site..." value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-secondary w-100">
                    <i class="bi bi-search"></i> Filtrar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Lista de Licenças -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Chave</th>
                        <th>Plano</th>
                        <th>Tipo</th>
                        <th>Status</th>
                        <th>Expira em</th>
                        <th width="150">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($licenses)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                Nenhuma licença encontrada
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($licenses as $license): ?>
                            <tr>
                                <td>
                                    <div class="fw-medium"><?= htmlspecialchars($license->client_name) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($license->client_email) ?></small>
                                    <?php if ($license->site_url): ?>
                                        <br><small class="text-info"><?= htmlspecialchars($license->site_url) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <code class="user-select-all"><?= htmlspecialchars($license->license_key) ?></code>
                                    <button class="btn btn-sm btn-link p-0 ms-1 copy-btn" data-copy="<?= htmlspecialchars($license->license_key) ?>" title="Copiar">
                                        <i class="bi bi-clipboard"></i>
                                    </button>
                                </td>
                                <td><?= htmlspecialchars($license->plan_name ?? '-') ?></td>
                                <td>
                                    <span class="badge badge-<?= $license->type ?>">
                                        <?= ucfirst($license->type) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-<?= $license->status ?>">
                                        <?= ucfirst($license->status) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($license->expires_at): ?>
                                        <?= date('d/m/Y', strtotime($license->expires_at)) ?>
                                    <?php else: ?>
                                        <span class="text-success">Vitalícia</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= url('/admin/licenses/' . $license->id) ?>" class="btn btn-outline-secondary" title="Ver">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="<?= url('/admin/licenses/' . $license->id . '/edit') ?>" class="btn btn-outline-secondary" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button type="button" class="btn btn-outline-secondary toggle-status" data-id="<?= $license->id ?>" data-status="<?= $license->status ?>" title="<?= $license->status === 'active' ? 'Desativar' : 'Ativar' ?>">
                                            <i class="bi bi-<?= $license->status === 'active' ? 'pause' : 'play' ?>"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.btn-outline-pink {
    color: #ec4899;
    border-color: #ec4899;
}
.btn-outline-pink:hover {
    background: #ec4899;
    color: white;
}
</style>

<?php $this->section('scripts'); ?>
<script>
// Copiar para clipboard
document.querySelectorAll('.copy-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        navigator.clipboard.writeText(btn.dataset.copy);
        btn.innerHTML = '<i class="bi bi-check text-success"></i>';
        setTimeout(() => {
            btn.innerHTML = '<i class="bi bi-clipboard"></i>';
        }, 2000);
    });
});

// Toggle status
document.querySelectorAll('.toggle-status').forEach(btn => {
    btn.addEventListener('click', async () => {
        const id = btn.dataset.id;
        const res = await fetch(`<?= url('/admin/licenses') ?>/${id}/toggle`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' }
        });
        const data = await res.json();
        if (data.success) {
            location.reload();
        }
    });
});
</script>
<?php $this->endSection(); ?>
