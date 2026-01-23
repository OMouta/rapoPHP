<?php

namespace Rapo\Components;

use Rapo\Component;

class Image extends Component {
    public function view(): string {
        $src = $this->props['src'] ?? '';
        $alt = $this->props['alt'] ?? '';
        $width = $this->props['width'] ?? null;
        $height = $this->props['height'] ?? null;
        $class = $this->props['class'] ?? '';
        
        $props = [
            'src' => str_starts_with($src, 'http') ? $src : url($src),
            'alt' => $alt,
            'loading' => 'lazy',
            'class' => $class
        ];

        if ($width) $props['width'] = $width;
        if ($height) $props['height'] = $height;

        return h('img', $props);
    }
}
