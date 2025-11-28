<?php

use App\Core\Router;

/**
 * Rotas da API (para o plugin cliente WordPress)
 */

Router::group(['prefix' => '/api/v1'], function() {
    
    // Validação de licença
    Router::post('/validate-license', 'Api\UpdateController@validateLicense');
    
    // Verificar atualizações
    Router::post('/check-updates', 'Api\UpdateController@checkUpdates');
    
    // Informações de um plugin
    Router::post('/plugin-info/{slug}', 'Api\UpdateController@pluginInfo');
    
    // Download de plugin
    Router::post('/download/{slug}', 'Api\UpdateController@download');
    Router::get('/download/{slug}', 'Api\UpdateController@download');
    
    // Lista de plugins disponíveis
    Router::post('/plugins', 'Api\UpdateController@listPlugins');
    
    // Status da licença
    Router::get('/license/status', 'Api\UpdateController@licenseStatus');
    Router::post('/license/status', 'Api\UpdateController@licenseStatus');
    
    // Ativação/Desativação de licença
    Router::post('/license/activate', 'Api\UpdateController@activateLicense');
    Router::post('/license/deactivate', 'Api\UpdateController@deactivateLicense');
    
    // API de Assinatura (para o plugin WordPress)
    Router::get('/subscription/prices', 'CheckoutController@getPricesApi');
    Router::post('/subscription/create', 'CheckoutController@createSubscription');
    Router::post('/subscription/renew', 'CheckoutController@renewSubscription');
    Router::get('/subscription/status/{payment_id}', 'CheckoutController@checkPaymentStatus');
    
    // Webhook do Asaas
    Router::post('/webhook/asaas', 'Api\WebhookController@asaas');
});
