<?php $this->layout('layouts/public', ['title' => 'Planos']); ?>

<div class="hero text-center">
    <div class="container">
        <h1 class="display-4 fw-bold mb-3">Plugins Premium para WordPress</h1>
        <p class="lead mb-0">Atualizações automáticas, suporte prioritário e muito mais.</p>
    </div>
</div>

<div class="container py-5">
    <div class="row justify-content-center g-4">
        <?php if (empty($plans)): ?>
            <div class="col-12 text-center">
                <p class="text-muted">Nenhum plano disponível no momento.</p>
            </div>
        <?php else: ?>
            <?php foreach ($plans as $plan): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="plan-card <?= $plan->is_featured ? 'featured' : '' ?>">
                        <h3 class="mb-3"><?= htmlspecialchars($plan->name) ?></h3>
                        
                        <div class="plan-price mb-3">
                            R$ <?= number_format($plan->price, 2, ',', '.') ?>
                            <small>
                                /<?= $plan->period === 'monthly' ? 'mês' : ($plan->period === 'yearly' ? 'ano' : 'único') ?>
                            </small>
                        </div>
                        
                        <p class="text-muted"><?= htmlspecialchars($plan->description ?? '') ?></p>
                        
                        <?php if ($plan->features): ?>
                            <ul class="plan-features my-4">
                                <?php foreach (explode("\n", $plan->features) as $feature): ?>
                                    <?php if (trim($feature)): ?>
                                        <li><i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars(trim($feature)) ?></li>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                        
                        <a href="<?= url('/checkout/' . $plan->slug) ?>" class="btn btn-primary btn-lg w-100">
                            Assinar Agora
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
