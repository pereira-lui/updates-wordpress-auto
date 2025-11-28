<?php $this->layout('layouts/admin', ['title' => 'Logs de Atividade']); ?>

<div class="row">
    <div class="col-lg-3 mb-4">
        <div class="list-group">
            <a href="<?= url('/admin/settings') ?>" class="list-group-item list-group-item-action">
                <i class="bi bi-gear"></i> Geral
            </a>
            <a href="<?= url('/admin/settings/profile') ?>" class="list-group-item list-group-item-action">
                <i class="bi bi-person"></i> Meu Perfil
            </a>
            <a href="<?= url('/admin/settings/users') ?>" class="list-group-item list-group-item-action">
                <i class="bi bi-people"></i> Usuários
            </a>
            <a href="<?= url('/admin/settings/logs') ?>" class="list-group-item list-group-item-action active">
                <i class="bi bi-journal-text"></i> Logs
            </a>
        </div>
    </div>
    
    <div class="col-lg-9">
        <!-- Filtros -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <select name="type" class="form-select">
                            <option value="">Todos os tipos</option>
                            <option value="login" <?= ($filters['type'] ?? '') === 'login' ? 'selected' : '' ?>>Login</option>
                            <option value="license_check" <?= ($filters['type'] ?? '') === 'license_check' ? 'selected' : '' ?>>Check Licença</option>
                            <option value="download" <?= ($filters['type'] ?? '') === 'download' ? 'selected' : '' ?>>Download</option>
                            <option value="payment" <?= ($filters['type'] ?? '') === 'payment' ? 'selected' : '' ?>>Pagamento</option>
                            <option value="webhook" <?= ($filters['type'] ?? '') === 'webhook' ? 'selected' : '' ?>>Webhook</option>
                            <option value="admin" <?= ($filters['type'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="date" name="date_from" class="form-control" value="<?= $filters['date_from'] ?? '' ?>" placeholder="De">
                    </div>
                    <div class="col-md-3">
                        <input type="date" name="date_to" class="form-control" value="<?= $filters['date_to'] ?? '' ?>" placeholder="Até">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-secondary w-100">
                            <i class="bi bi-search"></i> Filtrar
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Logs -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Logs de Atividade</span>
                <form method="POST" action="<?= url('/admin/settings/logs/clear') ?>" class="d-inline" onsubmit="return confirm('Limpar logs mais antigos que 90 dias?')">
                    <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="days" value="90">
                    <button type="submit" class="btn btn-sm btn-outline-danger">
                        <i class="bi bi-trash"></i> Limpar Antigos
                    </button>
                </form>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Tipo</th>
                                <th>Mensagem</th>
                                <th>IP</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">
                                        Nenhum log encontrado
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td class="text-nowrap">
                                            <?= date('d/m/Y H:i:s', strtotime($log->created_at)) ?>
                                        </td>
                                        <td>
                                            <?php
                                            $typeColors = [
                                                'login' => 'primary',
                                                'license_check' => 'info',
                                                'download' => 'success',
                                                'payment' => 'warning',
                                                'webhook' => 'secondary',
                                                'admin' => 'dark'
                                            ];
                                            ?>
                                            <span class="badge bg-<?= $typeColors[$log->type] ?? 'secondary' ?>">
                                                <?= $log->type ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($log->message) ?></td>
                                        <td><small class="text-muted"><?= htmlspecialchars($log->ip_address ?? '') ?></small></td>
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
