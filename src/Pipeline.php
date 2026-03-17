<?php

namespace Rapo;

use Rapo\Http\Request;

class Pipeline {
    protected $container;
    protected $pipes = [];
    protected $passable;

    public function __construct($container = null) {
        $this->container = $container ?: (class_exists(Container::class) ? Container::getInstance() : null);
    }

    public function send($passable) {
        $this->passable = $passable;
        return $this;
    }

    public function through($pipes) {
        $this->pipes = is_array($pipes) ? $pipes : func_get_args();
        return $this;
    }

    public function then(\Closure $destination) {
        $pipeline = array_reduce(
            array_reverse($this->pipes),
            $this->carry(),
            $this->prepareDestination($destination)
        );

        return $pipeline($this->passable);
    }

    protected function prepareDestination(\Closure $destination) {
        return function ($passable) use ($destination) {
            return $destination($passable);
        };
    }

    protected function carry() {
        return function ($stack, $pipe) {
            return function ($passable) use ($stack, $pipe) {
                if (is_string($pipe)) {
                    $pipe = $this->container ? $this->container->resolve($pipe) : new $pipe();
                }

                if (is_callable($pipe)) {
                    return $pipe($passable, $stack);
                } elseif (method_exists($pipe, 'handle')) {
                    return $pipe->handle($passable, $stack);
                } else {
                    throw new \Exception("Middleware must be callable or have a handle() method.");
                }
            };
        };
    }
}
