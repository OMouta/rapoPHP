<?php

namespace Rapo;

class Router {
    protected $routes = [];
    protected $pagesPath = null;
    protected $pagesNamespace = 'App\\Pages';
    protected $apiPath = null;
    protected $apiNamespace = 'App\\Api';
    protected $currentParams = [];

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

    public function getParams() {
        return $this->currentParams;
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
                $this->currentParams = $params;
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
                $apiParts = $parts;
                array_shift($apiParts); // remove 'api'
                
                // Magic Model API support
                if (isset($apiParts[0]) && $apiParts[0] === 'models') {
                    array_shift($apiParts);
                    if (!empty($apiParts)) {
                        $modelName = ucfirst($apiParts[0]);
                        $id = $apiParts[1] ?? null;
                        return [
                            'handler' => 'magic_api',
                            'model' => $modelName,
                            'id' => $id,
                            'params' => $params,
                            'is_api' => true
                        ];
                    }
                }
                
                if (empty($apiParts)) $apiParts = [];
                $result = $this->recursiveMatch($this->apiPath, $this->apiNamespace, $apiParts, $params, true, $method);
                if ($result) {
                    $this->currentParams = $params;
                    $result['is_api'] = true;
                    return $result;
                }
            }

            if ($uri === '/') $parts = [];
            
            $result = $this->recursiveMatch($this->pagesPath, $this->pagesNamespace, $parts, $params, false, $method);
            if ($result) {
                $this->currentParams = $params;
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

    protected function recursiveMatch($dir, $ns, $segments, &$params, $isApi = false, $method = 'GET', $hierarchyNs = null) {
        if ($hierarchyNs === null) $hierarchyNs = $ns;
        $loadingClass = null;
        if (!$isApi && file_exists($dir . '/Loading.php')) {
            $loadingClass = $ns . '\\Loading';
        }

        if (empty($segments)) {
            // Check for Page.php or Index.php (or Route.php for API)
            $files = $isApi ? ['Route'] : ['Page', 'Index'];
            $extensions = $isApi ? ['.php'] : ['.php', '.md'];

            foreach ($files as $file) {
                foreach ($extensions as $ext) {
                    $class = $ns . '\\' . $file;
                    $filePath = $dir . '/' . $file . $ext;
                    
                    if (file_exists($filePath)) {
                        if ($ext === '.md') {
                            return [
                                'handler' => 'markdown',
                                'params' => $params,
                                'hierarchy' => [$hierarchyNs],
                                'paths' => [$dir],
                                'markdown_file' => $filePath,
                                'loading' => $loadingClass ? [$loadingClass] : []
                            ];
                        }

                        require_once $filePath;
                        if (class_exists($class)) {
                            $action = 'index';
                            if ($isApi) {
                                // If it's an API route and the class has a method named after the HTTP method, use it
                                if (method_exists($class, $method)) {
                                    $action = $method;
                                }
                            }

                            return [
                                'handler' => [$class, $action],
                                'params' => $params,
                                'hierarchy' => [$hierarchyNs],
                                'paths' => [$dir],
                                'loading' => $loadingClass ? [$loadingClass] : []
                            ];
                        }
                    }
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

                // Next.js convention: Folders in () are Route Groups and don't affect URL
                $isGroup = preg_match('/^\((.+)\)$/', $item);
                
                // If it's a group, we stay on the same segments but dive into the folder
                if ($isGroup) {
                    $res = $this->recursiveMatch($dir . '/' . $item, $ns, array_merge([$segment], $segments), $params, $isApi, $method, $hierarchyNs . '\\' . $item);
                    if ($res) {
                        if ($loadingClass) array_unshift($res['loading'], $loadingClass);
                        if (!in_array($hierarchyNs, $res['hierarchy'])) {
                            array_unshift($res['hierarchy'], $hierarchyNs);
                            array_unshift($res['paths'], $dir);
                        }
                        return $res;
                    }
                    continue;
                }

                // If it's an exact match of the segment
                if (strtolower($item) === strtolower($segment) && is_dir($dir . '/' . $item)) {
                    $nextNs = $ns . '\\' . $item;
                    $res = $this->recursiveMatch($dir . '/' . $item, $nextNs, $segments, $params, $isApi, $method, $hierarchyNs . '\\' . $item);
                    if ($res) {
                        if ($loadingClass) array_unshift($res['loading'], $loadingClass);
                        if (!in_array($hierarchyNs, $res['hierarchy'])) {
                            array_unshift($res['hierarchy'], $hierarchyNs);
                            array_unshift($res['paths'], $dir);
                        }
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
                    // Match Page.php inside the catch-all folder, namespace doesn't include the [slug] folder
                    $res = $this->recursiveMatch($dir . '/' . $item, $ns, [], $params, $isApi, $method, $hierarchyNs . '\\' . $item);
                    if ($res) {
                        if ($loadingClass) array_unshift($res['loading'], $loadingClass);
                        if (!in_array($hierarchyNs, $res['hierarchy'])) {
                            array_unshift($res['hierarchy'], $hierarchyNs);
                            array_unshift($res['paths'], $dir);
                        }
                        return $res;
                    }
                }

                // Single segment [slug]
                if (preg_match('/^\[(.+)\]$/', $item, $m) && is_dir($dir . '/' . $item)) {
                    $paramName = $m[1];
                    $params[$paramName] = $segment;
                    // Namespace doesn't include the [slug] folder
                    $res = $this->recursiveMatch($dir . '/' . $item, $ns, $segments, $params, $isApi, $method, $hierarchyNs . '\\' . $item);
                    if ($res) {
                        if ($loadingClass) array_unshift($res['loading'], $loadingClass);
                        if (!in_array($hierarchyNs, $res['hierarchy'])) {
                            array_unshift($res['hierarchy'], $hierarchyNs);
                            array_unshift($res['paths'], $dir);
                        }
                        return $res;
                    }
                }
            }
        }

        return null;
    }

}
