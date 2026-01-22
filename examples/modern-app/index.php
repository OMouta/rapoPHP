<?php

require_once __DIR__ . '/../../rapo/Bootstrap.php';

$app = Rapo\Bootstrap::boot('App', __DIR__ . '/src');
$store = Rapo\Store::getDefault();

$store->get('router')->add('/', [\App\Controllers\HomeController::class, 'index']);
$store->get('router')->add('/user/{id}', [\App\Controllers\HomeController::class, 'user']);

$app->handle();


