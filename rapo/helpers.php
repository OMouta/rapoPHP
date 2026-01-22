<?php

namespace Rapo;

function h(string $tag, array $props = [], ...$children): string {
    $attributes = '';
    foreach ($props as $key => $value) {
        if (is_bool($value)) {
            if ($value) $attributes .= " $key";
        } else {
            $attributes .= " $key=\"" . htmlspecialchars((string)$value) . "\"";
        }
    }

    $content = '';
    foreach ($children as $child) {
        if (is_array($child)) {
            $content .= implode('', $child);
        } else {
            $content .= (string)$child;
        }
    }

    $selfClosing = ['img', 'br', 'hr', 'input', 'link', 'meta'];
    if (in_array(strtolower($tag), $selfClosing)) {
        return "<{$tag}{$attributes} />";
    }

    return "<{$tag}{$attributes}>{$content}</{$tag}>";
}
