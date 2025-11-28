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
    
    // Lista de plugins disponíveis
    Router::post('/plugins', 'Api\UpdateController@listPlugins');
    
    // Webhook do Asaas
    Router::post('/webhook/asaas', 'Api\WebhookController@asaas');
});
