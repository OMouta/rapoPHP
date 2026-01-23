<?php

namespace App\Components;

use Rapo\Component;
use Rapo\Components\Link;

class Navbar extends Component
{
    public function view(): string
    {
        return h('nav', ['class' => 'bg-slate-900 text-white p-4 flex justify-between items-center'], [
            h('div', ['class' => 'font-bold text-xl'], [
                component(Link::class, ['href' => '/'], 'RapoPHP')
            ]),
            h('div', ['class' => 'flex space-x-6 items-center'], [
                component(Link::class, ['href' => '/features', 'class' => 'hover:text-sky-400'], 'Features'),
                component(Link::class, ['href' => '/examples', 'class' => 'hover:text-sky-400'], 'Examples'),
                component(Link::class, ['href' => '/blog/hello-world', 'class' => 'hover:text-sky-400'], 'Blog'),
                component(Link::class, ['href' => '/about', 'class' => 'hover:text-sky-400'], 'About'),
                component(Link::class, ['href' => '/login', 'class' => 'bg-sky-600 px-4 py-2 rounded hover:bg-sky-500 transition'], 'Login')
            ])
        ]);
    }
}
