<?php

namespace Rapo;

class Storage {
    protected $root;
    protected $baseUrl;

    public function __construct() {
        $this->root = \Rapo\Env::get('STORAGE_PATH', dirname(__DIR__) . '/storage/app');
        $this->baseUrl = \Rapo\Env::get('STORAGE_URL', '/storage');
    }

    public function put($path, $content) {
        $fullPath = $this->root . '/' . ltrim($path, '/');
        if (!is_dir(dirname($fullPath))) mkdir(dirname($fullPath), 0777, true);
        return file_put_contents($fullPath, $content);
    }

    public function get($path) {
        return file_get_contents($this->root . '/' . ltrim($path, '/'));
    }

    public function exists($path) {
        return file_exists($this->root . '/' . ltrim($path, '/'));
    }

    public function delete($path) {
        return unlink($this->root . '/' . ltrim($path, '/'));
    }

    public function url($path) {
        return $this->baseUrl . '/' . ltrim($path, '/');
    }

    public static function disk() {
        return new self();
    }
}
