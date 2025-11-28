<?php $this->layout('layouts/admin', ['title' => 'Relatório de Pagamentos']); ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Relatório de Receitas</h4>
    <div class="d-flex gap-2">
        <a href="<?= url('/admin/payments') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Voltar
        </a>
    </div>
</div>

<!-- Filtro de período -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Período</label>
                <select name="period" class="form-select">
                    <option value="3" <?= $period == 3 ? 'selected' : '' ?>>Últimos 3 meses</option>
                    <option value="6" <?= $period == 6 ? 'selected' : '' ?>>Últimos 6 meses</option>
                    <option value="12" <?= $period == 12 ? 'selected' : '' ?>>Últimos 12 meses</option>
                    <option value="24" <?= $period == 24 ? 'selected' : '' ?>>Últimos 24 meses</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-secondary">
                    <i class="bi bi-filter"></i> Filtrar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Resumo -->
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card bg-success text-white h-100">
            <div class="card-body text-center">
                <div class="fs-2 fw-bold">R$ <?= number_format($totalRevenue, 2, ',', '.') ?></div>
                <div>Receita Total (Confirmados)</div>
            </div>
        </div>
    </div>
    <?php 
    $statusTotals = [];
    foreach ($statusStats as $stat) {
        $statusTotals[$stat->status] = $stat;
    }
    ?>
    <div class="col-md-4">
        <div class="card bg-warning text-dark h-100">
            <div class="card-body text-center">
                <div class="fs-2 fw-bold"><?= $statusTotals['pending']->total ?? 0 ?></div>
                <div>Pagamentos Pendentes</div>
                <small>R$ <?= number_format($statusTotals['pending']->amount ?? 0, 2, ',', '.') ?></small>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-danger text-white h-100">
            <div class="card-body text-center">
                <div class="fs-2 fw-bold"><?= $statusTotals['overdue']->total ?? 0 ?></div>
                <div>Pagamentos Vencidos</div>
                <small>R$ <?= number_format($statusTotals['overdue']->amount ?? 0, 2, ',', '.') ?></small>
            </div>
        </div>
    </div>
</div>

<!-- Gráfico de Receita -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Receita por Mês</h5>
    </div>
    <div class="card-body">
        <?php if (empty($revenueByMonth)): ?>
            <div class="text-center text-muted py-5">
                <i class="bi bi-bar-chart fs-1 d-block mb-2"></i>
                Nenhum dado disponível
            </div>
        <?php else: ?>
            <canvas id="revenueChart" height="100"></canvas>
        <?php endif; ?>
    </div>
</div>

<!-- Tabela de Receita por Mês -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Detalhamento por Mês</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Mês</th>
                        <th class="text-end">Qtd. Pagamentos</th>
                        <th class="text-end">Receita</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($revenueByMonth)): ?>
                        <tr>
                            <td colspan="3" class="text-center text-muted py-4">
                                Nenhum dado disponível
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php 
                        $meses = [
                            '01' => 'Janeiro', '02' => 'Fevereiro', '03' => 'Março', 
                            '04' => 'Abril', '05' => 'Maio', '06' => 'Junho',
                            '07' => 'Julho', '08' => 'Agosto', '09' => 'Setembro',
                            '10' => 'Outubro', '11' => 'Novembro', '12' => 'Dezembro'
                        ];
                        foreach (array_reverse($revenueByMonth) as $row): 
                            $parts = explode('-', $row->month);
                            $mesNome = $meses[$parts[1]] . '/' . $parts[0];
                        ?>
                            <tr>
                                <td><?= $mesNome ?></td>
                                <td class="text-end"><?= $row->count ?></td>
                                <td class="text-end fw-bold text-success">R$ <?= number_format($row->total, 2, ',', '.') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if (!empty($revenueByMonth)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('revenueChart').getContext('2d');
const meses = {
    '01': 'Jan', '02': 'Fev', '03': 'Mar', '04': 'Abr', '05': 'Mai', '06': 'Jun',
    '07': 'Jul', '08': 'Ago', '09': 'Set', '10': 'Out', '11': 'Nov', '12': 'Dez'
};

const data = <?= json_encode($revenueByMonth) ?>;
const labels = data.map(item => {
    const parts = item.month.split('-');
    return meses[parts[1]] + '/' + parts[0].substr(2);
});
const values = data.map(item => parseFloat(item.total));

new Chart(ctx, {
    type: 'bar',
    data: {
        labels: labels,
        datasets: [{
            label: 'Receita (R$)',
            data: values,
            backgroundColor: 'rgba(40, 167, 69, 0.7)',
            borderColor: 'rgba(40, 167, 69, 1)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return 'R$ ' + value.toLocaleString('pt-BR', {minimumFractionDigits: 2});
                    }
                }
            }
        },
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return 'R$ ' + context.parsed.y.toLocaleString('pt-BR', {minimumFractionDigits: 2});
                    }
                }
            }
        }
    }
});
</script>
<?php endif; ?>
