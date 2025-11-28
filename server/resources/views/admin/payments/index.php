<?php $this->layout('layouts/admin', ['title' => 'Pagamentos']); ?>

<!-- Stats -->
<div class="row g-4 mb-4">
    <?php 
    $statusTotals = [];
    foreach ($statusStats as $stat) {
        $statusTotals[$stat->status] = $stat;
    }
    ?>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="fs-3 fw-bold">R$ <?= number_format($statusTotals['confirmed']->amount ?? 0, 2, ',', '.') ?></div>
                <div>Confirmados (<?= $statusTotals['confirmed']->total ?? 0 ?>)</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-dark">
            <div class="card-body">
                <div class="fs-3 fw-bold">R$ <?= number_format($statusTotals['pending']->amount ?? 0, 2, ',', '.') ?></div>
                <div>Pendentes (<?= $statusTotals['pending']->total ?? 0 ?>)</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-danger text-white">
            <div class="card-body">
                <div class="fs-3 fw-bold">R$ <?= number_format($statusTotals['overdue']->amount ?? 0, 2, ',', '.') ?></div>
                <div>Vencidos (<?= $statusTotals['overdue']->total ?? 0 ?>)</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-secondary text-white">
            <div class="card-body">
                <div class="fs-3 fw-bold">R$ <?= number_format($statusTotals['refunded']->amount ?? 0, 2, ',', '.') ?></div>
                <div>Reembolsados (<?= $statusTotals['refunded']->total ?? 0 ?>)</div>
            </div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">Status</option>
                    <option value="pending" <?= ($filters['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pendente</option>
                    <option value="confirmed" <?= ($filters['status'] ?? '') === 'confirmed' ? 'selected' : '' ?>>Confirmado</option>
                    <option value="overdue" <?= ($filters['status'] ?? '') === 'overdue' ? 'selected' : '' ?>>Vencido</option>
                    <option value="refunded" <?= ($filters['status'] ?? '') === 'refunded' ? 'selected' : '' ?>>Reembolsado</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="method" class="form-select">
                    <option value="">Método</option>
                    <option value="pix" <?= ($filters['method'] ?? '') === 'pix' ? 'selected' : '' ?>>PIX</option>
                    <option value="boleto" <?= ($filters['method'] ?? '') === 'boleto' ? 'selected' : '' ?>>Boleto</option>
                    <option value="credit_card" <?= ($filters['method'] ?? '') === 'credit_card' ? 'selected' : '' ?>>Cartão</option>
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" name="date_from" class="form-control" value="<?= $filters['date_from'] ?? '' ?>" placeholder="De">
            </div>
            <div class="col-md-2">
                <input type="date" name="date_to" class="form-control" value="<?= $filters['date_to'] ?? '' ?>" placeholder="Até">
            </div>
            <div class="col-md-2">
                <input type="text" name="search" class="form-control" value="<?= htmlspecialchars($filters['search'] ?? '') ?>" placeholder="Buscar...">
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-secondary flex-fill">
                    <i class="bi bi-search"></i>
                </button>
                <a href="<?= url('/admin/payments/export') ?>?<?= http_build_query($filters) ?>" class="btn btn-outline-success" title="Exportar CSV">
                    <i class="bi bi-download"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Lista -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Cliente</th>
                        <th>Plano</th>
                        <th>Valor</th>
                        <th>Método</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($payments)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                Nenhum pagamento encontrado
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td>
                                    <?= date('d/m/Y', strtotime($payment->created_at)) ?>
                                    <br><small class="text-muted"><?= date('H:i', strtotime($payment->created_at)) ?></small>
                                </td>
                                <td>
                                    <div><?= htmlspecialchars($payment->client_name ?? '-') ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($payment->client_email ?? '') ?></small>
                                </td>
                                <td><?= htmlspecialchars($payment->plan_name ?? '-') ?></td>
                                <td class="fw-bold">R$ <?= number_format($payment->amount, 2, ',', '.') ?></td>
                                <td>
                                    <?php
                                    $methodIcons = ['pix' => 'qr-code', 'boleto' => 'upc', 'credit_card' => 'credit-card'];
                                    $methodNames = ['pix' => 'PIX', 'boleto' => 'Boleto', 'credit_card' => 'Cartão'];
                                    ?>
                                    <i class="bi bi-<?= $methodIcons[$payment->payment_method] ?? 'cash' ?>"></i>
                                    <?= $methodNames[$payment->payment_method] ?? ucfirst($payment->payment_method) ?>
                                </td>
                                <td>
                                    <?php
                                    $statusClasses = [
                                        'pending' => 'warning',
                                        'confirmed' => 'success',
                                        'received' => 'success',
                                        'overdue' => 'danger',
                                        'refunded' => 'secondary',
                                        'cancelled' => 'dark'
                                    ];
                                    ?>
                                    <span class="badge bg-<?= $statusClasses[$payment->status] ?? 'secondary' ?>">
                                        <?= ucfirst($payment->status) ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?= url('/admin/payments/' . $payment->id) ?>" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
