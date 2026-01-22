<?php

namespace Rapo\Http;

class Request {
    public function getMethod() {
        return $_SERVER['REQUEST_METHOD'];
    }

    public function getUri() {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $scriptPath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
        
        if (strpos($uri, $scriptPath) === 0) {
            $uri = substr($uri, strlen($scriptPath));
        }

        $uri = explode('?', $uri)[0];
        return '/' . trim($uri, '/');
    }

    public function getQuery($name = null, $default = null) {
        if ($name === null) return $_GET;
        return $_GET[$name] ?? $default;
    }

    public function getPost($name = null, $default = null) {
        if ($name === null) return $_POST;
        return $_POST[$name] ?? $default;
    }
}
