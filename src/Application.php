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
        $store = Store::getDefault();
        $guards = $store->get('config')['middleware']['guards'] ?? [];
        
        if (is_string($middleware) && isset($guards[$middleware])) {
            $mapped = $guards[$middleware];
            if (is_array($mapped)) {
                foreach ($mapped as $m) $this->middlewares[] = $m;
            } else {
                $this->middlewares[] = $mapped;
            }
        } else {
            $this->middlewares[] = $middleware;
        }
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

            $uri = $request->getUri();
            $method = $request->getMethod();

            // Match Route first to find hierarchy for local middlewares
            $match = $router->handle($uri, $method);

            if (!$match) {
                throw new \Exception("Page not found", 404);
            }

            // Load hierarchy-based middlewares
            if (isset($match['hierarchy'])) {
                $this->loadHierarchicalMiddleware($match['hierarchy']);
            }

            // Load Hierarchical i18n
            if (isset($match['paths'])) {
                $this->store->get('translation')->load($match['paths']);
            }

            // Run Middlewares
            foreach ($this->middlewares as $middleware) {
                $result = is_callable($middleware) ? $middleware($request, $response) : (new $middleware())->handle($request, $response);
                if ($result === false) return; // Middleware halted execution
            }

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

            $handler = $match['handler'];
            $params = $match['params'];
            $isApi = $match['is_api'] ?? false;

            // Handle Server Actions (Action.php or _action)
            if ($method === 'POST') {
                $this->handleServerAction($match, $request, $response);
            }

            if ($handler instanceof \Closure) {
                $content = call_user_func_array($handler, $params);
            } elseif ($handler === 'markdown') {
                $md = file_get_contents($match['markdown_file']);
                // Very basic markdown parser for the demo
                $content = $this->parseMarkdown($md);
            } elseif (is_array($handler) || is_string($handler)) {
                $controllerClass = is_array($handler) ? $handler[0] : $handler;
                $action = is_array($handler) ? $handler[1] : 'index';
                
                // Reflection for Attributes (Validation DTO support)
                $reflection = new \ReflectionClass($controllerClass);
                if ($reflection->hasMethod($action)) {
                    $methodRef = $reflection->getMethod($action);
                    $attributes = $methodRef->getAttributes(\Rapo\Http\Attributes\Validate::class);
                    foreach ($attributes as $attr) {
                        $validate = $attr->newInstance();
                        $request->validate($validate->rules);
                    }
                }

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
                        // Before slow data fetching, we could theoretically send Loading.php
                        // In PHP this requires Ob_flush which is tricky with Nested Layouts.
                        // For now we just run it.
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

                    // Automatic Page-to-API Mirroring
                    if (!$isApi && str_contains($request->getHeader('Accept') ?? '', 'application/json')) {
                        $mirrorData = property_exists($instance, 'props') ? $instance->props : [];
                        if (method_exists($instance, 'getServerSideProps')) {
                            $mirrorData = array_merge($mirrorData, $instance->getServerSideProps($request, $params));
                        }
                        $response->json($mirrorData)->send();
                        return;
                    }

                    // Server Actions support (Legacy _action)
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

            // Inject Debug Badge
            if (Env::get('DEBUG') === 'true' && is_string($content) && str_contains($content, '</body>')) {
                $content = str_replace('</body>', $this->renderDebugBadge($match, $instance) . '</body>', $content);
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
        $path = getcwd() . '/middleware.php';
        if (file_exists($path)) {
            $middlewares = require $path;
            if (is_array($middlewares)) {
                foreach ($middlewares as $m) $this->middlewares[] = $m;
            }
        }
    }

    protected function loadHierarchicalMiddleware($hierarchy) {
        $router = $this->store->get('router');
        foreach ($hierarchy as $ns) {
            // Convert namespace back to path relative to src
            $relPath = str_replace([$router->getPagesNamespace() . '\\', '\\'], ['', '/'], $ns);
            $possiblePath = getcwd() . '/src/Pages/' . trim($relPath, '/') . '/middleware.php';
            if (file_exists($possiblePath)) {
                $middlewares = require $possiblePath;
                if (is_array($middlewares)) {
                    foreach ($middlewares as $m) $this->use($m);
                }
            }
        }
    }

    protected function handleServerAction($match, $request, $response) {
        $hierarchy = $match['hierarchy'] ?? [];
        // Check for Action.php in the hierarchy, starting from most specific
        foreach (array_reverse($hierarchy) as $ns) {
            $class = $ns . '\\Action';
            if (class_exists($class)) {
                $instance = Container::getInstance()->resolve($class);
                if (method_exists($instance, 'handle')) {
                    $result = $instance->handle($request, $response);
                    if ($result instanceof \Rapo\Http\Response) {
                        $result->send();
                        exit; // Halt for redirect or direct response
                    }
                }
            }
        }
    }

    protected function getCachedPage($uri) {
        $cache = $this->store->get('cache');
        $isSpa = $this->store->get('request')->getHeader('X-Rapo-Spa') === 'true';
        $cacheKey = 'isr_' . md5($uri . ($isSpa ? ':spa' : ''));
        return $cache->get($cacheKey);
    }

    protected function cachePage($uri, $content, $revalidate) {
        if (!is_string($content)) return;
        $cache = $this->store->get('cache');
        $isSpa = $this->store->get('request')->getHeader('X-Rapo-Spa') === 'true';
        $cacheKey = 'isr_' . md5($uri . ($isSpa ? ':spa' : ''));
        $cache->set($cacheKey, $content, $revalidate);
    }

    protected function parseMarkdown($text) {
        $parsedown = new \Parsedown();
        $parsedown->setSafeMode(true);
        $html = $parsedown->text($text);
        return "<div class=\"prose mx-auto py-10\">$html</div>";
    }

    protected function renderDebugBadge($match, $instance = null) {
        $props = $instance ? json_encode($instance->props ?? [], JSON_PRETTY_PRINT) : '{}';
        $hierarchy = json_encode($match['hierarchy'] ?? [], JSON_PRETTY_PRINT);
        
        return "
        <div id=\"rapo-debug-badge\" style=\"position:fixed; bottom:20px; right:20px; z-index:9999;\">
            <button onclick=\"document.getElementById('rapo-debug-panel').style.display='block'\" style=\"background:#007bff; color:white; border:none; padding:10px 15px; border-radius:50px; cursor:pointer; font-weight:bold; box-shadow:0 4px 12px rgba(0,0,0,0.1);\">Rapo Debug</button>
        </div>
        <div id=\"rapo-debug-panel\" style=\"display:none; position:fixed; bottom:80px; right:20px; width:350px; max-height:500px; background:white; border:1px solid #ddd; border-radius:12px; z-index:9999; overflow-y:auto; font-family:sans-serif; box-shadow:0 8px 24px rgba(0,0,0,0.15);\">
            <div style=\"padding:15px; border-bottom:1px solid #eee; display:flex; justify-content:space-between; align-items:center;\">
                <strong style=\"color:#333\">Rapo Context</strong>
                <button onclick=\"document.getElementById('rapo-debug-panel').style.display='none'\" style=\"background:none; border:none; cursor:pointer; font-size:18px;\">&times;</button>
            </div>
            <div style=\"padding:15px; font-size:12px;\">
                <p><strong>Route Hierarchy:</strong></p>
                <pre style=\"background:#f8f9fa; padding:10px; border-radius:6px; overflow-x:auto;\">{$hierarchy}</pre>
                <p><strong>Page Props:</strong></p>
                <pre style=\"background:#f8f9fa; padding:10px; border-radius:6px; overflow-x:auto;\">{$props}</pre>
            </div>
        </div>
        ";
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

        // Vite-style critical overlay for non-404 errors in debug mode
        if ($code !== 404 && Env::get('DEBUG') === 'true') {
            Debug::handleError(E_USER_ERROR, $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTrace());
            $response->setStatusCode($code)->setContent(Debug::renderDevTools())->send();
            return;
        }

        $isSpa = $request->getHeader('X-Rapo-Spa') === 'true';
        $router = $this->store->get('router');

        // Check for App\Pages\Error component or 404
        $pageClass = $router->getPagesNamespace() . '\\Error';
        if ($code === 404 && class_exists($router->getPagesNamespace() . '\\NotFound')) {
            $pageClass = $router->getPagesNamespace() . '\\NotFound';
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
                    $content = $this->applyNestedLayouts($content, [
                        'is_page' => true, 
                        'handler' => $pageClass,
                        'hierarchy' => [$router->getPagesNamespace()]
                    ], $request);
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
