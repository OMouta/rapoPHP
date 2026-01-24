<?php

require_once __DIR__ . '/vendor/autoload.php';

// Boot RapoPHP with App namespace
$app = Rapo\Bootstrap::boot('App', __DIR__ . '/src');

// Handle the app
$app->handle();
