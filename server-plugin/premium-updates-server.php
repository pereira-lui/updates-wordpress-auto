<?php
/**
 * Plugin Name: Premium Updates Server
 * Plugin URI: https://github.com/pereira-lui/updates-wordpress-auto
 * Description: Servidor central para distribuição de atualizações de plugins premium para sites de clientes.
 * Version: 1.0.0
 * Author: Lui Pereira
 * Author URI: https://github.com/pereira-lui
 * License: GPL v2 or later
 * Text Domain: premium-updates-server
 */

if (!defined('ABSPATH')) {
    exit;
}

define('PUS_VERSION', '1.0.0');
define('PUS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PUS_PLUGIN_URL', plugin_dir_url(__FILE__));

// Carrega as classes
require_once PUS_PLUGIN_DIR . 'includes/class-pus-database.php';
require_once PUS_PLUGIN_DIR . 'includes/class-pus-api.php';
require_once PUS_PLUGIN_DIR . 'includes/class-pus-admin.php';
require_once PUS_PLUGIN_DIR . 'includes/class-pus-plugin-manager.php';

/**
 * Classe principal do plugin servidor
 */
class Premium_Updates_Server {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->init_hooks();
    }

    private function init_hooks() {
        register_activation_hook(__FILE__, array('PUS_Database', 'create_tables'));
        
        add_action('init', array($this, 'init'));
        add_action('rest_api_init', array('PUS_API', 'register_routes'));
        
        if (is_admin()) {
            new PUS_Admin();
        }
    }

    public function init() {
        load_plugin_textdomain('premium-updates-server', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
}

// Inicializa o plugin
Premium_Updates_Server::get_instance();
