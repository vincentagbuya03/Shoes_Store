<?php

/**
 * Delivery Rider API - Entry Point
 * 
 * This is a Laravel Lumen micro-app serving as the API scaffold
 * for the Delivery Rider Dashboard.
 * 
 * To run locally:
 * php -S localhost:8000 -t public
 */

$app = require __DIR__.'/../bootstrap/app.php';

$app->run();
