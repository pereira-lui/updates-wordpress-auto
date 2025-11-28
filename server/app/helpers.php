<?php
/**
 * Funções auxiliares globais
 */

if (!defined('PUS_SYSTEM')) {
    exit('Acesso negado');
}

/**
 * Retorna configuração
 */
function config($key, $default = null) {
    static $config = null;
    
    if ($config === null) {
        $config = require CONFIG_PATH . '/app.php';
    }
    
    $keys = explode('.', $key);
    $value = $config;
    
    foreach ($keys as $k) {
        if (!isset($value[$k])) {
            return $default;
        }
        $value = $value[$k];
    }
    
    return $value;
}

/**
 * Gera URL completa
 */
function url($path = '') {
    return rtrim(APP_URL, '/') . '/' . ltrim($path, '/');
}

/**
 * Gera URL para assets
 */
function asset($path) {
    return url('assets/' . ltrim($path, '/'));
}

/**
 * Redireciona para URL
 */
function redirect($url) {
    header('Location: ' . $url);
    exit;
}

/**
 * Retorna dados da sessão
 */
function session($key = null, $default = null) {
    if ($key === null) {
        return $_SESSION;
    }
    return $_SESSION[$key] ?? $default;
}

/**
 * Define dados na sessão
 */
function session_set($key, $value) {
    $_SESSION[$key] = $value;
}

/**
 * Remove dados da sessão
 */
function session_forget($key) {
    unset($_SESSION[$key]);
}

/**
 * Retorna mensagem flash
 */
function flash($type = null) {
    $flash = session('_flash', null);
    session_forget('_flash');
    
    if ($type !== null && is_array($flash)) {
        return $flash[$type] ?? null;
    }
    
    return $flash;
}

/**
 * Define mensagem flash
 */
function flash_set($type, $message) {
    session_set('_flash', [
        'type' => $type,
        'message' => $message
    ]);
}

/**
 * Verifica se usuário está autenticado
 */
function auth() {
    $user = session('user');
    if ($user && is_array($user)) {
        return (object) $user;
    }
    return $user;
}

/**
 * Verifica se é requisição POST
 */
function is_post() {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/**
 * Retorna input do request
 */
function input($key = null, $default = null) {
    $data = array_merge($_GET, $_POST);
    
    if ($key === null) {
        return $data;
    }
    
    return $data[$key] ?? $default;
}

/**
 * Sanitiza string
 */
function clean($value) {
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

/**
 * Gera token CSRF
 */
function csrf_token() {
    if (!session('_csrf_token')) {
        session_set('_csrf_token', bin2hex(random_bytes(32)));
    }
    return session('_csrf_token');
}

/**
 * Campo CSRF para formulários
 */
function csrf_field() {
    return '<input type="hidden" name="_token" value="' . csrf_token() . '">';
}

/**
 * Valida token CSRF
 */
function csrf_verify() {
    $token = input('_token');
    return $token && hash_equals(session('_csrf_token', ''), $token);
}

/**
 * Formata data
 */
function format_date($date, $format = 'd/m/Y H:i') {
    if (!$date) return '-';
    return date($format, strtotime($date));
}

/**
 * Formata moeda
 */
function format_money($value) {
    return 'R$ ' . number_format($value, 2, ',', '.');
}

/**
 * Gera chave de licença
 */
function generate_license_key() {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $segments = [];
    
    for ($i = 0; $i < 4; $i++) {
        $segment = '';
        for ($j = 0; $j < 4; $j++) {
            $segment .= $chars[random_int(0, strlen($chars) - 1)];
        }
        $segments[] = $segment;
    }
    
    return implode('-', $segments);
}

/**
 * Debug helper
 */
function dd(...$vars) {
    echo '<pre>';
    foreach ($vars as $var) {
        var_dump($var);
    }
    echo '</pre>';
    exit;
}
