<?php

/**
 * Configure application routes
 * 
 * @param \Core\Router $router
 * @return void
 */
function registerRoutes(\Core\Router $router): void
{
    $router->group([], function ($router) {
        // You can add other resource routes here
        // Example: Product routes
        // $router->get('/api/v1/products', 'App\Controllers\ProductController@index');
        // $router->get('/api/v1/products/:int', 'App\Controllers\ProductController@show');
        // ...
    });

    // Example of adding versioned API endpoints
    // $router->group(['api-version-2'], function ($router) {
    //     $router->get('/api/v2/status', 'App\Controllers\StatusController@check');
    // });

    // Handle 404s for unmatched routes
    $router->get('*', function () {
        return \Core\Response::notFound('Route not found');
    });
}
