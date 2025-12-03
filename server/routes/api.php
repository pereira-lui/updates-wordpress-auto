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
    
    // API Minha Conta - Relatórios do Cliente
    Router::get('/my/account', 'Api\UpdateController@myAccount');
    Router::post('/my/account', 'Api\UpdateController@myAccount');
    Router::get('/my/payments', 'Api\UpdateController@myPayments');
    Router::post('/my/payments', 'Api\UpdateController@myPayments');
    Router::get('/my/updates', 'Api\UpdateController@myUpdates');
    Router::post('/my/updates', 'Api\UpdateController@myUpdates');
    Router::post('/my/log-update', 'Api\UpdateController@logUpdate');
    
    // API de Status de Atualização (Safe Updater)
    Router::post('/update/started', 'Api\UpdateController@updateStarted');
    Router::post('/update/success', 'Api\UpdateController@updateSuccess');
    Router::post('/update/error', 'Api\UpdateController@updateError');
    Router::post('/update/rollback', 'Api\UpdateController@updateRollback');
    
    // API de Preferências de Notificação
    Router::get('/notifications/preferences', 'Api\UpdateController@getNotificationPreferences');
    Router::post('/notifications/preferences', 'Api\UpdateController@saveNotificationPreferences');
    
    // Webhook do Asaas
    Router::post('/webhook/asaas', 'Api\WebhookController@asaas');
});
