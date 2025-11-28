<?php
/**
 * Configurações do sistema
 */

return [
    'name' => 'Premium Updates Server',
    'url' => getenv('APP_URL') ?: 'https://www.luiasystems.com/updates-wordpress-auto',
    'debug' => getenv('APP_DEBUG') ?: true,
    'timezone' => 'America/Sao_Paulo',
    
    // Banco de dados
    'database' => [
        'driver' => 'mysql',
        'host' => getenv('DB_HOST') ?: 'localhost',
        'port' => getenv('DB_PORT') ?: 3306,
        'database' => getenv('DB_DATABASE') ?: 'updates-wp',
        'username' => getenv('DB_USERNAME') ?: 'updates-wp',
        'password' => getenv('DB_PASSWORD') ?: '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
    ],
    
    // Asaas
    'asaas' => [
        'sandbox' => getenv('ASAAS_SANDBOX') ?: true,
        'api_key' => getenv('ASAAS_API_KEY') ?: '',
        'webhook_token' => getenv('ASAAS_WEBHOOK_TOKEN') ?: '',
    ],
    
    // Upload de plugins
    'uploads' => [
        'max_size' => 50 * 1024 * 1024, // 50MB
        'allowed_types' => ['zip'],
    ],
];
