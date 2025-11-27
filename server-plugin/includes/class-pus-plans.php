<?php
/**
 * Classe para gerenciar os planos de licença
 */

if (!defined('ABSPATH')) {
    exit;
}

class PUS_Plans {

    /**
     * Retorna os planos padrão
     */
    public static function get_default_plans() {
        return array(
            array(
                'id' => 'starter',
                'name' => 'Starter',
                'description' => 'Ideal para 1 site',
                'price' => 97.00,
                'type' => 'yearly',
                'cycle' => 'YEARLY',
                'max_sites' => 1,
                'features' => array(
                    '1 site autorizado',
                    'Atualizações automáticas',
                    'Suporte por e-mail',
                    '1 ano de atualizações'
                )
            ),
            array(
                'id' => 'professional',
                'name' => 'Professional',
                'description' => 'Para agências pequenas',
                'price' => 197.00,
                'type' => 'yearly',
                'cycle' => 'YEARLY',
                'max_sites' => 5,
                'features' => array(
                    'Até 5 sites',
                    'Atualizações automáticas',
                    'Suporte prioritário',
                    '1 ano de atualizações'
                )
            ),
            array(
                'id' => 'agency',
                'name' => 'Agency',
                'description' => 'Sites ilimitados',
                'price' => 497.00,
                'type' => 'yearly',
                'cycle' => 'YEARLY',
                'max_sites' => 999,
                'features' => array(
                    'Sites ilimitados',
                    'Atualizações automáticas',
                    'Suporte VIP',
                    '1 ano de atualizações',
                    'Licença white-label'
                )
            )
        );
    }

    /**
     * Retorna os planos configurados
     */
    public static function get_plans() {
        $plans = get_option('pus_plans', array());
        
        if (empty($plans)) {
            return self::get_default_plans();
        }

        return $plans;
    }

    /**
     * Retorna um plano pelo ID
     */
    public static function get_plan($plan_id) {
        $plans = self::get_plans();
        
        foreach ($plans as $plan) {
            if ($plan['id'] === $plan_id) {
                return $plan;
            }
        }

        return null;
    }

    /**
     * Salva os planos
     */
    public static function save_plans($plans) {
        return update_option('pus_plans', $plans);
    }

    /**
     * Adiciona ou atualiza um plano
     */
    public static function save_plan($plan_data) {
        $plans = self::get_plans();
        $found = false;

        foreach ($plans as $key => $plan) {
            if ($plan['id'] === $plan_data['id']) {
                $plans[$key] = $plan_data;
                $found = true;
                break;
            }
        }

        if (!$found) {
            $plans[] = $plan_data;
        }

        return self::save_plans($plans);
    }

    /**
     * Remove um plano
     */
    public static function delete_plan($plan_id) {
        $plans = self::get_plans();
        
        foreach ($plans as $key => $plan) {
            if ($plan['id'] === $plan_id) {
                unset($plans[$key]);
                break;
            }
        }

        return self::save_plans(array_values($plans));
    }
}
