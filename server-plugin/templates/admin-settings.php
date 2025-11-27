<?php
if (!defined('ABSPATH')) {
    exit;
}

$api_key = get_option('pus_api_secret_key');
$api_url = rest_url('premium-updates/v1/');
?>
<div class="wrap pus-admin">
    <h1><?php _e('Configurações', 'premium-updates-server'); ?></h1>

    <hr class="wp-header-end">

    <?php if (isset($_GET['saved'])): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('Configurações salvas com sucesso!', 'premium-updates-server'); ?></p>
        </div>
    <?php endif; ?>

    <div class="pus-settings-section">
        <h2><?php _e('Informações da API', 'premium-updates-server'); ?></h2>
        
        <table class="form-table">
            <tr>
                <th><?php _e('URL da API', 'premium-updates-server'); ?></th>
                <td>
                    <code class="pus-api-url"><?php echo esc_html($api_url); ?></code>
                    <button type="button" class="button button-small pus-copy-btn" data-copy="<?php echo esc_attr($api_url); ?>">
                        <?php _e('Copiar', 'premium-updates-server'); ?>
                    </button>
                </td>
            </tr>
        </table>
    </div>

    <div class="pus-settings-section">
        <h2><?php _e('Endpoints Disponíveis', 'premium-updates-server'); ?></h2>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Endpoint', 'premium-updates-server'); ?></th>
                    <th><?php _e('Método', 'premium-updates-server'); ?></th>
                    <th><?php _e('Descrição', 'premium-updates-server'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>/check-updates</code></td>
                    <td>POST</td>
                    <td><?php _e('Verifica atualizações disponíveis para os plugins instalados', 'premium-updates-server'); ?></td>
                </tr>
                <tr>
                    <td><code>/plugin-info/{slug}</code></td>
                    <td>POST</td>
                    <td><?php _e('Retorna informações detalhadas de um plugin', 'premium-updates-server'); ?></td>
                </tr>
                <tr>
                    <td><code>/download/{slug}</code></td>
                    <td>POST</td>
                    <td><?php _e('Obtém a URL de download do plugin', 'premium-updates-server'); ?></td>
                </tr>
                <tr>
                    <td><code>/validate-license</code></td>
                    <td>POST</td>
                    <td><?php _e('Valida uma chave de licença', 'premium-updates-server'); ?></td>
                </tr>
                <tr>
                    <td><code>/plugins</code></td>
                    <td>POST</td>
                    <td><?php _e('Lista todos os plugins disponíveis', 'premium-updates-server'); ?></td>
                </tr>
            </tbody>
        </table>
        
        <p class="description">
            <?php _e('Todos os endpoints requerem os parâmetros <code>license_key</code> e <code>site_url</code> no body da requisição.', 'premium-updates-server'); ?>
        </p>
    </div>

    <div class="pus-settings-section">
        <h2><?php _e('Instruções de Configuração do Cliente', 'premium-updates-server'); ?></h2>
        
        <div class="pus-instructions">
            <p><?php _e('Para configurar o plugin cliente nos sites dos seus clientes:', 'premium-updates-server'); ?></p>
            
            <ol>
                <li><?php _e('Faça o upload do plugin <strong>Premium Updates Client</strong> no site do cliente', 'premium-updates-server'); ?></li>
                <li><?php _e('Ative o plugin', 'premium-updates-server'); ?></li>
                <li><?php _e('Vá em <strong>Configurações → Premium Updates</strong>', 'premium-updates-server'); ?></li>
                <li><?php printf(__('Configure a URL do servidor: <code>%s</code>', 'premium-updates-server'), esc_html(home_url('/'))); ?></li>
                <li><?php _e('Insira a chave de licença gerada para aquele cliente', 'premium-updates-server'); ?></li>
                <li><?php _e('Salve as configurações e teste a conexão', 'premium-updates-server'); ?></li>
            </ol>
        </div>
    </div>

    <div class="pus-settings-section">
        <h2><?php _e('Estatísticas', 'premium-updates-server'); ?></h2>
        
        <?php
        $plugins = PUS_Database::get_plugins(false);
        $licenses = PUS_Database::get_licenses();
        $active_licenses = array_filter($licenses, function($l) {
            return $l->is_active && (!$l->expires_at || strtotime($l->expires_at) > time());
        });
        ?>
        
        <div class="pus-stats">
            <div class="pus-stat-box">
                <span class="pus-stat-number"><?php echo count($plugins); ?></span>
                <span class="pus-stat-label"><?php _e('Plugins Cadastrados', 'premium-updates-server'); ?></span>
            </div>
            <div class="pus-stat-box">
                <span class="pus-stat-number"><?php echo count($licenses); ?></span>
                <span class="pus-stat-label"><?php _e('Licenças Total', 'premium-updates-server'); ?></span>
            </div>
            <div class="pus-stat-box">
                <span class="pus-stat-number"><?php echo count($active_licenses); ?></span>
                <span class="pus-stat-label"><?php _e('Licenças Ativas', 'premium-updates-server'); ?></span>
            </div>
        </div>
    </div>
</div>
