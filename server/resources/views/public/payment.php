<?php $this->layout('layouts/public', ['title' => 'Pagamento']); ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header text-center">
                    <h4 class="mb-0">Aguardando Pagamento</h4>
                </div>
                <div class="card-body text-center">
                    <div class="mb-4">
                        <span class="badge bg-warning fs-6">Pendente</span>
                    </div>
                    
                    <h5><?= htmlspecialchars($plan->name ?? 'Plano') ?></h5>
                    <p class="fs-3 fw-bold text-success mb-4">
                        R$ <?= number_format($payment->amount, 2, ',', '.') ?>
                    </p>
                    
                    <?php if ($payment->payment_method === 'pix' && $pixQrCode): ?>
                        <div class="mb-4">
                            <p class="text-muted mb-3">Escaneie o QR Code para pagar:</p>
                            <?php if (!empty($pixQrCode['encodedImage'])): ?>
                                <img src="data:image/png;base64,<?= $pixQrCode['encodedImage'] ?>" alt="QR Code PIX" class="img-fluid mb-3" style="max-width: 250px;">
                            <?php endif; ?>
                            <?php if (!empty($pixQrCode['payload'])): ?>
                                <div class="input-group">
                                    <input type="text" class="form-control" value="<?= htmlspecialchars($pixQrCode['payload']) ?>" id="pixCode" readonly>
                                    <button class="btn btn-outline-secondary" type="button" onclick="navigator.clipboard.writeText(document.getElementById('pixCode').value); this.innerHTML='<i class=\'bi bi-check\'></i>';">
                                        <i class="bi bi-clipboard"></i>
                                    </button>
                                </div>
                                <small class="text-muted">Clique para copiar o código PIX</small>
                            <?php endif; ?>
                        </div>
                    <?php elseif ($payment->payment_method === 'boleto' && $payment->boleto_url): ?>
                        <div class="mb-4">
                            <a href="<?= htmlspecialchars($payment->boleto_url) ?>" target="_blank" class="btn btn-lg btn-outline-primary">
                                <i class="bi bi-file-pdf"></i> Visualizar Boleto
                            </a>
                        </div>
                    <?php elseif ($payment->payment_method === 'credit_card'): ?>
                        <div class="mb-4">
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i> Seu pagamento está sendo processado. Aguarde a confirmação.
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <hr>
                    
                    <div class="text-start">
                        <p class="mb-1"><strong>Licença:</strong></p>
                        <code class="d-block mb-3 p-2 bg-light"><?= htmlspecialchars($license->license_key) ?></code>
                        
                        <p class="mb-1"><strong>E-mail:</strong></p>
                        <p><?= htmlspecialchars($license->client_email) ?></p>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        Após a confirmação do pagamento, você receberá as instruções por e-mail.
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-3">
                <p class="text-muted" id="checkingStatus">
                    <span class="spinner-border spinner-border-sm"></span>
                    Verificando pagamento...
                </p>
            </div>
        </div>
    </div>
</div>

<?php $this->section('scripts'); ?>
<script>
// Verifica status a cada 5 segundos
setInterval(async () => {
    const res = await fetch('<?= url('/checkout/status/' . $license->id) ?>');
    const data = await res.json();
    
    if (data.status === 'active') {
        window.location.href = '<?= url('/checkout/success/' . $license->id) ?>';
    }
}, 5000);
</script>
<?php $this->endSection(); ?>
