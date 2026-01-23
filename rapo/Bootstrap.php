<?php

namespace Rapo;

class Bootstrap {
    public static function boot(string $appNamespace = 'App', string $appPath = '') {
        // Register framework autoloader
        require_once __DIR__ . '/autoload.php';

        // Load .env if exists in app root (parent of appPath usually)
        if ($appPath) {
            $envPath = dirname($appPath) . '/.env';
            Env::load($envPath);
        }

        // Register App autoloader if path provided
        if ($appPath) {
            spl_autoload_register(function ($class) use ($appNamespace, $appPath) {
                if (strpos($class, $appNamespace . '\\') === 0) {
                    $file = rtrim($appPath, '/') . '/' . str_replace('\\', '/', substr($class, strlen($appNamespace) + 1)) . '.php';
                    if (file_exists($file)) {
                        require $file;
                    }
                }
            });
        }

        // Initialize Container with defaults
        $container = Container::getInstance();
        $store = Store::getDefault();
        
        $container->singleton(Http\Request::class, fn() => $store->get('request'));
        $container->singleton(Http\Response::class, fn() => $store->get('response'));
        $container->singleton(Router::class, fn() => $store->get('router'));
        $container->singleton(Database::class, fn() => $store->get('db'));

        // Auto-configure File-based Routing
        $router = $store->get('router');
        $pagesPath = $appPath . '/Pages';
        $apiPath = $appPath . '/Api';

        if (is_dir($pagesPath)) {
            $router->enableFileBasedRouting($pagesPath, $appNamespace . '\\Pages');
        }

        if (is_dir($apiPath)) {
            $router->enableApiRouting($apiPath, $appNamespace . '\\Api');
        }

        return new Application();
    }
}
