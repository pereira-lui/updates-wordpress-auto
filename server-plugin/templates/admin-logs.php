<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap pus-admin">
    <h1><?php _e('Logs de Atualizações', 'premium-updates-server'); ?></h1>

    <hr class="wp-header-end">

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php _e('Data', 'premium-updates-server'); ?></th>
                <th><?php _e('Cliente', 'premium-updates-server'); ?></th>
                <th><?php _e('Site', 'premium-updates-server'); ?></th>
                <th><?php _e('Plugin', 'premium-updates-server'); ?></th>
                <th><?php _e('Versão Anterior', 'premium-updates-server'); ?></th>
                <th><?php _e('Nova Versão', 'premium-updates-server'); ?></th>
                <th><?php _e('Status', 'premium-updates-server'); ?></th>
                <th><?php _e('IP', 'premium-updates-server'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($logs)): ?>
                <tr>
                    <td colspan="8"><?php _e('Nenhum log de atualização encontrado.', 'premium-updates-server'); ?></td>
                </tr>
            <?php else: ?>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td>
                            <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($log->created_at))); ?>
                        </td>
                        <td><?php echo esc_html($log->client_name ?: '-'); ?></td>
                        <td>
                            <a href="<?php echo esc_url($log->site_url); ?>" target="_blank">
                                <?php echo esc_html(parse_url($log->site_url, PHP_URL_HOST)); ?>
                            </a>
                        </td>
                        <td><?php echo esc_html($log->plugin_name ?: '-'); ?></td>
                        <td><?php echo esc_html($log->old_version ?: '-'); ?></td>
                        <td><strong><?php echo esc_html($log->new_version); ?></strong></td>
                        <td>
                            <?php if ($log->status === 'success'): ?>
                                <span class="pus-status pus-status-active"><?php _e('Sucesso', 'premium-updates-server'); ?></span>
                            <?php else: ?>
                                <span class="pus-status pus-status-inactive"><?php echo esc_html($log->status); ?></span>
                            <?php endif; ?>
                        </td>
                        <td><code><?php echo esc_html($log->ip_address); ?></code></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
