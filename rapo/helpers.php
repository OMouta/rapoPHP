<?php

namespace Rapo;

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
