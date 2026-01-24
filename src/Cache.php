<?php

namespace Rapo;

class Cache {
    protected $path;

    public function __construct($path = null) {
        $this->path = $path ?: dirname(__DIR__) . '/cache';
        if (!is_dir($this->path)) {
            mkdir($this->path, 0777, true);
        }
    }

    public function get($key, $default = null) {
        $file = $this->getFilePath($key);
        if (!file_exists($file)) return $default;

        $data = unserialize(file_get_contents($file));
        if ($data['expires'] !== 0 && $data['expires'] < time()) {
            $this->forget($key);
            return $default;
        }

        return $data['value'];
    }

    public function set($key, $value, $ttl = null) {
        $expires = $ttl ? time() + $ttl : 0;
        $file = $this->getFilePath($key);
        
        $data = [
            'expires' => $expires,
            'value' => $value
        ];

        file_put_contents($file, serialize($data));
        return true;
    }

    public function forget($key) {
        $file = $this->getFilePath($key);
        if (file_exists($file)) {
            unlink($file);
        }
    }

    public function flush() {
        $files = glob($this->path . '/*');
        foreach ($files as $file) {
            if (is_file($file)) unlink($file);
        }
    }

    protected function getFilePath($key) {
        return $this->path . '/' . md5($key) . '.cache';
    }

    /**
     * Remember a value for a given time
     */
    public function remember($key, $ttl, \Closure $callback) {
        $value = $this->get($key);
        if ($value !== null) return $value;

        $value = $callback();
        $this->set($key, $value, $ttl);
        return $value;
    }
}
