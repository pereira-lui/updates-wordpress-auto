<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="pus-checkout-wrapper">
    <div class="pus-checkout-container">
        <div class="pus-checkout-form-section">
            <h2><?php _e('Finalizar Compra', 'premium-updates-server'); ?></h2>
            
            <form id="pus-checkout-form" class="pus-checkout-form">
                <div class="pus-form-group">
                    <label for="pus-plan"><?php _e('Plano', 'premium-updates-server'); ?> *</label>
                    <select name="plan_id" id="pus-plan" required>
                        <option value=""><?php _e('Selecione um plano', 'premium-updates-server'); ?></option>
                        <?php foreach ($plans as $p): ?>
                            <option value="<?php echo esc_attr($p['id']); ?>" 
                                    data-price="<?php echo esc_attr($p['price']); ?>"
                                    <?php selected($plan && $plan['id'] === $p['id']); ?>>
                                <?php echo esc_html($p['name']); ?> - R$ <?php echo number_format($p['price'], 2, ',', '.'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="pus-form-group">
                    <label for="pus-name"><?php _e('Nome Completo', 'premium-updates-server'); ?> *</label>
                    <input type="text" name="client_name" id="pus-name" required 
                           placeholder="<?php _e('Seu nome completo', 'premium-updates-server'); ?>">
                </div>

                <div class="pus-form-group">
                    <label for="pus-email"><?php _e('E-mail', 'premium-updates-server'); ?> *</label>
                    <input type="email" name="client_email" id="pus-email" required 
                           placeholder="<?php _e('seu@email.com', 'premium-updates-server'); ?>">
                </div>

                <div class="pus-form-group">
                    <label for="pus-cpf"><?php _e('CPF ou CNPJ', 'premium-updates-server'); ?> *</label>
                    <input type="text" name="cpf_cnpj" id="pus-cpf" required 
                           placeholder="<?php _e('000.000.000-00', 'premium-updates-server'); ?>">
                </div>

                <div class="pus-form-group">
                    <label for="pus-site"><?php _e('URL do Site', 'premium-updates-server'); ?> *</label>
                    <input type="url" name="site_url" id="pus-site" required 
                           placeholder="https://seusite.com.br">
                    <p class="pus-field-hint"><?php _e('URL do site onde a licença será ativada', 'premium-updates-server'); ?></p>
                </div>

                <div class="pus-form-summary">
                    <div class="pus-summary-row">
                        <span><?php _e('Total', 'premium-updates-server'); ?>:</span>
                        <strong id="pus-total">R$ <?php echo $plan ? number_format($plan['price'], 2, ',', '.') : '0,00'; ?></strong>
                    </div>
                </div>

                <div class="pus-form-actions">
                    <button type="submit" class="pus-submit-button" id="pus-submit-btn">
                        <span class="pus-btn-text"><?php _e('Ir para Pagamento', 'premium-updates-server'); ?></span>
                        <span class="pus-btn-loading" style="display: none;">
                            <svg class="pus-spinner" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" opacity="0.25"></circle>
                                <path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <?php _e('Processando...', 'premium-updates-server'); ?>
                        </span>
                    </button>
                </div>

                <div class="pus-form-message" id="pus-form-message"></div>
            </form>

            <div class="pus-payment-methods">
                <p><?php _e('Formas de pagamento aceitas:', 'premium-updates-server'); ?></p>
                <div class="pus-payment-icons">
                    <span class="pus-payment-icon" title="PIX">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="M9.5 4.5L12 2l2.5 2.5L12 7 9.5 4.5zm5 15L12 22l-2.5-2.5L12 17l2.5 2.5zm7-7L19 15l-2.5-2.5L19 10l2.5 2.5zm-15 0L4 15l-2.5-2.5L4 10l2.5 2.5zM12 9l3 3-3 3-3-3 3-3z"/>
                        </svg>
                        PIX
                    </span>
                    <span class="pus-payment-icon" title="Boleto">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="M2 4h2v16H2V4zm4 0h1v16H6V4zm3 0h2v16H9V4zm4 0h1v16h-1V4zm3 0h2v16h-2V4zm4 0h2v16h-2V4z"/>
                        </svg>
                        Boleto
                    </span>
                    <span class="pus-payment-icon" title="Cartão de Crédito">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="M20 4H4c-1.11 0-1.99.89-1.99 2L2 18c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/>
                        </svg>
                        Cartão
                    </span>
                </div>
            </div>
        </div>

        <div class="pus-checkout-info-section">
            <div class="pus-info-card">
                <h3><?php _e('O que você vai receber:', 'premium-updates-server'); ?></h3>
                <ul>
                    <li>
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <?php _e('Chave de licença exclusiva', 'premium-updates-server'); ?>
                    </li>
                    <li>
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <?php _e('Atualizações automáticas', 'premium-updates-server'); ?>
                    </li>
                    <li>
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <?php _e('Suporte técnico', 'premium-updates-server'); ?>
                    </li>
                    <li>
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <?php _e('Ativação instantânea após pagamento', 'premium-updates-server'); ?>
                    </li>
                </ul>
            </div>

            <div class="pus-info-card pus-security-card">
                <div class="pus-security-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                </div>
                <div>
                    <strong><?php _e('Pagamento Seguro', 'premium-updates-server'); ?></strong>
                    <p><?php _e('Seus dados são protegidos e processados pelo Asaas.', 'premium-updates-server'); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>
