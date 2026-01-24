<?php

namespace Rapo;

use Dotenv\Dotenv;

class Env {
    protected static $data = [];

    public static function load(string $path) {
        if (!file_exists($path)) return;

        $dotenv = Dotenv::createImmutable(dirname($path), basename($path));
        self::$data = $dotenv->load();
    }

    public static function get(string $key, $default = null) {
        return self::$data[$key] ?? $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key) ?: $default;
    }
}
