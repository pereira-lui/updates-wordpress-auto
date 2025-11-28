<?php $this->layout('layouts/admin', ['title' => 'Plugins']); ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <a href="<?= url('/admin/plugins/create') ?>" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Novo Plugin
    </a>
</div>

<div class="row g-4">
    <?php if (empty($plugins)): ?>
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="bi bi-puzzle fs-1 text-muted mb-3 d-block"></i>
                    <h5>Nenhum plugin cadastrado</h5>
                    <p class="text-muted">Adicione seu primeiro plugin para começar.</p>
                    <a href="<?= url('/admin/plugins/create') ?>" class="btn btn-primary">
                        <i class="bi bi-plus-lg"></i> Adicionar Plugin
                    </a>
                </div>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($plugins as $plugin): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h5 class="card-title mb-1"><?= htmlspecialchars($plugin->name) ?></h5>
                                <code class="text-muted"><?= htmlspecialchars($plugin->slug) ?></code>
                            </div>
                            <span class="badge <?= $plugin->is_active ? 'bg-success' : 'bg-secondary' ?>">
                                <?= $plugin->is_active ? 'Ativo' : 'Inativo' ?>
                            </span>
                        </div>
                        
                        <p class="text-muted small mb-3">
                            <?= htmlspecialchars(substr($plugin->description ?? 'Sem descrição', 0, 100)) ?>
                            <?= strlen($plugin->description ?? '') > 100 ? '...' : '' ?>
                        </p>
                        
                        <div class="d-flex justify-content-between align-items-center text-muted small">
                            <span><i class="bi bi-tag"></i> v<?= htmlspecialchars($plugin->version) ?></span>
                            <span><i class="bi bi-download"></i> <?= number_format($plugin->downloads ?? 0) ?></span>
                        </div>
                        
                        <?php if (!$plugin->zip_file): ?>
                            <div class="alert alert-warning small py-2 mt-3 mb-0">
                                <i class="bi bi-exclamation-triangle"></i> Arquivo ZIP não enviado
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer bg-transparent border-top-0">
                        <div class="d-flex gap-2">
                            <a href="<?= url('/admin/plugins/' . $plugin->id . '/edit') ?>" class="btn btn-sm btn-outline-primary flex-fill">
                                <i class="bi bi-pencil"></i> Editar
                            </a>
                            <button type="button" class="btn btn-sm btn-outline-secondary toggle-plugin" data-id="<?= $plugin->id ?>">
                                <i class="bi bi-<?= $plugin->is_active ? 'pause' : 'play' ?>"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php $this->section('scripts'); ?>
<script>
document.querySelectorAll('.toggle-plugin').forEach(btn => {
    btn.addEventListener('click', async () => {
        const id = btn.dataset.id;
        const res = await fetch(`<?= url('/admin/plugins') ?>/${id}/toggle`, { method: 'POST' });
        const data = await res.json();
        if (data.success) {
            location.reload();
        }
    });
});
</script>
<?php $this->endSection(); ?>
