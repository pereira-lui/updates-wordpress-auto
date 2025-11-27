<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap puc-admin">
    <h1><?php _e('Premium Updates Client', 'premium-updates-client'); ?></h1>

    <form method="post" action="options.php">
        <?php settings_fields('puc_settings'); ?>

        <div class="puc-section">
            <h2><?php _e('Configurações do Servidor', 'premium-updates-client'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th><label for="puc_server_url"><?php _e('URL do Servidor', 'premium-updates-client'); ?></label></th>
                    <td>
                        <input type="url" name="puc_server_url" id="puc_server_url" class="regular-text" 
                               value="<?php echo esc_attr(get_option('puc_server_url')); ?>"
                               placeholder="https://seuservidor.com.br">
                        <p class="description"><?php _e('URL do site onde está instalado o Premium Updates Server', 'premium-updates-client'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="puc_license_key"><?php _e('Chave de Licença', 'premium-updates-client'); ?></label></th>
                    <td>
                        <input type="text" name="puc_license_key" id="puc_license_key" class="regular-text" 
                               value="<?php echo esc_attr(get_option('puc_license_key')); ?>"
                               placeholder="XXXX-XXXX-XXXX-XXXX">
                        <p class="description"><?php _e('Chave de licença fornecida pelo administrador', 'premium-updates-client'); ?></p>
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
        </div>

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
