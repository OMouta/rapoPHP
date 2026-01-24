<?php

namespace Rapo;

class Env {
    protected static $data = [];

    public static function load(string $path) {
        if (!file_exists($path)) return;

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (str_starts_with(trim($line), '#')) continue;

            $parts = explode('=', $line, 2);
            if (count($parts) === 2) {
                $key = trim($parts[0]);
                $value = trim($parts[1]);
                
                // Remove quotes
                $value = trim($value, '"\'');
                
                self::$data[$key] = $value;
                putenv("$key=$value");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }

    public static function get(string $key, $default = null) {
        return self::$data[$key] ?? $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key) ?: $default;
    }
}
