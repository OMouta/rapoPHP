<?php

namespace Rapo;

class Application {
    protected $store;
    protected $layoutClass = null;
    protected $middlewares = [];

    public function __construct($store = null) {
        $this->store = $store ?: Store::getDefault();
    }

    public function use($middleware) {
        $this->middlewares[] = $middleware;
        return $this;
    }

    public function setLayout($className) {
        $this->layoutClass = $className;
        return $this;
    }

    public function handle() {
        $request = $this->store->get('request');
        $router = $this->store->get('router');
        $response = $this->store->get('response');

        try {
            $controller = null;
            // Global middleware.php support
            $this->loadGlobalMiddleware();

            // Run Middlewares
            foreach ($this->middlewares as $middleware) {
                $result = is_callable($middleware) ? $middleware($request, $response) : (new $middleware())->handle($request, $response);
                if ($result === false) return; // Middleware halted execution
            }

            $uri = $request->getUri();
            $method = $request->getMethod();

            // ISR Cache Check
            if ($method === 'GET' && $cachedContent = $this->getCachedPage($uri)) {
                $response->setContent($cachedContent)->send();
                return;
            }

            // Internal Rapo-Live handling
            if (str_ends_with($uri, '/_rapo/live') && $method === 'POST') {
                $this->handleLiveRequest();
                return;
            }

            $match = $router->handle($uri, $method);

            if (!$match) {
                throw new \Exception("Page not found", 404);
            }

            $handler = $match['handler'];
            $params = $match['params'];
            $isApi = $match['is_api'] ?? false;

            if ($handler instanceof \Closure) {
                $content = call_user_func_array($handler, $params);
            } elseif (is_array($handler)) {
                $controllerClass = $handler[0];
                $action = $handler[1];
                
                // SEO Metadata support (Static)
                if (method_exists($controllerClass, 'getMetadata')) {
                    $metadata = $controllerClass::getMetadata($request, $params);
                    $head = $this->store->get('head');
                    if (isset($metadata['title'])) $head->setTitle($metadata['title']);
                    if (isset($metadata['description'])) $head->addTag("<meta name=\"description\" content=\"{$metadata['description']}\">");
                }

                $controller = new $controllerClass();

                // Controller Middlewares (Route Protection)
                if (property_exists($controller, 'middleware')) {
                    foreach ((array)$controller->middleware as $m) {
                        $mInstance = is_string($m) ? new $m() : $m;
                        $res = is_callable($mInstance) ? $mInstance($request, $response) : $mInstance->handle($request, $response);
                        if ($res === false) return;
                    }
                }

                // Server Actions support
                if ($method === 'POST' && $actionName = $request->getPost('_action')) {
                    if (method_exists($controller, $actionName)) {
                        $data = $request->getPost();
                        unset($data['_action']);
                        $controller->$actionName($data);
                    }
                }
                
                // Server-side Side Effects (getServerSideProps)
                $extraProps = [];
                if (method_exists($controller, 'getServerSideProps')) {
                    $extraProps = $controller->getServerSideProps($request, $params);
                    $controller->props = array_merge($controller->props, $extraProps);
                }

                $content = call_user_func_array([$controller, $action], $params);
            }

            // Handle API responses
            if ($isApi) {
                if (!$content instanceof \Rapo\Http\Response) {
                    $response->json($content)->send();
                } else {
                    $content->send();
                }
                return;
            }

            // Apply Layouts (Nested)
            if (is_string($content) && !str_contains($content, '<html')) {
                $content = $this->applyNestedLayouts($content, $match, $request);
            }

            // ISR Cache Save (Move to after layouts are applied)
            if ($method === 'GET' && isset($controller) && property_exists($controller, 'revalidate')) {
                $this->cachePage($uri, $content, $controller->revalidate);
            }

            if ($content instanceof \Rapo\Http\Response) {
                $content->send();
            } elseif (is_array($content) || is_object($content)) {
                $response->setHeader('Content-Type', 'application/json');
                $response->setContent(json_encode($content))->send();
            } else {
                $response->setContent((string)$content)->send();
            }

        } catch (\Throwable $e) {
            $this->handleError($e, $request, $response);
        }
    }

    protected function loadGlobalMiddleware() {
        // Assume middleware.php is in the app root if we can find it
        // For now, look in current directory or app path
        $path = $_SERVER['DOCUMENT_ROOT'] . $this->store->get('request')->getBasePath() . '/middleware.php';
        if (file_exists($path)) {
            $middlewares = require $path;
            if (is_array($middlewares)) {
                foreach ($middlewares as $m) $this->middlewares[] = $m;
            }
        }
    }

    protected function getCachedPage($uri) {
        $isSpa = $this->store->get('request')->getHeader('X-Rapo-Spa') === 'true';
        $cacheKey = md5($uri . ($isSpa ? ':spa' : ''));
        $cacheFile = __DIR__ . '/../cache/' . $cacheKey . '.html';

        if (file_exists($cacheFile)) {
            $meta = json_decode(file_get_contents($cacheFile . '.json'), true);
            if ($meta['expires_at'] > time()) {
                return file_get_contents($cacheFile);
            }
        }
        return null;
    }

    protected function cachePage($uri, $content, $revalidate) {
        if (!is_string($content)) return;
        if (!is_dir(__DIR__ . '/../cache')) mkdir(__DIR__ . '/../cache', 0777, true);
        
        $isSpa = $this->store->get('request')->getHeader('X-Rapo-Spa') === 'true';
        $cacheKey = md5($uri . ($isSpa ? ':spa' : ''));
        $cacheFile = __DIR__ . '/../cache/' . $cacheKey . '.html';

        file_put_contents($cacheFile, $content);
        file_put_contents($cacheFile . '.json', json_encode([
            'expires_at' => time() + $revalidate
        ]));
    }

    protected function applyNestedLayouts($content, $match, $request) {
        $isSpa = $request->getHeader('X-Rapo-Spa') === 'true';
        $layouts = [];

        // Find nested layouts based on namespace
        if (isset($match['is_page']) && is_array($match['handler'])) {
            $pageClass = $match['handler'][0];
            $pagesNS = $this->store->get('router')->getPagesNamespace();
            
            if (str_starts_with($pageClass, $pagesNS)) {
                $relativeNS = ltrim(substr($pageClass, strlen($pagesNS)), '\\');
                $parts = explode('\\', $relativeNS);
                array_pop($parts); // Remove page class name
                
                // Check root pages layout
                $layoutClass = $pagesNS . '\\Layout';
                if (class_exists($layoutClass)) {
                    $layouts[] = $layoutClass;
                }

                // Check directory-specific layouts
                $currentNS = $pagesNS;
                foreach ($parts as $part) {
                    $currentNS .= '\\' . $part;
                    $layoutClass = $currentNS . '\\Layout';
                    if (class_exists($layoutClass) && !in_array($layoutClass, $layouts)) {
                        $layouts[] = $layoutClass;
                    }
                }
            }
        }

        // Global layout set via setLayout() is the absolute outermost shell
        if ($this->layoutClass && !in_array($this->layoutClass, $layouts)) {
            array_unshift($layouts, $this->layoutClass);
        }

        // If SPA, we skip the outermost layout (Root layout that contains <html>)
        // because the client already has it.
        if ($isSpa && !empty($layouts)) {
            $head = $this->store->get('head');
            $this->store->get('response')->setHeader('X-Rapo-Title', $head->getTitle());
            array_shift($layouts); // Remove the RootLayout/Global Layout
        }

        // Reverse to wrap from inside out
        $layouts = array_reverse($layouts);

        foreach ($layouts as $layoutClass) {
            $layout = new $layoutClass([
                'children' => $content,
                'title' => $this->store->get('head')->getTitle()
            ]);
            $content = $layout->render();
        }

        return $content;
    }

    protected function handleError(\Throwable $e, $request, $response) {
        // Fallback error reporting
        error_log($e->getMessage());
        $code = ($e->getCode() >= 400 && $e->getCode() < 600) ? $e->getCode() : 500;
        $isSpa = $request->getHeader('X-Rapo-Spa') === 'true';

        // Check for App\Pages\Error component
        if (class_exists('App\\Pages\\Error')) {
            $errorPage = new \App\Pages\Error(['exception' => $e, 'statusCode' => $code]);
            $content = $errorPage->index();
            
            if ($isSpa) {
                $response->setHeader('X-Rapo-Title', "Error $code");
            } else {
                // Wrap in global layout if not SPA
                if ($this->layoutClass) {
                    $layoutClass = $this->layoutClass;
                    $layout = new $layoutClass([
                        'children' => $content,
                        'title' => "Error $code"
                    ]);
                    $content = $layout->render();
                }
            }
            
            $response->setStatusCode($code)->setContent((string)$content)->send();
        } else {
            $response->setStatusCode($code)->setContent("<h1>{$code} Internal Server Error</h1><p>{$e->getMessage()}</p>")->send();
        }
    }

    protected function handleLiveRequest() {
        $request = $this->store->get('request');
        $response = $this->store->get('response');
        
        $componentClass = $request->getPost('component');
        $action = $request->getPost('action');
        $props = json_decode($request->getPost('props', '[]'), true);

        if ($componentClass && class_exists($componentClass)) {
            // Clean any previous output (warnings) to ensure valid HTML response
            if (ob_get_level()) ob_clean();
            
            try {
                $component = new $componentClass($props);
                
                // Handle form data if present
                $formData = json_decode($request->getPost('form_data', '[]'), true);
                
                if (method_exists($component, $action)) {
                    if (!empty($formData)) {
                        $component->$action($formData);
                    } else {
                        $component->$action();
                    }
                }
                $response->setContent($component->render())->send();
            } catch (\Exception $e) {
                $response->setStatusCode(500)->setContent("Error: " . $e->getMessage())->send();
            }
        } else {
            $response->setStatusCode(400)->setContent("Invalid Component")->send();
        }
    }
}
