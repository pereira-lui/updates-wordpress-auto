<?php
if (!defined('ABSPATH')) {
    exit;
}

$plans = PUS_Plans::get_plans();
$editing_plan = null;

if (isset($_GET['edit'])) {
    $editing_plan = PUS_Plans::get_plan(sanitize_text_field($_GET['edit']));
}
?>
<div class="wrap pus-admin">
    <h1 class="wp-heading-inline"><?php _e('Gerenciar Planos', 'premium-updates-server'); ?></h1>
    
    <?php if (!$editing_plan): ?>
        <a href="<?php echo admin_url('admin.php?page=premium-updates-plans&action=add'); ?>" class="page-title-action">
            <?php _e('Adicionar Plano', 'premium-updates-server'); ?>
        </a>
    <?php endif; ?>

    <hr class="wp-header-end">

    <?php if (isset($_GET['saved'])): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('Plano salvo com sucesso!', 'premium-updates-server'); ?></p>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['action']) && $_GET['action'] === 'add' || $editing_plan): ?>
        <!-- Formulário de Plano -->
        <div class="pus-settings-section">
            <h2><?php echo $editing_plan ? __('Editar Plano', 'premium-updates-server') : __('Novo Plano', 'premium-updates-server'); ?></h2>
            
            <form method="post" action="">
                <?php wp_nonce_field('pus_save_plan', 'pus_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th><label for="plan_id"><?php _e('ID do Plano', 'premium-updates-server'); ?> *</label></th>
                        <td>
                            <input type="text" name="plan_id" id="plan_id" class="regular-text" required
                                   value="<?php echo $editing_plan ? esc_attr($editing_plan['id']) : ''; ?>"
                                   <?php echo $editing_plan ? 'readonly' : ''; ?>
                                   pattern="[a-z0-9_-]+" placeholder="ex: starter">
                            <p class="description"><?php _e('Identificador único (letras minúsculas, números, hífen)', 'premium-updates-server'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="plan_name"><?php _e('Nome', 'premium-updates-server'); ?> *</label></th>
                        <td>
                            <input type="text" name="plan_name" id="plan_name" class="regular-text" required
                                   value="<?php echo $editing_plan ? esc_attr($editing_plan['name']) : ''; ?>">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="plan_description"><?php _e('Descrição', 'premium-updates-server'); ?></label></th>
                        <td>
                            <input type="text" name="plan_description" id="plan_description" class="large-text"
                                   value="<?php echo $editing_plan ? esc_attr($editing_plan['description']) : ''; ?>">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="plan_price"><?php _e('Preço (R$)', 'premium-updates-server'); ?> *</label></th>
                        <td>
                            <input type="number" name="plan_price" id="plan_price" class="small-text" required
                                   min="0" step="0.01"
                                   value="<?php echo $editing_plan ? esc_attr($editing_plan['price']) : ''; ?>">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="plan_type"><?php _e('Tipo de Cobrança', 'premium-updates-server'); ?></label></th>
                        <td>
                            <select name="plan_type" id="plan_type">
                                <option value="yearly" <?php selected($editing_plan && $editing_plan['type'] === 'yearly'); ?>>
                                    <?php _e('Anual (único)', 'premium-updates-server'); ?>
                                </option>
                                <option value="recurring" <?php selected($editing_plan && $editing_plan['type'] === 'recurring'); ?>>
                                    <?php _e('Recorrente (assinatura)', 'premium-updates-server'); ?>
                                </option>
                                <option value="lifetime" <?php selected($editing_plan && $editing_plan['type'] === 'lifetime'); ?>>
                                    <?php _e('Vitalício', 'premium-updates-server'); ?>
                                </option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="plan_cycle"><?php _e('Ciclo (se recorrente)', 'premium-updates-server'); ?></label></th>
                        <td>
                            <select name="plan_cycle" id="plan_cycle">
                                <option value="MONTHLY" <?php selected($editing_plan && isset($editing_plan['cycle']) && $editing_plan['cycle'] === 'MONTHLY'); ?>>
                                    <?php _e('Mensal', 'premium-updates-server'); ?>
                                </option>
                                <option value="YEARLY" <?php selected(!$editing_plan || !isset($editing_plan['cycle']) || $editing_plan['cycle'] === 'YEARLY'); ?>>
                                    <?php _e('Anual', 'premium-updates-server'); ?>
                                </option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="plan_max_sites"><?php _e('Máximo de Sites', 'premium-updates-server'); ?></label></th>
                        <td>
                            <input type="number" name="plan_max_sites" id="plan_max_sites" class="small-text"
                                   min="1" value="<?php echo $editing_plan && isset($editing_plan['max_sites']) ? esc_attr($editing_plan['max_sites']) : '1'; ?>">
                            <p class="description"><?php _e('Use 999 para ilimitado', 'premium-updates-server'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="plan_features"><?php _e('Recursos (um por linha)', 'premium-updates-server'); ?></label></th>
                        <td>
                            <textarea name="plan_features" id="plan_features" rows="5" class="large-text"><?php 
                                if ($editing_plan && isset($editing_plan['features'])) {
                                    echo esc_textarea(implode("\n", $editing_plan['features']));
                                }
                            ?></textarea>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <input type="submit" name="pus_save_plan" class="button button-primary" value="<?php _e('Salvar Plano', 'premium-updates-server'); ?>">
                    <a href="<?php echo admin_url('admin.php?page=premium-updates-plans'); ?>" class="button"><?php _e('Cancelar', 'premium-updates-server'); ?></a>
                </p>
            </form>
        </div>
    <?php else: ?>
        <!-- Lista de Planos -->
        <div class="pus-plans-grid">
            <?php foreach ($plans as $plan): ?>
                <div class="pus-plan-card">
                    <div class="pus-plan-header">
                        <h3><?php echo esc_html($plan['name']); ?></h3>
                        <span class="pus-plan-id"><?php echo esc_html($plan['id']); ?></span>
                    </div>
                    
                    <div class="pus-plan-price">
                        R$ <?php echo number_format($plan['price'], 2, ',', '.'); ?>
                        <span class="pus-plan-cycle">
                            <?php 
                            if ($plan['type'] === 'lifetime') {
                                _e('vitalício', 'premium-updates-server');
                            } elseif ($plan['type'] === 'recurring') {
                                echo $plan['cycle'] === 'MONTHLY' ? '/mês' : '/ano';
                            } else {
                                _e('/ano', 'premium-updates-server');
                            }
                            ?>
                        </span>
                    </div>
                    
                    <div class="pus-plan-details">
                        <p><strong><?php _e('Máx. sites:', 'premium-updates-server'); ?></strong> 
                            <?php echo isset($plan['max_sites']) && $plan['max_sites'] >= 999 ? __('Ilimitado', 'premium-updates-server') : $plan['max_sites']; ?>
                        </p>
                    </div>

                    <?php if (!empty($plan['features'])): ?>
                        <ul class="pus-plan-features">
                            <?php foreach ($plan['features'] as $feature): ?>
                                <li><?php echo esc_html($feature); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    
                    <div class="pus-plan-actions">
                        <a href="<?php echo admin_url('admin.php?page=premium-updates-plans&edit=' . $plan['id']); ?>" class="button">
                            <?php _e('Editar', 'premium-updates-server'); ?>
                        </a>
                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=premium-updates-plans&delete=' . $plan['id']), 'delete_plan_' . $plan['id']); ?>" 
                           class="button button-link-delete"
                           onclick="return confirm('<?php _e('Tem certeza?', 'premium-updates-server'); ?>')">
                            <?php _e('Remover', 'premium-updates-server'); ?>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
