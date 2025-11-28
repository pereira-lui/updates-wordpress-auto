<?php $this->layout('layouts/admin', ['title' => 'Detalhes do Pagamento']); ?>

<?php
$periodLabels = [
    'monthly' => 'Mensal',
    'quarterly' => 'Trimestral',
    'semiannual' => 'Semestral',
    'yearly' => 'Anual',
    'lifetime' => 'Vitalício'
];
?>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">Informações do Pagamento</div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-6">
                        <h6 class="text-muted mb-1">Cliente</h6>
                        <p class="mb-0 fs-5"><?= htmlspecialchars($payment->client_name ?? '-') ?></p>
                        <small class="text-muted"><?= htmlspecialchars($payment->client_email ?? '') ?></small>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted mb-1">Licença</h6>
                        <code><?= htmlspecialchars($payment->license_key ?? '-') ?></code>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted mb-1">Período</h6>
                        <p class="mb-0"><?= $periodLabels[$payment->period] ?? $payment->period ?? '-' ?></p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted mb-1">Valor</h6>
                        <p class="mb-0 fs-4 fw-bold text-success">R$ <?= number_format($payment->amount, 2, ',', '.') ?></p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted mb-1">Método de Pagamento</h6>
                        <p class="mb-0">
                            <?php
                            $methods = ['pix' => 'PIX', 'boleto' => 'Boleto Bancário', 'credit_card' => 'Cartão de Crédito'];
                            echo $methods[$payment->payment_method] ?? ucfirst($payment->payment_method);
                            ?>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted mb-1">ID Asaas</h6>
                        <code><?= htmlspecialchars($payment->asaas_id ?? '-') ?></code>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if ($payment->boleto_url): ?>
            <div class="card mt-4">
                <div class="card-header">Boleto</div>
                <div class="card-body">
                    <a href="<?= htmlspecialchars($payment->boleto_url) ?>" target="_blank" class="btn btn-outline-primary">
                        <i class="bi bi-file-pdf"></i> Abrir Boleto
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body text-center">
                <?php
                $statusClasses = [
                    'pending' => 'warning',
                    'confirmed' => 'success',
                    'received' => 'success',
                    'overdue' => 'danger',
                    'refunded' => 'secondary'
                ];
                $statusNames = [
                    'pending' => 'Pendente',
                    'confirmed' => 'Confirmado',
                    'received' => 'Recebido',
                    'overdue' => 'Vencido',
                    'refunded' => 'Reembolsado'
                ];
                ?>
                <span class="badge bg-<?= $statusClasses[$payment->status] ?? 'secondary' ?> fs-5 mb-3">
                    <?= $statusNames[$payment->status] ?? ucfirst($payment->status) ?>
                </span>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header">Datas</div>
            <div class="card-body">
                <p class="mb-2">
                    <strong>Criado em:</strong><br>
                    <?= date('d/m/Y H:i', strtotime($payment->created_at)) ?>
                </p>
                <?php if ($payment->due_date): ?>
                    <p class="mb-2">
                        <strong>Vencimento:</strong><br>
                        <?= date('d/m/Y', strtotime($payment->due_date)) ?>
                    </p>
                <?php endif; ?>
                <?php if ($payment->paid_at): ?>
                    <p class="mb-0">
                        <strong>Pago em:</strong><br>
                        <?= date('d/m/Y H:i', strtotime($payment->paid_at)) ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
