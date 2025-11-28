<?php $this->layout('layouts/public', ['title' => 'Pagamento Confirmado!']); ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body text-center py-5">
                    <div class="mb-4">
                        <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
                    </div>
                    
                    <h2 class="mb-3">Pagamento Confirmado!</h2>
                    <p class="text-muted mb-4">
                        Obrigado pela sua compra. Sua licença está ativa!
                    </p>
                    
                    <div class="bg-light p-4 rounded mb-4">
                        <h5 class="mb-3">Sua Chave de Licença:</h5>
                        <div class="input-group input-group-lg">
                            <input type="text" class="form-control text-center font-monospace" value="<?= htmlspecialchars($license->license_key) ?>" id="licenseKey" readonly>
                            <button class="btn btn-primary" type="button" onclick="navigator.clipboard.writeText(document.getElementById('licenseKey').value); this.innerHTML='<i class=\'bi bi-check\'></i> Copiado';">
                                <i class="bi bi-clipboard"></i> Copiar
                            </button>
                        </div>
                    </div>
                    
                    <div class="alert alert-info text-start">
                        <h6><i class="bi bi-info-circle"></i> Próximos passos:</h6>
                        <ol class="mb-0">
                            <li>Instale o plugin <strong>Premium Updates Client</strong> no seu WordPress</li>
                            <li>Vá em <strong>Configurações → Premium Updates</strong></li>
                            <li>Cole sua chave de licença e salve</li>
                            <li>Pronto! Seus plugins serão atualizados automaticamente</li>
                        </ol>
                    </div>
                    
                    <hr>
                    
                    <div class="text-start">
                        <p><strong>Plano:</strong> <?= htmlspecialchars($plan->name ?? '-') ?></p>
                        <p><strong>E-mail:</strong> <?= htmlspecialchars($license->client_email) ?></p>
                        <p class="mb-0">
                            <strong>Validade:</strong> 
                            <?php if ($license->expires_at): ?>
                                <?= date('d/m/Y', strtotime($license->expires_at)) ?>
                            <?php else: ?>
                                <span class="text-success">Vitalícia</span>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-3">
                <a href="<?= url('/plans') ?>" class="btn btn-outline-primary">
                    <i class="bi bi-arrow-left"></i> Voltar aos Planos
                </a>
            </div>
        </div>
    </div>
</div>
