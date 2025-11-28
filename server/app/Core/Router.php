<?php

namespace App\Core;

/**
 * Roteador simples
 */
class Router {
    
    private static $routes = [];
    private static $currentGroup = [];
    
    public static function get($path, $handler) {
        self::addRoute('GET', $path, $handler);
    }
    
    public static function post($path, $handler) {
        self::addRoute('POST', $path, $handler);
    }
    
    public static function any($path, $handler) {
        self::addRoute('GET', $path, $handler);
        self::addRoute('POST', $path, $handler);
    }
    
    public static function group($options, $callback) {
        $previousGroup = self::$currentGroup;
        
        self::$currentGroup = array_merge(self::$currentGroup, $options);
        
        $callback();
        
        self::$currentGroup = $previousGroup;
    }
    
    private static function addRoute($method, $path, $handler) {
        $prefix = self::$currentGroup['prefix'] ?? '';
        $middleware = self::$currentGroup['middleware'] ?? null;
        
        $fullPath = rtrim($prefix, '/') . '/' . ltrim($path, '/');
        $fullPath = '/' . trim($fullPath, '/');
        
        self::$routes[] = [
            'method' => $method,
            'path' => $fullPath,
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }
    
    public function dispatch() {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Remove o base path (subdiretório) da URL
        $basePath = parse_url(APP_URL, PHP_URL_PATH) ?: '';
        if ($basePath && strpos($uri, $basePath) === 0) {
            $uri = substr($uri, strlen($basePath));
        }
        
        // Remove /public se existir
        $uri = preg_replace('#^/public#', '', $uri);
        $uri = '/' . trim($uri, '/');
        
        foreach (self::$routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            
            $pattern = $this->convertToRegex($route['path']);
            
            if (preg_match($pattern, $uri, $matches)) {
                array_shift($matches);
                
                // Verifica middleware
                if ($route['middleware']) {
                    $middlewareClass = "App\\Middleware\\{$route['middleware']}";
                    if (class_exists($middlewareClass)) {
                        $middleware = new $middlewareClass();
                        if (!$middleware->handle()) {
                            return;
                        }
                    }
                }
                
                // Executa o handler
                if (is_callable($route['handler'])) {
                    call_user_func_array($route['handler'], $matches);
                } elseif (is_string($route['handler'])) {
                    list($controller, $action) = explode('@', $route['handler']);
                    $controllerClass = "App\\Controllers\\{$controller}";
                    
                    if (class_exists($controllerClass)) {
                        $instance = new $controllerClass();
                        call_user_func_array([$instance, $action], $matches);
                    } else {
                        $this->notFound();
                    }
                }
                return;
            }
        }
        
        $this->notFound();
    }
    
    private function convertToRegex($path) {
        $pattern = preg_replace('/\{([a-zA-Z_]+)\}/', '([^/]+)', $path);
        return '#^' . $pattern . '$#';
    }
    
    private function notFound() {
        http_response_code(404);
        if (file_exists(VIEWS_PATH . '/errors/404.php')) {
            include VIEWS_PATH . '/errors/404.php';
        } else {
            echo '404 - Página não encontrada';
        }
    }
}
