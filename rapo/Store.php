<?php

namespace Rapo;

class Store {
    protected static $instance;
    protected $services = [];
    protected $sharedInstances = [];

    public function __construct() {
        static::$instance = $this;
        $this->registerDefaults();
    }

    protected function registerDefaults() {
        $this->setShared('request', \Rapo\Http\Request::class);
        $this->setShared('response', \Rapo\Http\Response::class);
        $this->setShared('router', \Rapo\Router::class);
        $this->setShared('db', function() {
            return new \Rapo\Database(['driver' => 'sqlite', 'path' => __DIR__ . '/../database.sqlite']);
        });
    }

    public static function getDefault() {
        if (!static::$instance) {
            static::$instance = new self();
        }
        return static::$instance;
    }

    public function set($name, $definition, $shared = false) {
        $this->services[$name] = [
            'definition' => $definition,
            'shared' => $shared
        ];
    }

    public function setShared($name, $definition) {
        $this->set($name, $definition, true);
    }

    public function get($name) {
        if (!isset($this->services[$name])) {
            throw new \Exception("Service '$name' not found in DI container");
        }

        if ($this->services[$name]['shared'] && isset($this->sharedInstances[$name])) {
            return $this->sharedInstances[$name];
        }

        $definition = $this->services[$name]['definition'];
        $instance = null;

        if ($definition instanceof \Closure) {
            $instance = $definition($this);
        } elseif (is_string($definition) && class_exists($definition)) {
            $instance = new $definition();
        } else {
            $instance = $definition;
        }

        if ($this->services[$name]['shared']) {
            $this->sharedInstances[$name] = $instance;
        }

        return $instance;
    }
}
