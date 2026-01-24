<?php

namespace Rapo;

class View {
    public static function render(Component $component) {
        return $component->render();
    }

    // A helper to make it feel more JSX like if we wanted
    public static function create($componentClass, $props = []) {
        $component = new $componentClass($props);
        return $component->render();
    }
}
