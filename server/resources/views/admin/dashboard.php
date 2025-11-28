<?php $this->layout('layouts/admin', ['title' => 'Dashboard']); ?>

<!-- Stats Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-6 col-lg-3">
        <div class="card stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-value"><?= $stats['licenses']['active'] ?></div>
                    <div class="stat-label">Licenças Ativas</div>
                </div>
                <div class="stat-icon bg-success bg-opacity-10 text-success">
                    <i class="bi bi-key"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 col-lg-3">
        <div class="card stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-value"><?= $stats['plugins']['total'] ?></div>
                    <div class="stat-label">Plugins</div>
                </div>
                <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                    <i class="bi bi-puzzle"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 col-lg-3">
        <div class="card stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-value"><?= $stats['plans']['total'] ?></div>
                    <div class="stat-label">Planos</div>
                </div>
                <div class="stat-icon bg-info bg-opacity-10 text-info">
                    <i class="bi bi-tags"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 col-lg-3">
        <div class="card stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-value">R$ <?= number_format($stats['payments']['revenue'], 2, ',', '.') ?></div>
                    <div class="stat-label">Receita Total</div>
                </div>
                <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                    <i class="bi bi-currency-dollar"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Gráfico de Receita -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Receita Mensal</span>
            </div>
            <div class="card-body">
                <canvas id="revenueChart" height="100"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Licenças por Status -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">Licenças por Status</div>
            <div class="card-body">
                <canvas id="licensesChart" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mt-2">
    <!-- Pagamentos Recentes -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Pagamentos Recentes</span>
                <a href="<?= url('/admin/payments') ?>" class="btn btn-sm btn-outline-primary">Ver todos</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Cliente</th>
                                <th>Valor</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentPayments)): ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4">
                                        Nenhum pagamento ainda
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentPayments as $payment): ?>
                                    <tr>
                                        <td>
                                            <div><?= htmlspecialchars($payment->client_name ?? '-') ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($payment->client_email ?? '') ?></small>
                                        </td>
                                        <td>R$ <?= number_format($payment->amount, 2, ',', '.') ?></td>
                                        <td>
                                            <span class="badge badge-<?= $payment->status ?>">
                                                <?= ucfirst($payment->status) ?>
                                            </span>
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
    
    <!-- Atividade de Licenças -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Atividade Recente</span>
                <a href="<?= url('/admin/licenses') ?>" class="btn btn-sm btn-outline-primary">Ver todas</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Cliente</th>
                                <th>Site</th>
                                <th>Último Check</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentActivity)): ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4">
                                        Nenhuma atividade ainda
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentActivity as $license): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($license->client_name) ?></td>
                                        <td>
                                            <small class="text-muted"><?= htmlspecialchars($license->site_url ?: '-') ?></small>
                                        </td>
                                        <td>
                                            <small><?= $license->last_check_at ? date('d/m H:i', strtotime($license->last_check_at)) : '-' ?></small>
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
</div>

<?php $this->section('scripts'); ?>
<script>
// Gráfico de Receita
const revenueData = <?= json_encode($revenueByMonth) ?>;
const revenueLabels = revenueData.map(r => r.month);
const revenueValues = revenueData.map(r => parseFloat(r.total));

new Chart(document.getElementById('revenueChart'), {
    type: 'line',
    data: {
        labels: revenueLabels,
        datasets: [{
            label: 'Receita',
            data: revenueValues,
            borderColor: '#6366f1',
            backgroundColor: 'rgba(99, 102, 241, 0.1)',
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: value => 'R$ ' + value.toFixed(2)
                }
            }
        }
    }
});

// Gráfico de Licenças
const licenseStats = <?= json_encode($stats['licenses']['by_status']) ?>;
const licenseLabels = licenseStats.map(s => s.status.charAt(0).toUpperCase() + s.status.slice(1));
const licenseValues = licenseStats.map(s => parseInt(s.total));
const licenseColors = {
    'Active': '#10b981',
    'Pending': '#f59e0b',
    'Expired': '#ef4444',
    'Cancelled': '#6b7280'
};

new Chart(document.getElementById('licensesChart'), {
    type: 'doughnut',
    data: {
        labels: licenseLabels,
        datasets: [{
            data: licenseValues,
            backgroundColor: licenseLabels.map(l => licenseColors[l] || '#6b7280')
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});
</script>
<?php $this->endSection(); ?>
