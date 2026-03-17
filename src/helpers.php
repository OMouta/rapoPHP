<?php

/**
 * RapoPHP Global Helpers
 */

function h(string $tag, array $props = [], ...$children): string {
    $attributes = '';
    foreach ($props as $key => $value) {
        if (is_bool($value)) {
            if ($value) $attributes .= " $key";
        } elseif (is_array($value)) {
            // Support ['class' => ['a', 'b']]
            $value = implode(' ', $value);
            $attributes .= " $key=\"" . htmlspecialchars((string)$value) . "\"";
        } else {
            $attributes .= " $key=\"" . htmlspecialchars((string)$value) . "\"";
        }
    }

    $render = function($data) use (&$render) {
        if (is_array($data)) {
            $res = '';
            foreach ($data as $item) $res .= $render($item);
            return $res;
        }
        return (string)$data;
    };

    $content = $render($children);

    $selfClosing = ['img', 'br', 'hr', 'input', 'link', 'meta'];
    if (in_array(strtolower($tag), $selfClosing)) {
        return "<{$tag}{$attributes} />";
    }

    return "<{$tag}{$attributes}>{$content}</{$tag}>";
}

function url(?string $path = ''): string {
    $request = \Rapo\Store::getDefault()->get('request');
    $base = rtrim($request->getBasePath(), '/');
    return ($base ?: '') . '/' . ltrim($path, '/');
}

function asset(string $path): string {
    $request = \Rapo\Store::getDefault()->get('request');
    $base = rtrim($request->getBasePath(), '/');
    $fullPath = $_SERVER['DOCUMENT_ROOT'] . $base . '/' . ltrim($path, '/');
    
    $url = ($base ?: '') . '/' . ltrim($path, '/');
    if (file_exists($fullPath)) {
        $url .= '?v=' . filemtime($fullPath);
    }
    return $url;
}

function renderAssets(string $type): string {
    $assets = \Rapo\Component::getRegisteredAssets($type);
    $html = '';
    foreach ($assets as $url) {
        $urlString = is_array($url) ? implode('', $url) : (string)$url;
        if ($type === 'js') {
            $html .= "<script src=\"{$urlString}\"></script>\n";
        } elseif ($type === 'css') {
            $html .= "<link rel=\"stylesheet\" href=\"{$urlString}\">\n";
        }
    }
    return $html;
}

function env(string $key, $default = null) {
    return \Rapo\Env::get($key, $default);
}

function formAction(string $name): string {
    return h('input', ['type' => 'hidden', 'name' => '_action', 'value' => $name]);
}

function csrf_token(): string {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['_token'])) {
        $_SESSION['_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_token'];
}

function csrf_field(): string {
    return h('input', ['type' => 'hidden', 'name' => '_token', 'value' => csrf_token()]);
}

function session() {
    return \Rapo\Store::getDefault()->get('session') ?: new \Rapo\Http\Session();
}

function request(): \Rapo\Http\Request {
    return \Rapo\Store::getDefault()->get('request');
}

function post(?string $key = null, $default = null) {
    return request()->getPost($key, $default);
}

function get(?string $key = null, $default = null) {
    return request()->getQuery($key, $default);
}

function old(string $key, $default = null) {
    $old = session()->get('old', []); // Note: getFlash might have cleared it already if called elsewhere
    return $old[$key] ?? $default;
}

function errors(?string $key = null) {
    $errors = session()->get('errors', []);
    if ($key) return $errors[$key] ?? null;
    return $errors;
}

function cache() {
    return \Rapo\Store::getDefault()->get('cache');
}

function storage() {
    return \Rapo\Store::getDefault()->get('storage');
}

function queue($job = null, $data = [], $delay = 0) {
    $q = \Rapo\Store::getDefault()->get('queue');
    if ($job === null) return $q;
    return $q->push($job, $data, $delay);
}

function redirect(string $url) {
    return new \Rapo\Http\RedirectResponse($url);
}

if (!function_exists('dd')) {
    function dd(...$vars) {
        foreach ($vars as $v) {
            dump($v);
        }
        die(1);
    }
}

function component(string $class, array $props = [], $children = null): string {
    if (!class_exists($class)) {
        return "<!-- Component $class not found -->";
    }
    $instance = \Rapo\Container::getInstance()->resolve($class);
    if (!($instance instanceof \Rapo\Component)) {
        return "<!-- $class is not a valid Rapo Component -->";
    }
    if ($children !== null) {
        $props['children'] = $children;
    }
    $instance->props = array_merge($instance->props ?? [], $props);
    return $instance->render();
}

/**
 * Get configuration value
 */
function config(string $key, $default = null) {
    $config = \Rapo\Store::getDefault()->get('config');
    if ($config instanceof \Rapo\Config) {
        return $config->get($key, $default);
    }
    return $default;
}

/**
 * Log a message
 */
function logger($level = null, $message = null, array $context = []) {
    if ($level === null) {
        return new \Rapo\Log();
    }
    \Rapo\Log::log($level, $message, $context);
}

/**
 * Redirect back to previous page
 */
function back() {
    $url = $_SERVER['HTTP_REFERER'] ?? '/';
    return redirect($url);
}

/**
 * Vite asset helper
 */
function vite($entry = 'src/Assets/app.js'): string {
    return \Rapo\Vite::render($entry);
}

