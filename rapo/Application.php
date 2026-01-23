<?php

namespace Rapo;

class Application {
    protected $store;
    protected $layoutClass = null;
    protected $middlewares = [];

    public function __construct($store = null) {
        $this->store = $store ?: Store::getDefault();
        // Add default middlewares
        $this->use(\Rapo\Http\Middleware\VerifyCsrfToken::class);
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
            $instance = null;
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
            } elseif (is_array($handler) || is_string($handler)) {
                $controllerClass = is_array($handler) ? $handler[0] : $handler;
                $action = is_array($handler) ? $handler[1] : 'index';
                
                // SEO Metadata support (Static)
                if (method_exists($controllerClass, 'getMetadata')) {
                    $metadata = $controllerClass::getMetadata($request, $params);
                    $head = $this->store->get('head');
                    if (isset($metadata['title'])) $head->setTitle($metadata['title']);
                    if (isset($metadata['description'])) $head->addTag("<meta name=\"description\" content=\"{$metadata['description']}\">");
                }

                $instance = Container::getInstance()->resolve($controllerClass);

                // If it's a Component, we can just render it. If it's a Controller, we call the action.
                if ($instance instanceof \Rapo\Component) {
                    $instance->props = array_merge($instance->props ?? [], $params);
                    
                    // Server-side Data Fetching (getServerSideProps)
                    if (method_exists($instance, 'getServerSideProps')) {
                        $extraProps = $instance->getServerSideProps($request, $params);
                        $instance->props = array_merge($instance->props, $extraProps);
                    }
                    
                    $content = $instance->render();
                } else {
                    // Controller Middlewares (Route Protection)
                    if (property_exists($instance, 'middleware')) {
                        foreach ((array)$instance->middleware as $m) {
                            $mInstance = is_string($m) ? new $m() : $m;
                            $res = is_callable($mInstance) ? $mInstance($request, $response) : $mInstance->handle($request, $response);
                            if ($res === false) return;
                        }
                    }

                    // Server Actions support
                    if ($method === 'POST' && $actionName = $request->getPost('_action')) {
                        if (method_exists($instance, $actionName)) {
                            $data = $request->getPost();
                            unset($data['_action']);
                            $instance->$actionName($data);
                        }
                    }
                    
                    // Server-side Side Effects (getServerSideProps)
                    $extraProps = [];
                    if (method_exists($instance, 'getServerSideProps')) {
                        $extraProps = $instance->getServerSideProps($request, $params);
                        if (property_exists($instance, 'props')) {
                            $instance->props = array_merge($instance->props, $extraProps);
                        }
                    }

                    // For API routes, we pass the request as the first argument
                    $args = $isApi ? array_values(array_merge([$request], $params)) : array_values($params);
                    $content = call_user_func_array([$instance, $action], $args);
                }
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
            if ($method === 'GET' && $instance && isset($instance->revalidate)) {
                $this->cachePage($uri, $content, $instance->revalidate);
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

    protected function applyNestedLayouts($content, $match, $request): string {
        $isSpa = $request->getHeader('X-Rapo-Spa') === 'true';
        $layouts = [];
        $hierarchy = $match['hierarchy'] ?? [];

        foreach ($hierarchy as $ns) {
            foreach (['Layout', 'Template'] as $file) {
                $layoutClass = $ns . '\\' . $file;
                if (class_exists($layoutClass)) {
                    $layouts[] = $layoutClass;
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
            
            // In Next.js App Router, the root layout usually contains <html>.
            // When navigating in SPA mode, we typically replace only the page content.
            // If there's at least one layout, the first one is considered the "Root"
            array_shift($layouts); 
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

        // Check for App\Pages\Error component or 404
        $pageClass = 'App\\Pages\\Error';
        if ($code === 404 && class_exists('App\\Pages\\NotFound')) {
            $pageClass = 'App\\Pages\\NotFound';
        }

        if (class_exists($pageClass)) {
            $instance = Container::getInstance()->resolve($pageClass);
            
            if ($instance instanceof \Rapo\Component) {
                $instance->props = ['exception' => $e, 'statusCode' => $code];
                $content = $instance->render();
            } else {
                $content = $instance->index(['exception' => $e, 'statusCode' => $code]);
            }
            
            if ($isSpa) {
                $response->setHeader('X-Rapo-Title', "Error $code");
            } else {
                // Wrap in layouts if not a full HTML page
                if (!str_contains($content, '<html')) {
                    $content = $this->applyNestedLayouts($content, ['is_page' => true, 'handler' => $pageClass], $request);
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
