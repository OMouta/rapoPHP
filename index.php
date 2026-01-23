<?php

require_once __DIR__ . '/rapo/Bootstrap.php';

// Boot RapoPHP with App namespace
$app = Rapo\Bootstrap::boot('App', __DIR__ . '/src');
$store = Rapo\Store::getDefault();

// 1. Enable File-based Routing (Next.js style)
$store->get('router')->enableFileBasedRouting(__DIR__ . '/src/Pages', 'App\\Pages');
$store->get('router')->enableApiRouting(__DIR__ . '/src/Api', 'App\\Api');

// The app will automatically load middleware.php if it exists in the root (handled by Application.php or Bootstrap)

// Handle the app
$app->handle();
