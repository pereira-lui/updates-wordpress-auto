<?php $this->layout('layouts/public', ['title' => 'Checkout - ' . $plan->name]); ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="row g-4">
                <!-- Formulário -->
                <div class="col-md-7">
                    <div class="card">
                        <div class="card-header">
                            <i class="bi bi-cart"></i> Finalizar Compra
                        </div>
                        <div class="card-body">
                            <form method="POST" action="<?= url('/checkout/' . $plan->slug) ?>">
                                <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                                
                                <div class="mb-3">
                                    <label class="form-label">Nome Completo *</label>
                                    <input type="text" name="name" class="form-control" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">E-mail *</label>
                                    <input type="email" name="email" class="form-control" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">CPF/CNPJ</label>
                                    <input type="text" name="document" class="form-control" placeholder="Opcional">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">URL do Site (onde usará os plugins)</label>
                                    <input type="url" name="site_url" class="form-control" placeholder="https://seusite.com.br">
                                </div>
                                
                                <hr>
                                
                                <div class="mb-4">
                                    <label class="form-label">Forma de Pagamento *</label>
                                    <div class="row g-2">
                                        <div class="col-4">
                                            <input type="radio" name="payment_method" value="pix" class="btn-check" id="pix" required checked>
                                            <label class="btn btn-outline-primary w-100" for="pix">
                                                <i class="bi bi-qr-code"></i><br>
                                                PIX
                                            </label>
                                        </div>
                                        <div class="col-4">
                                            <input type="radio" name="payment_method" value="boleto" class="btn-check" id="boleto">
                                            <label class="btn btn-outline-primary w-100" for="boleto">
                                                <i class="bi bi-upc"></i><br>
                                                Boleto
                                            </label>
                                        </div>
                                        <div class="col-4">
                                            <input type="radio" name="payment_method" value="credit_card" class="btn-check" id="credit_card">
                                            <label class="btn btn-outline-primary w-100" for="credit_card">
                                                <i class="bi bi-credit-card"></i><br>
                                                Cartão
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary btn-lg w-100">
                                    <i class="bi bi-lock"></i> Pagar R$ <?= number_format($plan->price, 2, ',', '.') ?>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Resumo -->
                <div class="col-md-5">
                    <div class="card">
                        <div class="card-header">Resumo do Pedido</div>
                        <div class="card-body">
                            <h5><?= htmlspecialchars($plan->name) ?></h5>
                            <p class="text-muted mb-3"><?= htmlspecialchars($plan->description ?? '') ?></p>
                            
                            <hr>
                            
                            <div class="d-flex justify-content-between mb-2">
                                <span>Plano</span>
                                <span>R$ <?= number_format($plan->price, 2, ',', '.') ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <span>Período</span>
                                <span>
                                    <?= $plan->period === 'monthly' ? 'Mensal' : ($plan->period === 'yearly' ? 'Anual' : 'Vitalício') ?>
                                </span>
                            </div>
                            
                            <hr>
                            
                            <div class="d-flex justify-content-between fs-5 fw-bold">
                                <span>Total</span>
                                <span class="text-success">R$ <?= number_format($plan->price, 2, ',', '.') ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-3 text-center">
                        <small class="text-muted">
                            <i class="bi bi-shield-check"></i> Pagamento seguro processado por Asaas
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
