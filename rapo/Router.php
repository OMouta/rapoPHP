<?php

namespace Rapo;

class Router {
    protected $routes = [];
    protected $pagesPath = null;
    protected $pagesNamespace = 'App\\Pages';

    public function enableFileBasedRouting($path, $namespace = 'App\\Pages') {
        $this->pagesPath = $path;
        $this->pagesNamespace = $namespace;
    }

    public function add($path, $handler, $methods = ['GET']) {
        $path = '/' . trim($path, '/');
        // Convert {param} to regex ([^/]+)
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $path);
        $pattern = '#^' . $pattern . '$#';

        $this->routes[] = [
            'pattern' => $pattern,
            'handler' => $handler,
            'methods' => (array)$methods
        ];
    }

    public function handle($uri, $method) {
        $uri = '/' . trim($uri, '/');
        foreach ($this->routes as $route) {
            if (preg_match($route['pattern'], $uri, $matches) && in_array($method, $route['methods'])) {
                // Remove numeric keys from matches
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                return [
                    'handler' => $route['handler'],
                    'params' => $params
                ];
            }
        }

        // Try File-based Rout