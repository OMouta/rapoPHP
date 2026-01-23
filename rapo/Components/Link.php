<?php

namespace Rapo\Components;

use Rapo\Component;
use function Rapo\h;
use function Rapo\url;

class Link extends Component {
    public function view(): string {
        $href = $this->props['href'] ?? '#';
        $fullUrl = url($href);
        $class = $this->props['class'] ?? '';
        $children = $this->props['children'] ?? '';

        return h('a', [
            'href' => $fullUrl,
            'class' => $class,
            'rapo-link' => $fullUrl
        ], $children);
    }
}
