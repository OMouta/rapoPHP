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

        return new Application();
    }
}
