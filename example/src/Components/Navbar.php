<?php

namespace App\Components;

use Rapo\Component;
use Rapo\Components\Link;
use function Rapo\h;

class Navbar extends Component {
    public function view(): string {
        return h('nav', ['class' => 'bg-white shadow mb-8'],
            h('div', ['class' => 'max-w-7xl mx-auto px-4'],
                h('div', ['class' => 'flex justify-between h-16'],
                    h('div', ['class' => 'flex'],
                        h('div', ['class' => 'flex-shrink-0 flex items-center'],
                            h('span', ['class' => 'text-2xl font-black text-blue-600'], 'RapoPHP')
                        ),
                        h('div', ['class' => 'ml-10 flex space-x-8 items-center'],
                            (new Link(['href' => '/', 'class' => 'text-gray-900 px-3 py-2 text-sm font-medium hover:text-blue-600', 'children' => 'Home']))->render(),
                            (new Link(['href' => '/dashboard', 'class' => 'text-gray-500 px-3 py-2 text-sm font-medium hover:text-blue-600', 'children' => 'Dashboard']))->render(),
                            (new Link(['href' => '/about', 'class' => 'text-gray-500 px-3 py-2 text-sm font-medium hover:text-blue-600', 'children' => 'About']))->render(),
                            
                            (session_status() === PHP_SESSION_NONE ? (session_start() ? '' : '') : ''),
                            isset($_SESSION['is_logged_in']) ? (new Link(['href' => '/admin/secret', 'class' => 'text-red-500 px-3 py-2 text-sm font-bold hover:text-red-700', 'children' => '🔒 Secret Area']))->render() : '',

                            (new Link(['href' => '/non-existent-page', 'class' => 'text-gray-500 px-3 py-2 text-sm font-medium hover:text-red-600', 'children' => 'Test 404']))->render()
                        )
                    )
                )
            )
        );
    }
}
