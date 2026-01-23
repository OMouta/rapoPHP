<?php

namespace App\Pages\Admin;

use Rapo\Controller;
use Rapo\Components\Link;
use function Rapo\h;

class Secret extends Controller {
    public function index() {
        return h('div', ['class' => 'max-w-2xl mx-auto py-12'],
            h('div', ['class' => 'bg-red-50 border-l-4 border-red-500 p-8 rounded-r-xl shadow-lg'],
                h('h1', ['class' => 'text-3xl font-black text-red-700 mb-4'], '🔒 This is Secret!'),
                h('p', ['class' => 'text-red-600 mb-6'], 'You only see this because you passed the global middleware check.'),
                h('div', ['class' => 'bg-white p-4 rounded border border-red-100 mb-6'],
                    h('p', ['class' => 'font-mono text-sm'], 'Secret Code: RapoPHP-Rocks-2024')
                ),
                (new Link(['href' => '/', 'class' => 'inline-block bg-red-600 text-white px-6 py-2 rounded font-bold transition-transform active:scale-95', 'children' => 'Go back safely']))->render()
            )
        );
    }
}
