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
            
            // Handle API Routes
            if ($this->apiPath && $parts[0] === 'api') {
                array_shift($parts); // remove 'api'
                if (empty($parts)) $parts = ['Index'];
                
                $result = $this->resolveFileRoute($this->apiPath, $this->apiNamespace, $parts);
                if ($result) {
                    $result['is_api'] = true;
                    return $result;
                }
            }

            if ($uri === '/') $parts = ['Index'];
            
            $result = $this->resolveFileRoute($this->pagesPath, $this->pagesNamespace, $parts);
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

        foreach ($parts as $part) {
            $possibleClass = $currentNamespace . '\\' . ucfirst($part);
            if (class_exists($possibleClass)) {
                $currentNamespace = $possibleClass;
            } else {
                // Check if it's a directory
                $relativeDir = str_replace('\\', '/', substr($currentNamespace, strlen($baseNamespace) + 1));
                $dirPath = rtrim($basePath . '/' . $relativeDir, '/') . '/' . ucfirst($part);
                
                if (is_dir($dirPath)) {
                    $currentNamespace = $possibleClass;
                    continue;
                }

                // Try to find a dynamic segment (e.g. _Id)
                $dir = $basePath . '/' . $relativeDir;
                $dir = rtrim($dir, '/');
                
                $dynamicClass = null;
                if (is_dir($dir)) {
                    $files = scandir($dir);
                    foreach ($files as $file) {
                        if (str_starts_with($file, '_') && str_ends_with($file, '.php')) {
                            $paramName = strtolower(substr($file, 1, -4));
                            $dynamicClass = $currentNamespace . '\\' . substr($file, 0, -4);
                            $params[$paramName] = $part;
                            break;
                        }
                    }
                }

                if ($dynamicClass && class_exists($dynamicClass)) {
                    $currentNamespace = $dynamicClass;
                } else {
                    $found = false;
                    break;
                }
            }
        }

        if ($found) {
            // If the current result is not a class, try appending \Index
            if (!class_exists($currentNamespace)) {
                $indexClass = $currentNamespace . '\\Index';
                if (class_exists($indexClass)) {
                    $currentNamespace = $indexClass;
                } else {
                    $found = false;
                }
            }
        }

        if ($found && class_exists($currentNamespace)) {
            return [
                'handler' => [$currentNamespace, 'index'],
                'params' => $params
            ];
        }

        return null;
    }
}
