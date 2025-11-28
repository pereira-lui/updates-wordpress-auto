<?php
/**
 * Bootstrap do sistema
 */

if (!defined('PUS_SYSTEM')) {
    exit('Acesso negado');
}

// Define caminhos
define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');
define('CONFIG_PATH', ROOT_PATH . '/config');
define('STORAGE_PATH', ROOT_PATH . '/storage');
define('PUBLIC_PATH', ROOT_PATH . '/public');

// Carrega configurações
$config = require CONFIG_PATH . '/app.php';
define('APP_NAME', $config['name']);
define('APP_URL', $config['url']);
define('APP_DEBUG', $config['debug']);

// Configurações de erro
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Timezone
date_default_timezone_set($config['timezone']);

// Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = APP_PATH . '/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Inicia sessão
session_start();

// Helpers globais
require_once APP_PATH . '/helpers.php';

// Conexão com banco de dados
App\Core\Database::init();
