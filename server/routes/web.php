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
    Router::get('/licenses/friend', 'Admin\LicenseController@createFriend');
    Router::post('/licenses/friend', 'Admin\LicenseController@storeFriend');
    Router::get('/licenses/{id}', 'Admin\LicenseController@show');
    Router::get('/licenses/{id}/edit', 'Admin\LicenseController@edit');
    Router::post('/licenses/{id}', 'Admin\LicenseController@update');
    Router::post('/licenses/{id}/delete', 'Admin\LicenseController@delete');
    Router::post('/licenses/{id}/toggle', 'Admin\LicenseController@toggle');
    Router::post('/licenses/{id}/regenerate', 'Admin\LicenseController@regenerateKey');
    
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
    Router::get('/settings/profile', 'Admin\SettingsController@profile');
    Router::post('/settings/profile', 'Admin\SettingsController@updateProfile');
    Router::get('/settings/users', 'Admin\SettingsController@users');
    Router::get('/settings/users/create', 'Admin\SettingsController@createUser');
    Router::post('/settings/users', 'Admin\SettingsController@storeUser');
    Router::post('/settings/users/{id}/delete', 'Admin\SettingsController@destroyUser');
    
    // Logs
    Router::get('/logs', 'Admin\LogController@index');
    Router::get('/settings/logs', 'Admin\SettingsController@logs');
    Router::post('/settings/logs/clear', 'Admin\SettingsController@clearLogs');
    
    // Relatório de pagamentos
    Router::get('/payments/report', 'Admin\PaymentController@report');
    Router::get('/payments/export', 'Admin\PaymentController@export');
});
