<?php

namespace Rapo;

class Config {
    protected $items = [];

    public function __construct(array $items = []) {
        $this->items = $items;
    }

    public static function load(string $path) {
        $items = [];
        if (is_dir($path)) {
            foreach (glob($path . '/*.php') as $file) {
                $key = basename($file, '.php');
                $items[$key] = require $file;
            }
        }
        return new static($items);
    }

    public function get(string $key, $default = null) {
        $array = $this->items;
        if (strpos($key, '.') === false) {
            return $array[$key] ?? $default;
        }

        foreach (explode('.', $key) as $segment) {
            if (is_array($array) && array_key_exists($segment, $array)) {
                $array = $array[$segment];
            } else {
                return $default;
            }
        }

        return $array;
    }

    public function set(string $key, $value) {
        $keys = explode('.', $key);
        $array = &$this->items;

        while (count($keys) > 1) {
            $key = array_shift($keys);
            if (!isset($array[$key]) || !is_array($array[$key])) {
                $array[$key] = [];
            }
            $array = &$array[$key];
        }

        $array[array_shift($keys)] = $value;
    }

    public function all() {
        return $this->items;
    }
}
