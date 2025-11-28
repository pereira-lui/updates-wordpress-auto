<?php $this->layout('layouts/admin', ['title' => 'Planos']); ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <a href="<?= url('/admin/plans/create') ?>" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Novo Plano
    </a>
</div>

<div class="row g-4">
    <?php if (empty($plans)): ?>
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="bi bi-tags fs-1 text-muted mb-3 d-block"></i>
                    <h5>Nenhum plano cadastrado</h5>
                    <p class="text-muted">Crie planos para vender seus plugins.</p>
                    <a href="<?= url('/admin/plans/create') ?>" class="btn btn-primary">
                        <i class="bi bi-plus-lg"></i> Criar Plano
                    </a>
                </div>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($plans as $plan): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 <?= $plan->is_featured ? 'border-primary' : '' ?>">
                    <?php if ($plan->is_featured): ?>
                        <div class="card-header bg-primary text-white text-center">
                            <i class="bi bi-star-fill"></i> Destaque
                        </div>
                    <?php endif; ?>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <h5 class="card-title mb-0"><?= htmlspecialchars($plan->name) ?></h5>
                            <span class="badge <?= $plan->is_active ? 'bg-success' : 'bg-secondary' ?>">
                                <?= $plan->is_active ? 'Ativo' : 'Inativo' ?>
                            </span>
                        </div>
                        
                        <div class="mb-3">
                            <span class="fs-2 fw-bold">R$ <?= number_format($plan->price, 2, ',', '.') ?></span>
                            <span class="text-muted">/<?= $plan->period === 'monthly' ? 'mês' : ($plan->period === 'yearly' ? 'ano' : 'único') ?></span>
                        </div>
                        
                        <p class="text-muted small"><?= htmlspecialchars($plan->description ?? '') ?></p>
                        
                        <?php if (!empty($plan->plugins)): ?>
                            <h6 class="mt-3 mb-2">Plugins inclusos:</h6>
                            <ul class="list-unstyled small">
                                <?php foreach ($plan->plugins as $plugin): ?>
                                    <li><i class="bi bi-check text-success"></i> <?= htmlspecialchars($plugin->name) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer bg-transparent">
                        <div class="d-flex gap-2">
                            <a href="<?= url('/admin/plans/' . $plan->id . '/edit') ?>" class="btn btn-sm btn-outline-primary flex-fill">
                                <i class="bi bi-pencil"></i> Editar
                            </a>
                            <button type="button" class="btn btn-sm btn-outline-secondary toggle-plan" data-id="<?= $plan->id ?>">
                                <i class="bi bi-<?= $plan->is_active ? 'pause' : 'play' ?>"></i>
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
document.querySelectorAll('.toggle-plan').forEach(btn => {
    btn.addEventListener('click', async () => {
        const id = btn.dataset.id;
        const res = await fetch(`<?= url('/admin/plans') ?>/${id}/toggle`, { method: 'POST' });
        const data = await res.json();
        if (data.success) {
            location.reload();
        }
    });
});
</script>
<?php $this->endSection(); ?>
