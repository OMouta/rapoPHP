<?php

require_once __DIR__ . '/rapo/Bootstrap.php';

// Boot RapoPHP with App namespace
$app = Rapo\Bootstrap::boot('App', __DIR__ . '/src');

// Handle the app
$app->handle();
