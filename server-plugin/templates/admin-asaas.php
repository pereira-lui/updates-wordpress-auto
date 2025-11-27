<?php
if (!defined('ABSPATH')) {
    exit;
}

$asaas = new PUS_Asaas();
$is_configured = $asaas->is_configured();
$sandbox_mode = get_option('pus_asaas_sandbox', true);
?>
<div class="wrap pus-admin">
    <h1><?php _e('Configurações de Pagamento - Asaas', 'premium-updates-server'); ?></h1>

    <hr class="wp-header-end">

    <?php if (isset($_GET['saved'])): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('Configurações salvas com sucesso!', 'premium-updates-server'); ?></p>
        </div>
    <?php endif; ?>

    <form method="post" action="">
        <?php wp_nonce_field('pus_save_asaas', 'pus_nonce'); ?>

        <div class="pus-settings-section">
            <h2><?php _e('Credenciais da API', 'premium-updates-server'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th><label for="pus_asaas_sandbox"><?php _e('Ambiente', 'premium-updates-server'); ?></label></th>
                    <td>
                        <label>
                            <input type="checkbox" name="pus_asaas_sandbox" id="pus_asaas_sandbox" value="1" <?php checked($sandbox_mode); ?>>
                            <?php _e('Modo Sandbox (testes)', 'premium-updates-server'); ?>
                        </label>
                        <p class="description">
                            <?php _e('Marque para usar o ambiente de testes. Desmarque para produção.', 'premium-updates-server'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th><label for="pus_asaas_api_key"><?php _e('API Key', 'premium-updates-server'); ?></label></th>
                    <td>
                        <input type="password" name="pus_asaas_api_key" id="pus_asaas_api_key" class="large-text" 
                               value="<?php echo esc_attr(get_option('pus_asaas_api_key')); ?>"
                               placeholder="$aact_...">
                        <p class="description">
                            <?php printf(
                                __('Obtenha sua API Key em %s', 'premium-updates-server'),
                                '<a href="https://www.asaas.com/config/api" target="_blank">Asaas → Configurações → Integrações</a>'
                            ); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th><label for="pus_asaas_webhook_token"><?php _e('Token do Webhook', 'premium-updates-server'); ?></label></th>
                    <td>
                        <input type="text" name="pus_asaas_webhook_token" id="pus_asaas_webhook_token" class="regular-text" 
                               value="<?php echo esc_attr(get_option('pus_asaas_webhook_token')); ?>">
                        <p class="description">
                            <?php _e('Token opcional para validar os webhooks recebidos.', 'premium-updates-server'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Status da Conexão', 'premium-updates-server'); ?></th>
                    <td>
                        <?php if ($is_configured): ?>
                            <?php 
                            $test = $asaas->test_connection();
                            if ($test === true): ?>
                                <span class="pus-status pus-status-active">
                                    <?php _e('Conectado', 'premium-updates-server'); ?>
                                    <?php if ($sandbox_mode): ?>
                                        <em>(Sandbox)</em>
                                    <?php endif; ?>
                                </span>
                            <?php else: ?>
                                <span class="pus-status pus-status-inactive">
                                    <?php _e('Erro:', 'premium-updates-server'); ?> 
                                    <?php echo esc_html($test->get_error_message()); ?>
                                </span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="pus-status pus-status-inactive"><?php _e('Não configurado', 'premium-updates-server'); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        </div>

        <div class="pus-settings-section">
            <h2><?php _e('URL do Webhook', 'premium-updates-server'); ?></h2>
            
            <p><?php _e('Configure esta URL no painel do Asaas para receber notificações de pagamento:', 'premium-updates-server'); ?></p>
            
            <code class="pus-api-url"><?php echo esc_html(rest_url('premium-updates/v1/webhook/asaas')); ?></code>
            <button type="button" class="button button-small pus-copy-btn" data-copy="<?php echo esc_attr(rest_url('premium-updates/v1/webhook/asaas')); ?>">
                <?php _e('Copiar', 'premium-updates-server'); ?>
            </button>
            
            <p class="description" style="margin-top: 15px;">
                <?php _e('No Asaas, vá em Configurações → Integrações → Webhooks e adicione esta URL.', 'premium-updates-server'); ?>
                <br>
                <?php _e('Eventos recomendados: PAYMENT_CONFIRMED, PAYMENT_RECEIVED, PAYMENT_OVERDUE, PAYMENT_REFUNDED', 'premium-updates-server'); ?>
            </p>
        </div>

        <div class="pus-settings-section">
            <h2><?php _e('Shortcodes Disponíveis', 'premium-updates-server'); ?></h2>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Shortcode', 'premium-updates-server'); ?></th>
                        <th><?php _e('Descrição', 'premium-updates-server'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>[pus_pricing]</code></td>
                        <td><?php _e('Exibe a tabela de preços com todos os planos', 'premium-updates-server'); ?></td>
                    </tr>
                    <tr>
                        <td><code>[pus_pricing checkout_url="/checkout"]</code></td>
                        <td><?php _e('Tabela de preços com link para página de checkout personalizada', 'premium-updates-server'); ?></td>
                    </tr>
                    <tr>
                        <td><code>[pus_checkout]</code></td>
                        <td><?php _e('Formulário de checkout para compra', 'premium-updates-server'); ?></td>
                    </tr>
                    <tr>
                        <td><code>[pus_checkout plan="starter"]</code></td>
                        <td><?php _e('Checkout com plano pré-selecionado', 'premium-updates-server'); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p class="submit">
            <input type="submit" name="pus_save_asaas" class="button button-primary" value="<?php _e('Salvar Configurações', 'premium-updates-server'); ?>">
        </p>
    </form>
</div>
