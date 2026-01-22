<?php

namespace Rapo;

class Application {
    protected $store;

    public function __construct($store = null) {
        $this->store = $store ?: Store::getDefault();
    }

    public function handle() {
        $request = $this->store->get('request');
        $router = $this->store->get('router');
        $response = $this->store->get('response');

        $uri = $request->getUri();
        $method = $request->getMethod();

        // Internal Rapo-Live handling
        if (str_ends_with($uri, '/_rapo/live') && $method === 'POST') {
            $this->handleLiveRequest();
            return;
        }

        $match = $router->handle($uri, $method);

        if (!$match) {
            $response->setStatusCode(404)->setContent("404 Not Found")->send();
            return;
        }

        $handler = $match['handler'];
        $params = $match['params'];

        if ($handler instanceof \Closure) {
            $content = call_user_func_array($handler, $params);
        } elseif (is_array($handler)) {
            $controllerClass = $handler[0];
            $action = $handler[1];
            
            $controller = new $controllerClass();
            $content = call_user_func_array([$controller, $action], $params);
        }

        if ($content instanceof \Rapo\Http\Response) {
            $content->send();
        } elseif (is_array($content) || is_object($content)) {
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode($content))->send();
        } else {
            $response->setContent((string)$content)->send();
        }
    }

    protected function handleLiveRequest() {
        $request = $this->store->get('request');
        $response = $this->store->get('response');
        
        $componentClass = $request->getPost('component');
        $action = $request->getPost('action');
        $props = json_decode($request->getPost('props', '[]'), true);

        if ($componentClass && class_exists($componentClass)) {
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
        } else {
            $response->setStatusCode(400)->setContent("Invalid Component")->send();
        }
    }
}
