<?php

namespace Rapo;

class Hook {
    protected static $hooks = [];

    public static function add($name, callable $callback) {
        static::$hooks[$name][] = $callback;
    }

    public static function run($name, ...$args) {
        if (!isset(static::$hooks[$name])) return;
        foreach (static::$hooks[$name] as $callback) {
            call_user_func_array($callback, $args);
        }
    }
}
