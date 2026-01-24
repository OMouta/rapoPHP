<?php

namespace Rapo;

class Translation {
    protected $data = [];

    public function load(array $hierarchyPaths) {
        $this->data = [];
        foreach ($hierarchyPaths as $path) {
            $file = rtrim($path, '/') . '/translation.php';
            if (file_exists($file)) {
                $translations = require $file;
                if (is_array($translations)) {
                    $this->data = array_replace_recursive($this->data, $translations);
                }
            }
        }
    }

    public function get($key, $default = null) {
        $parts = explode('.', $key);
        $current = $this->data;
        
        foreach ($parts as $part) {
            if (!isset($current[$part])) return $default ?: $key;
            $current = $current[$part];
        }

        return $current;
    }
}
