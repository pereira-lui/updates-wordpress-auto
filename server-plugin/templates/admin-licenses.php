<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap pus-admin">
    <h1 class="wp-heading-inline"><?php _e('Gerenciar Licenças', 'premium-updates-server'); ?></h1>
    
    <?php if ($action === 'list'): ?>
        <a href="<?php echo admin_url('admin.php?page=premium-updates-licenses&action=add'); ?>" class="page-title-action">
            <?php _e('Adicionar Nova', 'premium-updates-server'); ?>
        </a>
    <?php endif; ?>

    <hr class="wp-header-end">

    <?php if (isset($_GET['saved'])): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('Licença salva com sucesso!', 'premium-updates-server'); ?></p>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['deleted'])): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('Licença removida com sucesso!', 'premium-updates-server'); ?></p>
        </div>
    <?php endif; ?>

    <?php if ($action === 'list'): ?>
        <?php $licenses = PUS_Database::get_licenses(); ?>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Cliente', 'premium-updates-server'); ?></th>
                    <th><?php _e('Chave de Licença', 'premium-updates-server'); ?></th>
                    <th><?php _e('Site', 'premium-updates-server'); ?></th>
                    <th><?php _e('Status', 'premium-updates-server'); ?></th>
                    <th><?php _e('Expira em', 'premium-updates-server'); ?></th>
                    <th><?php _e('Último Check', 'premium-updates-server'); ?></th>
                    <th><?php _e('Ações', 'premium-updates-server'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($licenses)): ?>
                    <tr>
                        <td colspan="7"><?php _e('Nenhuma licença cadastrada.', 'premium-updates-server'); ?></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($licenses as $l): ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($l->client_name); ?></strong>
                                <?php if ($l->client_email): ?>
                                    <br><small><?php echo esc_html($l->client_email); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><code class="pus-license-key"><?php echo esc_html($l->license_key); ?></code></td>
                            <td>
                                <a href="<?php echo esc_url($l->site_url); ?>" target="_blank">
                                    <?php echo esc_html(parse_url($l->site_url, PHP_URL_HOST)); ?>
                                </a>
                            </td>
                            <td>
                                <?php 
                                $is_expired = $l->expires_at && strtotime($l->expires_at) < time();
                                if (!$l->is_active): ?>
                                    <span class="pus-status pus-status-inactive"><?php _e('Inativa', 'premium-updates-server'); ?></span>
                                <?php elseif ($is_expired): ?>
                                    <span class="pus-status pus-status-expired"><?php _e('Expirada', 'premium-updates-server'); ?></span>
                                <?php else: ?>
                                    <span class="pus-status pus-status-active"><?php _e('Ativa', 'premium-updates-server'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                if ($l->expires_at) {
                                    echo esc_html(date_i18n(get_option('date_format'), strtotime($l->expires_at)));
                                } else {
                                    _e('Nunca', 'premium-updates-server');
                                }
                                ?>
                            </td>
                            <td>
                                <?php 
                                if ($l->last_check) {
                                    echo esc_html(human_time_diff(strtotime($l->last_check)) . ' atrás');
                                } else {
                                    _e('Nunca', 'premium-updates-server');
                                }
                                ?>
                            </td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=premium-updates-licenses&action=edit&id=' . $l->id); ?>" class="button button-small">
                                    <?php _e('Editar', 'premium-updates-server'); ?>
                                </a>
                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=premium-updates-licenses&action=delete_license&id=' . $l->id), 'delete_license_' . $l->id); ?>" 
                                   class="button button-small button-link-delete" 
                                   onclick="return confirm('<?php _e('Tem certeza que deseja remover esta licença?', 'premium-updates-server'); ?>')">
                                    <?php _e('Remover', 'premium-updates-server'); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

    <?php else: ?>
        <!-- Formulário de adição/edição -->
        <form method="post" class="pus-form">
            <?php wp_nonce_field('pus_save_license', 'pus_nonce'); ?>
            <?php if ($license): ?>
                <input type="hidden" name="license_id" value="<?php echo esc_attr($license->id); ?>">
            <?php endif; ?>
            
            <table class="form-table">
                <tr>
                    <th><label for="client_name"><?php _e('Nome do Cliente', 'premium-updates-server'); ?> *</label></th>
                    <td>
                        <input type="text" name="client_name" id="client_name" class="regular-text" required
                               value="<?php echo $license ? esc_attr($license->client_name) : ''; ?>">
                    </td>
                </tr>
                <tr>
                    <th><label for="client_email"><?php _e('E-mail do Cliente', 'premium-updates-server'); ?></label></th>
                    <td>
                        <input type="email" name="client_email" id="client_email" class="regular-text"
                               value="<?php echo $license ? esc_attr($license->client_email) : ''; ?>">
                    </td>
                </tr>
                <tr>
                    <th><label for="site_url"><?php _e('URL do Site', 'premium-updates-server'); ?> *</label></th>
                    <td>
                        <input type="url" name="site_url" id="site_url" class="large-text" required
                               value="<?php echo $license ? esc_attr($license->site_url) : ''; ?>"
                               placeholder="https://exemplo.com.br">
                        <p class="description"><?php _e('URL completa do site do cliente', 'premium-updates-server'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="license_key"><?php _e('Chave de Licença', 'premium-updates-server'); ?></label></th>
                    <td>
                        <?php if ($license): ?>
                            <code class="pus-license-key-large"><?php echo esc_html($license->license_key); ?></code>
                            <input type="hidden" name="license_key" value="<?php echo esc_attr($license->license_key); ?>">
                        <?php else: ?>
                            <input type="text" name="license_key" id="license_key" class="regular-text"
                                   placeholder="<?php _e('Deixe em branco para gerar automaticamente', 'premium-updates-server'); ?>">
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><label for="max_activations"><?php _e('Máximo de Ativações', 'premium-updates-server'); ?></label></th>
                    <td>
                        <input type="number" name="max_activations" id="max_activations" class="small-text" min="1"
                               value="<?php echo $license ? esc_attr($license->max_activations) : '1'; ?>">
                    </td>
                </tr>
                <tr>
                    <th><label for="expires_at"><?php _e('Data de Expiração', 'premium-updates-server'); ?></label></th>
                    <td>
                        <input type="date" name="expires_at" id="expires_at"
                               value="<?php echo $license && $license->expires_at ? esc_attr(date('Y-m-d', strtotime($license->expires_at))) : ''; ?>">
                        <p class="description"><?php _e('Deixe em branco para licença vitalícia', 'premium-updates-server'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="notes"><?php _e('Notas', 'premium-updates-server'); ?></label></th>
                    <td>
                        <textarea name="notes" id="notes" rows="3" class="large-text"><?php echo $license ? esc_textarea($license->notes) : ''; ?></textarea>
                    </td>
                </tr>
                <tr>
                    <th><label for="is_active"><?php _e('Status', 'premium-updates-server'); ?></label></th>
                    <td>
                        <label>
                            <input type="checkbox" name="is_active" id="is_active" value="1"
                                   <?php checked($license ? $license->is_active : true); ?>>
                            <?php _e('Licença Ativa', 'premium-updates-server'); ?>
                        </label>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <input type="submit" name="pus_save_license" class="button button-primary" value="<?php _e('Salvar Licença', 'premium-updates-server'); ?>">
                <a href="<?php echo admin_url('admin.php?page=premium-updates-licenses'); ?>" class="button"><?php _e('Cancelar', 'premium-updates-server'); ?></a>
            </p>
        </form>
    <?php endif; ?>
</div>
