<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
*/

// Handle CORS preflight requests
$router->options('{any:.*}', function () {
    return response('', 200)
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
        ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
});

// Health check
$router->get('/', function () use ($router) {
    return response()->json([
        'name' => 'Delivery Rider API',
        'version' => '1.0.0',
        'status' => 'running',
        'lumen' => $router->app->version()
    ]);
});

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/

$router->post('/api/auth/login', 'AuthController@login');

/*
|--------------------------------------------------------------------------
| Orders Routes
|--------------------------------------------------------------------------
*/

$router->get('/api/orders', 'OrdersController@index');
$router->get('/api/orders/history', 'OrdersController@history');
$router->get('/api/orders/{id}', 'OrdersController@show');
$router->post('/api/orders/{id}/accept', 'OrdersController@accept');
$router->post('/api/orders/{id}/complete', 'OrdersController@complete');

/*
|--------------------------------------------------------------------------
| Profile Routes
|--------------------------------------------------------------------------
*/

$router->get('/api/profile', 'ProfileController@show');
$router->put('/api/profile', 'ProfileController@update');
