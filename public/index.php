<?php

require_once __DIR__ . '/../rapo/Bootstrap.php';

$app = Rapo\Bootstrap::boot();
$store = Rapo\Store::getDefault();

$store->get('router')->add('/', function() {
    return "Welcome to RapoPHP! It's cool and good.";
});

$store->get('router')->add('/hello', function() {
    return "Hello World! The router is now updated with zero boilerplate.";
});

$app->handle();

