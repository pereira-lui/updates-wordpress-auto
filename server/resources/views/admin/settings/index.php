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
                    
                    <h5 class="mb-3"><i class="bi bi-envelope"></i> E-mail (SMTP) - Opcional</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-8">
                            <label class="form-label">Host SMTP</label>
                            <input type="text" name="smtp_host" class="form-control" value="<?= htmlspecialchars($settings['smtp_host'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Porta</label>
                            <input type="text" name="smtp_port" class="form-control" value="<?= htmlspecialchars($settings['smtp_port'] ?? '587') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Usuário SMTP</label>
                            <input type="text" name="smtp_user" class="form-control" value="<?= htmlspecialchars($settings['smtp_user'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Senha SMTP</label>
                            <input type="password" name="smtp_pass" class="form-control" value="<?= htmlspecialchars($settings['smtp_pass'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">E-mail de Envio</label>
                            <input type="email" name="smtp_from" class="form-control" value="<?= htmlspecialchars($settings['smtp_from'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nome do Remetente</label>
                            <input type="text" name="smtp_from_name" class="form-control" value="<?= htmlspecialchars($settings['smtp_from_name'] ?? '') ?>">
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
