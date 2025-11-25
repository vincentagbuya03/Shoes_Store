<?php

/**
 * Delivery Rider API Routes
 * 
 * These routes define the API endpoints for the delivery rider dashboard.
 * All routes return sample JSON responses for testing without a database.
 */

/** @var \Laravel\Lumen\Routing\Router $router */

// Health check
$router->get('/', function () {
    return response()->json([
        'status' => 'ok',
        'message' => 'Delivery Rider API is running',
        'version' => '1.0.0'
    ]);
});

// API routes group
$router->group(['prefix' => 'api'], function () use ($router) {
    
    // Authentication routes
    $router->post('/auth/login', 'AuthController@login');
    $router->post('/auth/logout', 'AuthController@logout');
    
    // Orders routes
    $router->get('/orders', 'OrdersController@index');
    $router->get('/orders/history', 'OrdersController@history');
    $router->get('/orders/{id}', 'OrdersController@show');
    $router->post('/orders/{id}/accept', 'OrdersController@accept');
    $router->post('/orders/{id}/complete', 'OrdersController@complete');
    
    // Profile routes
    $router->get('/profile', 'ProfileController@show');
    $router->put('/profile', 'ProfileController@update');
});
