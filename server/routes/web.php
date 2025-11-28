<?php

use App\Core\Router;

/**
 * Rotas da aplicação web
 */

// Rotas públicas
Router::get('/', 'HomeController@index');
Router::get('/login', 'AuthController@loginForm');
Router::post('/login', 'AuthController@login');
Router::get('/logout', 'AuthController@logout');

// Checkout público
Router::get('/pricing', 'CheckoutController@pricing');
Router::get('/checkout', 'CheckoutController@checkout');
Router::post('/checkout/process', 'CheckoutController@process');

// Rotas protegidas (admin)
Router::group(['prefix' => '/admin', 'middleware' => 'Auth'], function() {
    
    // Dashboard
    Router::get('/', 'Admin\DashboardController@index');
    Router::get('/dashboard', 'Admin\DashboardController@index');
    
    // Licenças
    Router::get('/licenses', 'Admin\LicenseController@index');
    Router::get('/licenses/create', 'Admin\LicenseController@create');
    Router::post('/licenses', 'Admin\LicenseController@store');
    Router::get('/licenses/{id}/edit', 'Admin\LicenseController@edit');
    Router::post('/licenses/{id}', 'Admin\LicenseController@update');
    Router::post('/licenses/{id}/delete', 'Admin\LicenseController@delete');
    Router::post('/licenses/{id}/toggle', 'Admin\LicenseController@toggle');
    
    // Plugins
    Router::get('/plugins', 'Admin\PluginController@index');
    Router::post('/plugins/upload', 'Admin\PluginController@upload');
    Router::post('/plugins/{id}/toggle', 'Admin\PluginController@toggle');
    Router::post('/plugins/{id}/delete', 'Admin\PluginController@destroy');
    
    // Planos
    Router::get('/plans', 'Admin\PlanController@index');
    Router::get('/plans/create', 'Admin\PlanController@create');
    Router::post('/plans', 'Admin\PlanController@store');
    Router::get('/plans/{id}/edit', 'Admin\PlanController@edit');
    Router::post('/plans/{id}', 'Admin\PlanController@update');
    Router::post('/plans/{id}/delete', 'Admin\PlanController@delete');
    Router::post('/plans/{id}/toggle', 'Admin\PlanController@toggle');
    
    // Pagamentos
    Router::get('/payments', 'Admin\PaymentController@index');
    Router::get('/payments/{id}', 'Admin\PaymentController@show');
    
    // Configurações
    Router::get('/settings', 'Admin\SettingsController@index');
    Router::post('/settings', 'Admin\SettingsController@update');
    
    // Logs
    Router::get('/logs', 'Admin\LogController@index');
});
