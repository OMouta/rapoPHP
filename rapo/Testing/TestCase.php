<?php

namespace Rapo\Testing;

use Rapo\Bootstrap;
use Rapo\Http\Request;
use Rapo\Store;

class TestCase {
    protected $app;

    public function __construct() {
        // Basic app setup for testing
        $this->app = Bootstrap::boot('App', dirname(__DIR__, 2) . '/src');
    }

    public function get($uri) {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = $uri;
        
        // Refresh request in store
        Store::getDefault()->setShared('request', Request::class);
        
        ob_start();
        $this->app->handle();
        $content = ob_get_clean();
        
        return new TestResponse($content);
    }

    public function post($uri, $data = []) {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = $uri;
        $_POST = $data;
        
        Store::getDefault()->setShared('request', Request::class);
        
        ob_start();
        $this->app->handle();
        $content = ob_get_clean();
        
        return new TestResponse($content);
    }
}
