<?php
/**
 * Premium Updates Server - Sistema de Gerenciamento
 * 
 * Ponto de entrada principal do sistema
 */

// Previne acesso direto aos arquivos
define('PUS_SYSTEM', true);

// Define constantes de caminho
define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');
define('CONFIG_PATH', ROOT_PATH . '/config');
define('VIEWS_PATH', ROOT_PATH . '/resources/views');
define('STORAGE_PATH', ROOT_PATH . '/storage');

// Carrega configurações
$config = require CONFIG_PATH . '/app.php';

// Define constantes da aplicação
define('APP_URL', $config['url'] ?? 'http://localhost');
define('APP_DEBUG', $config['debug'] ?? false);

// Carrega autoloader
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

// Carrega helpers
require_once APP_PATH . '/helpers.php';

// Inicia sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configura tratamento de erros
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Inicializa conexão com banco de dados
try {
    App\Core\Database::getInstance();
} catch (Exception $e) {
    if (APP_DEBUG) {
        die('Erro de conexão: ' . $e->getMessage());
    }
    die('Erro de conexão com o banco de dados');
}

// Inicializa o roteador
$router = new App\Core\Router();

// Carrega as rotas
require_once ROOT_PATH . '/routes/web.php';
require_once ROOT_PATH . '/routes/api.php';

// Executa a rota
$router->dispatch();
