<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="pus-pricing-wrapper">
    <div class="pus-pricing-grid">
        <?php foreach ($plans as $plan): ?>
            <div class="pus-pricing-card <?php echo $plan['id'] === 'professional' ? 'pus-featured' : ''; ?>">
                <?php if ($plan['id'] === 'professional'): ?>
                    <div class="pus-featured-badge"><?php _e('Mais Popular', 'premium-updates-server'); ?></div>
                <?php endif; ?>
                
                <div class="pus-pricing-header">
                    <h3 class="pus-plan-name"><?php echo esc_html($plan['name']); ?></h3>
                    <p class="pus-plan-description"><?php echo esc_html($plan['description']); ?></p>
                </div>
                
                <div class="pus-pricing-price">
                    <span class="pus-currency">R$</span>
                    <span class="pus-amount"><?php echo number_format($plan['price'], 0, ',', '.'); ?></span>
                    <span class="pus-period">/ano</span>
                </div>
                
                <ul class="pus-features-list">
                    <?php foreach ($plan['features'] as $feature): ?>
                        <li>
                            <svg class="pus-check-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <?php echo esc_html($feature); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
                
                <div class="pus-pricing-footer">
                    <?php 
                    $checkout_url = !empty($atts['checkout_url']) 
                        ? add_query_arg('plan', $plan['id'], $atts['checkout_url'])
                        : add_query_arg('plan', $plan['id']);
                    ?>
                    <a href="<?php echo esc_url($checkout_url); ?>" class="pus-buy-button">
                        <?php _e('Comprar Agora', 'premium-updates-server'); ?>
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <div class="pus-pricing-guarantee">
        <svg class="pus-shield-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
        </svg>
        <div>
            <strong><?php _e('Garantia de 7 dias', 'premium-updates-server'); ?></strong>
            <p><?php _e('Se não ficar satisfeito, devolvemos seu dinheiro.', 'premium-updates-server'); ?></p>
        </div>
    </div>
</div>
