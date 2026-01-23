<?php

namespace Rapo;

class Router {
    protected $routes = [];
    protected $pagesPath = null;
    protected $pagesNamespace = 'App\\Pages';
    protected $apiPath = null;
    protected $apiNamespace = 'App\\Api';

    public function enableFileBasedRouting($path, $namespace = 'App\\Pages') {
        $this->pagesPath = $path;
        $this->pagesNamespace = $namespace;
    }

    public function enableApiRouting($path, $namespace = 'App\\Api') {
        $this->apiPath = $path;
        $this->apiNamespace = $namespace;
    }

    public function getPagesNamespace() {
        return $this->pagesNamespace;
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

        // Try File-based Routing (Next.js style)
        if ($this->pagesPath) {
            $parts = explode('/', trim($uri, '/'));
            $params = [];
            
            // Handle API Routes
            if ($this->apiPath && $parts[0] === 'api') {
                array_shift($parts); // remove 'api'
                if (empty($parts)) $parts = [];
                
                $result = $this->recursiveMatch($this->apiPath, $this->apiNamespace, $parts, $params, true);
                if ($result) {
                    $result['is_api'] = true;
                    return $result;
                }
            }

            if ($uri === '/') $parts = [];
            
            $result = $this->recursiveMatch($this->pagesPath, $this->pagesNamespace, $parts, $params);
            if ($result) {
                $result['is_page'] = true;
                return $result;
            }
        }

        return null;
    }

    protected function resolveFileRoute($basePath, $baseNamespace, $parts) {
        $currentNamespace = $baseNamespace;
        $params = [];
        $found = true;
        
        // Next.js convention: Folders in () are Route Groups and don't affect URL
        // folders starting with _ are Private and opted out of routing.
        
        $currentPath = $basePath;
        $segments = $parts;
        $targetNamespace = $baseNamespace;

        return $this->recursiveMatch($basePath, $baseNamespace, $segments, $params);
    }

    protected function recursiveMatch($dir, $ns, $segments, &$params, $isApi = false) {
        if (empty($segments)) {
            // Check for Page.php or Index.php (or Route.php for API)
            $files = $isApi ? ['Route'] : ['Page', 'Index'];
            foreach ($files as $file) {
                $class = $ns . '\\' . $file;
                if (class_exists($class)) {
                    return [
                        'handler' => [$class, 'index'],
                        'params' => $params,
                        'hierarchy' => [$ns]
                    ];
                }
            }
            return null;
        }

        $segment = array_shift($segments);
        $found = null;

        if (is_dir($dir)) {
            $items = scandir($dir);
            
            // 1. Try exact match folder
            foreach ($items as $item) {
                if ($item === '.' || $item === '..') continue;
                if (str_starts_with($item, '_')) continue; // Private folder

                // Handle Route Groups (marketing) -> URL remains /
                if (preg_match('/^\((.+)\)$/', $item)) {
                    // Dive into group but keep same segment for matching
                    $res = $this->recursiveMatch($dir . '/' . $item, $ns . '\\' . $item, array_merge([$segment], $segments), $params, $isApi);
                    if ($res) {
                        array_unshift($res['hierarchy'], $ns);
                        return $res;
                    }
                    continue;
                }

                if (strtolower($item) === strtolower($segment) && is_dir($dir . '/' . $item)) {
                    $res = $this->recursiveMatch($dir . '/' . $item, $ns . '\\' . $item, $segments, $params, $isApi);
                    if ($res) {
                        array_unshift($res['hierarchy'], $ns);
                        return $res;
                    }
                }
            }

            // 2. Try dynamic segments [slug] or [...slug]
            foreach ($items as $item) {
                if ($item === '.' || $item === '..') continue;
                
                // Catch-all [...slug]
                if (preg_match('/^\[\.\.\.(.+)\]$/', $item, $m) && is_dir($dir . '/' . $item)) {
                    $paramName = $m[1];
                    $params[$paramName] = array_merge([$segment], $segments);
                    // Match Page.php inside the catch-all folder
                    $res = $this->recursiveMatch($dir . '/' . $item, $ns . '\\' . $item, [], $params, $isApi);
                    if ($res) {
                        array_unshift($res['hierarchy'], $ns);
                        return $res;
                    }
                }

                // Single segment [slug]
                if (preg_match('/^\[(.+)\]$/', $item, $m) && is_dir($dir . '/' . $item)) {
                    $paramName = $m[1];
                    $params[$paramName] = $segment;
                    $res = $this->recursiveMatch($dir . '/' . $item, $ns . '\\' . $item, $segments, $params, $isApi);
                    if ($res) {
                        array_unshift($res['hierarchy'], $ns);
                        return $res;
                    }
                }
            }
        }

        return null;
    }
}
