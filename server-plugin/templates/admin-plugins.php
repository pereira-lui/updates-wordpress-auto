<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap pus-admin">
    <h1 class="wp-heading-inline"><?php _e('Gerenciar Plugins Premium', 'premium-updates-server'); ?></h1>
    
    <?php if ($action === 'list'): ?>
        <a href="<?php echo admin_url('admin.php?page=premium-updates&action=add'); ?>" class="page-title-action">
            <?php _e('Adicionar Novo', 'premium-updates-server'); ?>
        </a>
    <?php endif; ?>

    <hr class="wp-header-end">

    <?php if (isset($_GET['saved'])): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('Plugin salvo com sucesso!', 'premium-updates-server'); ?></p>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['deleted'])): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('Plugin removido com sucesso!', 'premium-updates-server'); ?></p>
        </div>
    <?php endif; ?>

    <?php if ($action === 'list'): ?>
        <?php $plugins = PUS_Database::get_plugins(false); ?>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Plugin', 'premium-updates-server'); ?></th>
                    <th><?php _e('Slug', 'premium-updates-server'); ?></th>
                    <th><?php _e('Versão', 'premium-updates-server'); ?></th>
                    <th><?php _e('Autor', 'premium-updates-server'); ?></th>
                    <th><?php _e('Status', 'premium-updates-server'); ?></th>
                    <th><?php _e('Última Atualização', 'premium-updates-server'); ?></th>
                    <th><?php _e('Ações', 'premium-updates-server'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($plugins)): ?>
                    <tr>
                        <td colspan="7"><?php _e('Nenhum plugin cadastrado.', 'premium-updates-server'); ?></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($plugins as $p): ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($p->plugin_name); ?></strong>
                            </td>
                            <td><code><?php echo esc_html($p->plugin_slug); ?></code></td>
                            <td><?php echo esc_html($p->plugin_version); ?></td>
                            <td><?php echo esc_html($p->plugin_author); ?></td>
                            <td>
                                <?php if ($p->is_active): ?>
                                    <span class="pus-status pus-status-active"><?php _e('Ativo', 'premium-updates-server'); ?></span>
                                <?php else: ?>
                                    <span class="pus-status pus-status-inactive"><?php _e('Inativo', 'premium-updates-server'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html($p->last_updated); ?></td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=premium-updates&action=edit&id=' . $p->id); ?>" class="button button-small">
                                    <?php _e('Editar', 'premium-updates-server'); ?>
                                </a>
                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=premium-updates&action=delete_plugin&id=' . $p->id), 'delete_plugin_' . $p->id); ?>" 
                                   class="button button-small button-link-delete" 
                                   onclick="return confirm('<?php _e('Tem certeza que deseja remover este plugin?', 'premium-updates-server'); ?>')">
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
            <?php wp_nonce_field('pus_save_plugin', 'pus_nonce'); ?>
            
            <table class="form-table">
                <tr>
                    <th><label for="plugin_name"><?php _e('Nome do Plugin', 'premium-updates-server'); ?> *</label></th>
                    <td>
                        <input type="text" name="plugin_name" id="plugin_name" class="regular-text" required
                               value="<?php echo $plugin ? esc_attr($plugin->plugin_name) : ''; ?>">
                    </td>
                </tr>
                <tr>
                    <th><label for="plugin_slug"><?php _e('Slug do Plugin', 'premium-updates-server'); ?> *</label></th>
                    <td>
                        <input type="text" name="plugin_slug" id="plugin_slug" class="regular-text" required
                               value="<?php echo $plugin ? esc_attr($plugin->plugin_slug) : ''; ?>"
                               <?php echo $plugin ? 'readonly' : ''; ?>>
                        <p class="description"><?php _e('Nome da pasta do plugin (ex: meu-plugin-premium)', 'premium-updates-server'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="plugin_version"><?php _e('Versão', 'premium-updates-server'); ?> *</label></th>
                    <td>
                        <input type="text" name="plugin_version" id="plugin_version" class="small-text" required
                               value="<?php echo $plugin ? esc_attr($plugin->plugin_version) : '1.0.0'; ?>">
                    </td>
                </tr>
                <tr>
                    <th><label for="plugin_author"><?php _e('Autor', 'premium-updates-server'); ?></label></th>
                    <td>
                        <input type="text" name="plugin_author" id="plugin_author" class="regular-text"
                               value="<?php echo $plugin ? esc_attr($plugin->plugin_author) : ''; ?>">
                    </td>
                </tr>
                <tr>
                    <th><label for="plugin_description"><?php _e('Descrição', 'premium-updates-server'); ?></label></th>
                    <td>
                        <textarea name="plugin_description" id="plugin_description" rows="3" class="large-text"><?php echo $plugin ? esc_textarea($plugin->plugin_description) : ''; ?></textarea>
                    </td>
                </tr>
                <tr>
                    <th><label for="plugin_url"><?php _e('URL do Plugin', 'premium-updates-server'); ?></label></th>
                    <td>
                        <input type="url" name="plugin_url" id="plugin_url" class="regular-text"
                               value="<?php echo $plugin ? esc_attr($plugin->plugin_url) : ''; ?>">
                    </td>
                </tr>
                <tr>
                    <th><label for="package_url"><?php _e('URL do Pacote ZIP', 'premium-updates-server'); ?> *</label></th>
                    <td>
                        <input type="url" name="package_url" id="package_url" class="large-text" required
                               value="<?php echo $plugin ? esc_attr($plugin->package_url) : ''; ?>">
                        <p class="description"><?php _e('URL direta para download do arquivo ZIP do plugin', 'premium-updates-server'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="tested_wp_version"><?php _e('Testado até (WP)', 'premium-updates-server'); ?></label></th>
                    <td>
                        <input type="text" name="tested_wp_version" id="tested_wp_version" class="small-text"
                               value="<?php echo $plugin ? esc_attr($plugin->tested_wp_version) : '6.4'; ?>">
                    </td>
                </tr>
                <tr>
                    <th><label for="requires_wp_version"><?php _e('Requer WP', 'premium-updates-server'); ?></label></th>
                    <td>
                        <input type="text" name="requires_wp_version" id="requires_wp_version" class="small-text"
                               value="<?php echo $plugin ? esc_attr($plugin->requires_wp_version) : '5.0'; ?>">
                    </td>
                </tr>
                <tr>
                    <th><label for="requires_php"><?php _e('Requer PHP', 'premium-updates-server'); ?></label></th>
                    <td>
                        <input type="text" name="requires_php" id="requires_php" class="small-text"
                               value="<?php echo $plugin ? esc_attr($plugin->requires_php) : '7.4'; ?>">
                    </td>
                </tr>
                <tr>
                    <th><label for="changelog"><?php _e('Changelog', 'premium-updates-server'); ?></label></th>
                    <td>
                        <textarea name="changelog" id="changelog" rows="6" class="large-text"><?php echo $plugin ? esc_textarea($plugin->changelog) : ''; ?></textarea>
                        <p class="description"><?php _e('HTML permitido. Use &lt;h4&gt; para versões e &lt;ul&gt; para lista de mudanças.', 'premium-updates-server'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="icon_url"><?php _e('URL do Ícone', 'premium-updates-server'); ?></label></th>
                    <td>
                        <input type="url" name="icon_url" id="icon_url" class="regular-text"
                               value="<?php echo $plugin ? esc_attr($plugin->icon_url) : ''; ?>">
                        <p class="description"><?php _e('Ícone 256x256 pixels', 'premium-updates-server'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="banner_url"><?php _e('URL do Banner', 'premium-updates-server'); ?></label></th>
                    <td>
                        <input type="url" name="banner_url" id="banner_url" class="regular-text"
                               value="<?php echo $plugin ? esc_attr($plugin->banner_url) : ''; ?>">
                        <p class="description"><?php _e('Banner 772x250 pixels', 'premium-updates-server'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="is_active"><?php _e('Status', 'premium-updates-server'); ?></label></th>
                    <td>
                        <label>
                            <input type="checkbox" name="is_active" id="is_active" value="1"
                                   <?php checked($plugin ? $plugin->is_active : true); ?>>
                            <?php _e('Ativo (disponível para atualizações)', 'premium-updates-server'); ?>
                        </label>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <input type="submit" name="pus_save_plugin" class="button button-primary" value="<?php _e('Salvar Plugin', 'premium-updates-server'); ?>">
                <a href="<?php echo admin_url('admin.php?page=premium-updates'); ?>" class="button"><?php _e('Cancelar', 'premium-updates-server'); ?></a>
            </p>
        </form>
    <?php endif; ?>
</div>
