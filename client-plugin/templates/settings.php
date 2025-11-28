<?php
if (!defined('ABSPATH')) {
    exit;
}

$license_key = get_option('puc_license_key', '');
$server_url = get_option('puc_server_url', '');
$license_status = get_option('puc_license_status', array());
$has_license = !empty($license_key) && !empty($server_url);
?>
<div class="wrap puc-admin">
    <h1><?php _e('Premium Updates Client', 'premium-updates-client'); ?></h1>

    <!-- Nav Tabs -->
    <nav class="nav-tab-wrapper">
        <a href="#tab-settings" class="nav-tab nav-tab-active" data-tab="settings"><?php _e('Configurações', 'premium-updates-client'); ?></a>
        <a href="#tab-subscription" class="nav-tab" data-tab="subscription"><?php _e('Assinatura', 'premium-updates-client'); ?></a>
        <a href="#tab-plugins" class="nav-tab" data-tab="plugins"><?php _e('Plugins', 'premium-updates-client'); ?></a>
        <?php if ($has_license): ?>
        <a href="#tab-account" class="nav-tab" data-tab="account"><?php _e('Minha Conta', 'premium-updates-client'); ?></a>
        <a href="#tab-payments" class="nav-tab" data-tab="payments"><?php _e('Pagamentos', 'premium-updates-client'); ?></a>
        <a href="#tab-updates-history" class="nav-tab" data-tab="updates-history"><?php _e('Histórico de Atualizações', 'premium-updates-client'); ?></a>
        <?php endif; ?>
    </nav>

    <!-- Tab: Settings -->
    <div id="tab-settings" class="puc-tab-content active">
        <form method="post" action="options.php">
            <?php settings_fields('puc_settings'); ?>

            <div class="puc-section">
                <h2><?php _e('Configurações do Servidor', 'premium-updates-client'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th><label for="puc_server_url"><?php _e('URL do Servidor', 'premium-updates-client'); ?></label></th>
                        <td>
                            <input type="url" name="puc_server_url" id="puc_server_url" class="regular-text" 
                                   value="<?php echo esc_attr($server_url); ?>"
                                   placeholder="https://seuservidor.com.br">
                            <p class="description"><?php _e('URL do site onde está instalado o Premium Updates Server', 'premium-updates-client'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="puc_license_key"><?php _e('Chave de Licença', 'premium-updates-client'); ?></label></th>
                        <td>
                            <input type="text" name="puc_license_key" id="puc_license_key" class="regular-text" 
                                   value="<?php echo esc_attr($license_key); ?>"
                                   placeholder="XXXX-XXXX-XXXX-XXXX">
                            <p class="description"><?php _e('Chave de licença fornecida pelo administrador ou obtida na assinatura', 'premium-updates-client'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th></th>
                        <td>
                            <button type="button" id="puc-test-connection" class="button">
                                <?php _e('Testar Conexão', 'premium-updates-client'); ?>
                            </button>
                            <span id="puc-test-result"></span>
                        </td>
                    </tr>
                </table>

                <?php submit_button(__('Salvar Configurações', 'premium-updates-client')); ?>
            </div>
        </form>
    </div>

    <!-- Tab: Subscription -->
    <div id="tab-subscription" class="puc-tab-content">
        
        <?php if (!empty($license_key) && !empty($license_status)): ?>
            <!-- Status da Assinatura Atual -->
            <div class="puc-section puc-subscription-status">
                <h2><?php _e('Sua Assinatura', 'premium-updates-client'); ?></h2>
                
                <?php 
                $is_active = ($license_status['status'] ?? '') === 'active';
                $expires_at = $license_status['expires_at'] ?? null;
                $days_left = $expires_at ? floor((strtotime($expires_at) - time()) / 86400) : 999;
                ?>
                
                <div class="puc-status-card <?php echo $is_active ? 'active' : 'expired'; ?>">
                    <div class="puc-status-header">
                        <span class="puc-status-badge <?php echo $is_active ? 'active' : 'expired'; ?>">
                            <?php echo $is_active ? __('Ativa', 'premium-updates-client') : __('Expirada', 'premium-updates-client'); ?>
                        </span>
                        <?php if (!empty($license_status['period_label'])): ?>
                            <span class="puc-period-badge"><?php echo esc_html($license_status['period_label']); ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="puc-status-details">
                        <p><strong><?php _e('Licença:', 'premium-updates-client'); ?></strong> <code><?php echo esc_html($license_key); ?></code></p>
                        
                        <?php if ($expires_at): ?>
                            <p><strong><?php _e('Expira em:', 'premium-updates-client'); ?></strong> <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($expires_at))); ?></p>
                            
                            <?php if ($days_left > 0 && $days_left <= 30): ?>
                                <p class="puc-warning"><?php printf(__('Atenção: Sua assinatura expira em %d dias!', 'premium-updates-client'), $days_left); ?></p>
                            <?php elseif ($days_left <= 0): ?>
                                <p class="puc-error"><?php _e('Sua assinatura expirou! Renove para continuar recebendo atualizações.', 'premium-updates-client'); ?></p>
                            <?php endif; ?>
                        <?php else: ?>
                            <p><strong><?php _e('Tipo:', 'premium-updates-client'); ?></strong> <?php _e('Licença Vitalícia', 'premium-updates-client'); ?></p>
                        <?php endif; ?>
                        
                        <p>
                            <button type="button" id="puc-refresh-license" class="button">
                                <?php _e('Atualizar Status', 'premium-updates-client'); ?>
                            </button>
                        </p>
                    </div>
                    
                    <?php if (!$is_active || $days_left <= 30): ?>
                        <div class="puc-renew-section">
                            <h3><?php _e('Renovar Assinatura', 'premium-updates-client'); ?></h3>
                            <form id="puc-renew-form">
                                <select name="period" id="puc-renew-period" class="regular-text">
                                    <option value="monthly"><?php _e('Mensal', 'premium-updates-client'); ?></option>
                                    <option value="quarterly"><?php _e('Trimestral', 'premium-updates-client'); ?></option>
                                    <option value="semiannual"><?php _e('Semestral', 'premium-updates-client'); ?></option>
                                    <option value="yearly"><?php _e('Anual', 'premium-updates-client'); ?></option>
                                </select>
                                
                                <select name="payment_method" id="puc-renew-method" class="regular-text">
                                    <option value="pix"><?php _e('PIX', 'premium-updates-client'); ?></option>
                                    <option value="boleto"><?php _e('Boleto Bancário', 'premium-updates-client'); ?></option>
                                </select>
                                
                                <button type="submit" class="button button-primary"><?php _e('Renovar Agora', 'premium-updates-client'); ?></button>
                            </form>
                            <div id="puc-renew-result"></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <!-- Nova Assinatura -->
            <div class="puc-section puc-new-subscription">
                <h2><?php _e('Contratar Assinatura', 'premium-updates-client'); ?></h2>
                
                <?php if (empty($server_url)): ?>
                    <div class="notice notice-warning inline">
                        <p><?php _e('Configure a URL do servidor na aba Configurações primeiro.', 'premium-updates-client'); ?></p>
                    </div>
                <?php else: ?>
                    <p><?php _e('Escolha um período de assinatura para receber atualizações de plugins premium:', 'premium-updates-client'); ?></p>
                    
                    <div id="puc-prices-loading" style="text-align: center; padding: 40px;">
                        <span class="spinner is-active" style="float: none;"></span>
                        <p><?php _e('Carregando preços...', 'premium-updates-client'); ?></p>
                    </div>
                    
                    <div id="puc-pricing-cards" class="puc-pricing-cards" style="display: none;"></div>
                    
                    <!-- Formulário de Assinatura -->
                    <div id="puc-subscription-form" style="display: none;">
                        <h3><?php _e('Seus Dados', 'premium-updates-client'); ?></h3>
                        <form id="puc-checkout-form">
                            <input type="hidden" name="period" id="puc-selected-period">
                            
                            <table class="form-table">
                                <tr>
                                    <th><label for="puc-customer-name"><?php _e('Nome Completo', 'premium-updates-client'); ?> *</label></th>
                                    <td><input type="text" name="name" id="puc-customer-name" class="regular-text" required></td>
                                </tr>
                                <tr>
                                    <th><label for="puc-customer-email"><?php _e('E-mail', 'premium-updates-client'); ?> *</label></th>
                                    <td><input type="email" name="email" id="puc-customer-email" class="regular-text" required value="<?php echo esc_attr(get_option('admin_email')); ?>"></td>
                                </tr>
                                <tr>
                                    <th><label for="puc-customer-cpf"><?php _e('CPF/CNPJ', 'premium-updates-client'); ?> *</label></th>
                                    <td><input type="text" name="cpf_cnpj" id="puc-customer-cpf" class="regular-text" required placeholder="000.000.000-00"></td>
                                </tr>
                                <tr>
                                    <th><label for="puc-payment-method"><?php _e('Forma de Pagamento', 'premium-updates-client'); ?></label></th>
                                    <td>
                                        <select name="payment_method" id="puc-payment-method" class="regular-text">
                                            <option value="pix"><?php _e('PIX (aprovação imediata)', 'premium-updates-client'); ?></option>
                                            <option value="boleto"><?php _e('Boleto Bancário (até 3 dias úteis)', 'premium-updates-client'); ?></option>
                                        </select>
                                    </td>
                                </tr>
                            </table>
                            
                            <p class="submit">
                                <button type="submit" class="button button-primary button-hero" id="puc-submit-subscription">
                                    <?php _e('Finalizar Assinatura', 'premium-updates-client'); ?>
                                </button>
                            </p>
                        </form>
                    </div>
                    
                    <!-- Resultado do Pagamento -->
                    <div id="puc-payment-result" style="display: none;"></div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Tab: Plugins -->
    <div id="tab-plugins" class="puc-tab-content">
        <form method="post" action="options.php">
            <?php settings_fields('puc_settings'); ?>

            <div class="puc-section">
                <h2><?php _e('Plugins Gerenciados', 'premium-updates-client'); ?></h2>
                
                <p class="description">
                    <?php _e('Selecione os plugins que devem receber atualizações do servidor premium:', 'premium-updates-client'); ?>
                </p>

                <div class="puc-plugins-actions">
                    <button type="button" id="puc-sync-plugins" class="button">
                        <?php _e('Sincronizar com Servidor', 'premium-updates-client'); ?>
                    </button>
                    <span id="puc-sync-result"></span>
                </div>

                <table class="wp-list-table widefat fixed striped puc-plugins-table">
                    <thead>
                        <tr>
                            <th class="check-column"><input type="checkbox" id="puc-select-all"></th>
                            <th><?php _e('Plugin', 'premium-updates-client'); ?></th>
                            <th><?php _e('Versão Instalada', 'premium-updates-client'); ?></th>
                            <th><?php _e('Status', 'premium-updates-client'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $server_plugins = array();
                        $result = Premium_Updates_Client::get_instance();
                        
                        foreach ($all_plugins as $plugin_file => $plugin_data): 
                            $is_managed = in_array($plugin_file, $managed_plugins);
                            $slug = explode('/', $plugin_file)[0];
                        ?>
                            <tr>
                                <td class="check-column">
                                    <input type="checkbox" name="puc_managed_plugins[]" 
                                           value="<?php echo esc_attr($plugin_file); ?>"
                                           <?php checked($is_managed); ?>>
                                </td>
                                <td>
                                    <strong><?php echo esc_html($plugin_data['Name']); ?></strong>
                                    <br>
                                    <small class="puc-plugin-slug"><?php echo esc_html($slug); ?></small>
                                </td>
                                <td><?php echo esc_html($plugin_data['Version']); ?></td>
                                <td>
                                    <?php if ($is_managed): ?>
                                        <span class="puc-status puc-status-managed"><?php _e('Gerenciado', 'premium-updates-client'); ?></span>
                                    <?php else: ?>
                                        <span class="puc-status puc-status-not-managed"><?php _e('Não gerenciado', 'premium-updates-client'); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="puc-section">
                <h2><?php _e('Informações', 'premium-updates-client'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th><?php _e('URL deste Site', 'premium-updates-client'); ?></th>
                        <td><code><?php echo esc_html(home_url('/')); ?></code></td>
                    </tr>
                    <tr>
                        <th><?php _e('Plugins Gerenciados', 'premium-updates-client'); ?></th>
                        <td><?php echo count($managed_plugins); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Próxima Verificação', 'premium-updates-client'); ?></th>
                        <td>
                            <?php 
                            $next_check = wp_next_scheduled('puc_check_updates');
                            if ($next_check) {
                                echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $next_check));
                            } else {
                                _e('Não agendado', 'premium-updates-client');
                            }
                            ?>
                        </td>
                    </tr>
                </table>
            </div>

            <?php submit_button(__('Salvar Configurações', 'premium-updates-client')); ?>
        </form>
    </div>

    <?php if ($has_license): ?>
    <!-- Tab: Minha Conta -->
    <div id="tab-account" class="puc-tab-content">
        <div class="puc-section">
            <h2><?php _e('Minha Conta', 'premium-updates-client'); ?></h2>
            
            <div id="puc-account-loading" class="puc-loading-container">
                <span class="spinner is-active" style="float: none;"></span>
                <p><?php _e('Carregando informações da conta...', 'premium-updates-client'); ?></p>
            </div>
            
            <div id="puc-account-content" style="display: none;">
                <div class="puc-account-grid">
                    <!-- Card de Status -->
                    <div class="puc-account-card puc-license-card">
                        <div class="puc-account-card-header">
                            <h3><?php _e('Status da Licença', 'premium-updates-client'); ?></h3>
                            <span id="puc-account-status-badge" class="puc-status-badge"></span>
                        </div>
                        <div class="puc-account-card-body">
                            <div class="puc-info-row">
                                <span class="puc-info-label"><?php _e('Chave de Licença:', 'premium-updates-client'); ?></span>
                                <code id="puc-account-license-key"></code>
                            </div>
                            <div class="puc-info-row">
                                <span class="puc-info-label"><?php _e('Período:', 'premium-updates-client'); ?></span>
                                <span id="puc-account-period"></span>
                            </div>
                            <div class="puc-info-row">
                                <span class="puc-info-label"><?php _e('Data de Expiração:', 'premium-updates-client'); ?></span>
                                <span id="puc-account-expires"></span>
                            </div>
                            <div class="puc-info-row" id="puc-days-remaining-row">
                                <span class="puc-info-label"><?php _e('Dias Restantes:', 'premium-updates-client'); ?></span>
                                <span id="puc-account-days"></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Card de Estatísticas -->
                    <div class="puc-account-card puc-stats-card">
                        <div class="puc-account-card-header">
                            <h3><?php _e('Estatísticas', 'premium-updates-client'); ?></h3>
                        </div>
                        <div class="puc-account-card-body">
                            <div class="puc-stats-grid">
                                <div class="puc-stat-item">
                                    <span class="puc-stat-value" id="puc-stat-total-payments">-</span>
                                    <span class="puc-stat-label"><?php _e('Pagamentos', 'premium-updates-client'); ?></span>
                                </div>
                                <div class="puc-stat-item">
                                    <span class="puc-stat-value" id="puc-stat-confirmed-payments">-</span>
                                    <span class="puc-stat-label"><?php _e('Confirmados', 'premium-updates-client'); ?></span>
                                </div>
                                <div class="puc-stat-item">
                                    <span class="puc-stat-value" id="puc-stat-downloads">-</span>
                                    <span class="puc-stat-label"><?php _e('Downloads', 'premium-updates-client'); ?></span>
                                </div>
                                <div class="puc-stat-item">
                                    <span class="puc-stat-value" id="puc-stat-updates">-</span>
                                    <span class="puc-stat-label"><?php _e('Atualizações', 'premium-updates-client'); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Plugins Disponíveis -->
                <div class="puc-account-section">
                    <h3><?php _e('Plugins Disponíveis', 'premium-updates-client'); ?></h3>
                    <div id="puc-account-plugins" class="puc-plugins-list"></div>
                </div>
                
                <!-- Atividade Recente -->
                <div class="puc-account-section">
                    <h3><?php _e('Atividade Recente', 'premium-updates-client'); ?></h3>
                    <div id="puc-account-activity"></div>
                </div>
            </div>
            
            <div id="puc-account-error" class="notice notice-error" style="display: none;"></div>
        </div>
    </div>

    <!-- Tab: Pagamentos -->
    <div id="tab-payments" class="puc-tab-content">
        <div class="puc-section">
            <h2><?php _e('Histórico de Pagamentos', 'premium-updates-client'); ?></h2>
            
            <div id="puc-payments-loading" class="puc-loading-container">
                <span class="spinner is-active" style="float: none;"></span>
                <p><?php _e('Carregando pagamentos...', 'premium-updates-client'); ?></p>
            </div>
            
            <div id="puc-payments-content" style="display: none;">
                <div id="puc-payments-summary" class="puc-payments-summary"></div>
                
                <table id="puc-payments-table" class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('ID', 'premium-updates-client'); ?></th>
                            <th><?php _e('Data', 'premium-updates-client'); ?></th>
                            <th><?php _e('Período', 'premium-updates-client'); ?></th>
                            <th><?php _e('Método', 'premium-updates-client'); ?></th>
                            <th><?php _e('Valor', 'premium-updates-client'); ?></th>
                            <th><?php _e('Status', 'premium-updates-client'); ?></th>
                            <th><?php _e('Ações', 'premium-updates-client'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="puc-payments-tbody">
                    </tbody>
                </table>
                
                <div id="puc-payments-empty" class="puc-empty-state" style="display: none;">
                    <span class="dashicons dashicons-format-aside"></span>
                    <p><?php _e('Nenhum pagamento encontrado.', 'premium-updates-client'); ?></p>
                </div>
            </div>
            
            <div id="puc-payments-error" class="notice notice-error" style="display: none;"></div>
        </div>
    </div>

    <!-- Tab: Histórico de Atualizações -->
    <div id="tab-updates-history" class="puc-tab-content">
        <div class="puc-section">
            <h2><?php _e('Histórico de Atualizações', 'premium-updates-client'); ?></h2>
            
            <div id="puc-updates-loading" class="puc-loading-container">
                <span class="spinner is-active" style="float: none;"></span>
                <p><?php _e('Carregando histórico...', 'premium-updates-client'); ?></p>
            </div>
            
            <div id="puc-updates-content" style="display: none;">
                <div id="puc-updates-stats" class="puc-updates-stats"></div>
                
                <div class="puc-updates-filter">
                    <select id="puc-updates-type-filter">
                        <option value=""><?php _e('Todos os tipos', 'premium-updates-client'); ?></option>
                        <option value="download"><?php _e('Downloads', 'premium-updates-client'); ?></option>
                        <option value="update"><?php _e('Atualizações', 'premium-updates-client'); ?></option>
                    </select>
                </div>
                
                <table id="puc-updates-table" class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Data', 'premium-updates-client'); ?></th>
                            <th><?php _e('Tipo', 'premium-updates-client'); ?></th>
                            <th><?php _e('Plugin', 'premium-updates-client'); ?></th>
                            <th><?php _e('Versão', 'premium-updates-client'); ?></th>
                            <th><?php _e('Detalhes', 'premium-updates-client'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="puc-updates-tbody">
                    </tbody>
                </table>
                
                <div id="puc-updates-empty" class="puc-empty-state" style="display: none;">
                    <span class="dashicons dashicons-update"></span>
                    <p><?php _e('Nenhum download ou atualização registrada.', 'premium-updates-client'); ?></p>
                </div>
            </div>
            
            <div id="puc-updates-error" class="notice notice-error" style="display: none;"></div>
        </div>
    </div>
    <?php endif; ?>
</div>
