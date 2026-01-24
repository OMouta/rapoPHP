<?php

namespace App\Pages;

use Rapo\Component;
use App\Components\Navbar;

class Layout extends Component
{
    public function view(): string
    {
        return h('html', ['lang' => 'en'], [
            h('head', [], [
                h('meta', ['charset' => 'UTF-8']),
                h('meta', ['name' => 'viewport', 'content' => 'width=device-width, initial-scale=1.0']),
                h('title', [], 'RapoPHP Framework'),
                h('script', ['src' => 'https://cdn.tailwindcss.com']),
            ]),
            h('body', ['class' => 'bg-slate-50 min-h-screen flex flex-col'], [
                component(Navbar::class),
                h('main', ['class' => 'flex-grow container mx-auto p-8'], [
                    $this->props['children'] ?? ''
                ]),
                h('footer', ['class' => 'p-8 text-center text-slate-400 border-t'], [
                    'Built with ❤ using RapoPHP Framework'
                ])
            ])
        ]);
    }
}
