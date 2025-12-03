<?php $this->layout('layouts/admin', ['title' => 'Configurações']); ?>

<div class="row">
    <div class="col-lg-3 mb-4">
        <div class="list-group">
            <a href="<?= url('/admin/settings') ?>" class="list-group-item list-group-item-action active">
                <i class="bi bi-gear"></i> Geral
            </a>
            <a href="<?= url('/admin/settings/profile') ?>" class="list-group-item list-group-item-action">
                <i class="bi bi-person"></i> Meu Perfil
            </a>
            <a href="<?= url('/admin/settings/users') ?>" class="list-group-item list-group-item-action">
                <i class="bi bi-people"></i> Usuários
            </a>
            <a href="<?= url('/admin/settings/logs') ?>" class="list-group-item list-group-item-action">
                <i class="bi bi-journal-text"></i> Logs
            </a>
        </div>
    </div>
    
    <div class="col-lg-9">
        <div class="card mb-4">
            <div class="card-header">Configurações Gerais</div>
            <div class="card-body">
                <form method="POST" action="<?= url('/admin/settings') ?>">
                    <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                    
                    <h5 class="mb-3">Site</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Nome do Site</label>
                            <input type="text" name="site_name" class="form-control" value="<?= htmlspecialchars($settings['site_name'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">URL do Site</label>
                            <input type="url" name="site_url" class="form-control" value="<?= htmlspecialchars($settings['site_url'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">E-mail do Admin</label>
                            <input type="email" name="admin_email" class="form-control" value="<?= htmlspecialchars($settings['admin_email'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <hr>
                    
                    <h5 class="mb-3"><i class="bi bi-calendar-check"></i> Preços das Assinaturas</h5>
                    <p class="text-muted small">Defina os valores para cada período de assinatura que os clientes podem contratar.</p>
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <label class="form-label">Mensal (R$)</label>
                            <input type="number" step="0.01" name="price_monthly" class="form-control" value="<?= htmlspecialchars($settings['price_monthly'] ?? '29.90') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Trimestral (R$)</label>
                            <input type="number" step="0.01" name="price_quarterly" class="form-control" value="<?= htmlspecialchars($settings['price_quarterly'] ?? '79.90') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Semestral (R$)</label>
                            <input type="number" step="0.01" name="price_semiannual" class="form-control" value="<?= htmlspecialchars($settings['price_semiannual'] ?? '149.90') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Anual (R$)</label>
                            <input type="number" step="0.01" name="price_yearly" class="form-control" value="<?= htmlspecialchars($settings['price_yearly'] ?? '249.90') ?>">
                        </div>
                    </div>
                    
                    <hr>
                    
                    <h5 class="mb-3"><i class="bi bi-credit-card"></i> Asaas (Pagamentos)</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <label class="form-label">API Key</label>
                            <input type="password" name="asaas_api_key" class="form-control" value="<?= htmlspecialchars($settings['asaas_api_key'] ?? '') ?>" placeholder="$aact_...">
                            <small class="text-muted">Pegue sua API Key em: Asaas → Minha Conta → Integrações → API</small>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input type="checkbox" name="asaas_sandbox" class="form-check-input" id="asaasSandbox" <?= ($settings['asaas_sandbox'] ?? '0') === '1' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="asaasSandbox">Modo Sandbox (testes)</label>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <h5 class="mb-3"><i class="bi bi-envelope"></i> E-mail (SMTP)</h5>
                    <p class="text-muted small">Configure seu servidor SMTP para envio de notificações por email.</p>
                    <div class="row g-3 mb-4">
                        <div class="col-md-8">
                            <label class="form-label">Host SMTP</label>
                            <input type="text" name="smtp_host" class="form-control" value="<?= htmlspecialchars($settings['smtp_host'] ?? '') ?>" placeholder="smtp.gmail.com">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Porta</label>
                            <input type="text" name="smtp_port" class="form-control" value="<?= htmlspecialchars($settings['smtp_port'] ?? '587') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Usuário SMTP</label>
                            <input type="text" name="smtp_user" class="form-control" value="<?= htmlspecialchars($settings['smtp_user'] ?? '') ?>" placeholder="seu@email.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Senha SMTP</label>
                            <input type="password" name="smtp_pass" class="form-control" value="<?= htmlspecialchars($settings['smtp_pass'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">E-mail de Envio (From)</label>
                            <input type="email" name="smtp_from" class="form-control" value="<?= htmlspecialchars($settings['smtp_from'] ?? '') ?>" placeholder="noreply@seudominio.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nome do Remetente</label>
                            <input type="text" name="smtp_from_name" class="form-control" value="<?= htmlspecialchars($settings['smtp_from_name'] ?? '') ?>" placeholder="Premium Updates">
                        </div>
                        <div class="col-12">
                            <button type="button" id="testSmtp" class="btn btn-outline-secondary">
                                <i class="bi bi-send"></i> Testar Configuração
                            </button>
                            <span id="smtpTestResult" class="ms-2"></span>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <h5 class="mb-3"><i class="bi bi-bell"></i> Notificações do Admin</h5>
                    <p class="text-muted small">Configure quais notificações você deseja receber.</p>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Email para Notificações</label>
                            <input type="email" name="notify_admin_email" class="form-control" value="<?= htmlspecialchars($settings['notify_admin_email'] ?? $settings['admin_email'] ?? '') ?>" placeholder="admin@seudominio.com">
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input type="checkbox" name="notify_admin_updates" class="form-check-input" id="notifyAdminUpdates" value="1" <?= ($settings['notify_admin_updates'] ?? '1') === '1' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="notifyAdminUpdates">
                                    Receber notificação quando um cliente atualizar plugin
                                </label>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input type="checkbox" name="notify_admin_errors" class="form-check-input" id="notifyAdminErrors" value="1" <?= ($settings['notify_admin_errors'] ?? '1') === '1' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="notifyAdminErrors">
                                    Receber notificação quando ocorrer erro em atualização
                                </label>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input type="checkbox" name="notify_admin_rollbacks" class="form-check-input" id="notifyAdminRollbacks" value="1" <?= ($settings['notify_admin_rollbacks'] ?? '1') === '1' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="notifyAdminRollbacks">
                                    Receber notificação quando ocorrer rollback
                                </label>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input type="checkbox" name="notify_admin_payments" class="form-check-input" id="notifyAdminPayments" value="1" <?= ($settings['notify_admin_payments'] ?? '1') === '1' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="notifyAdminPayments">
                                    Receber notificação de novos pagamentos
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg"></i> Salvar Configurações
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php $this->section('scripts'); ?>
<script>
document.getElementById('testSmtp').addEventListener('click', async () => {
    const btn = document.getElementById('testSmtp');
    const result = document.getElementById('smtpTestResult');
    
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Testando...';
    result.innerHTML = '';
    
    try {
        const res = await fetch('<?= url('/admin/settings/test-smtp') ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: '_token=<?= csrf_token() ?>'
        });
        const data = await res.json();
        
        if (data.success) {
            result.innerHTML = '<span class="text-success"><i class="bi bi-check-circle"></i> ' + data.message + '</span>';
        } else {
            result.innerHTML = '<span class="text-danger"><i class="bi bi-x-circle"></i> ' + data.message + '</span>';
        }
    } catch (e) {
        result.innerHTML = '<span class="text-danger"><i class="bi bi-x-circle"></i> Erro de conexão</span>';
    }
    
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-send"></i> Testar Configuração';
});
</script>
<?php $this->endSection(); ?>
