<?php

namespace App\Pages\Dashboard;

use Rapo\Component;
use Rapo\Components\Link;
use function Rapo\h;

class Layout extends Component {
    public function view(): string {
        return h('div', ['class' => 'flex gap-8'],
            h('aside', ['class' => 'w-48 shrink-0'],
                h('ul', ['class' => 'space-y-2'],
                    h('li', [], (new Link(['href' => '/dashboard', 'class' => 'font-bold text-blue-600', 'children' => 'Overview']))->render()),
                    h('li', [], (new Link(['href' => '/dashboard/settings', 'class' => 'text-gray-600', 'children' => 'Settings']))->render()),
                    h('li', [], (new Link(['href' => '/dashboard/stats', 'class' => 'text-gray-600', 'children' => 'Stats']))->render())
                )
            ),
            h('div', ['class' => 'flex-1'], 
                h('div', ['class' => 'bg-white p-6 rounded-xl border border-gray-100 shadow-sm'],
                    $this->props['children']
                )
            )
        );
    }
}
