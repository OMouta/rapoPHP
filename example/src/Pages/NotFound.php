<?php

namespace App\Pages;

use Rapo\Component;
use Rapo\Components\Link;

class NotFound extends Component
{
    public function view(): string
    {
        return h('div', ['class' => 'min-h-[70vh] flex flex-col items-center justify-center text-center px-4'], [
            h('h1', ['class' => 'text-9xl font-black text-slate-200 mb-4'], '404'),
            h('h2', ['class' => 'text-4xl font-bold text-slate-900 mb-6'], 'Page not found'),
            h('p', ['class' => 'text-xl text-slate-500 mb-10 max-w-md'], 'Sorry, we couldn\'t find the page you\'re looking for. It might have been moved or deleted.'),
            component(Link::class, [
                'href' => '/', 
                'class' => 'px-8 py-4 bg-sky-600 text-white font-bold rounded-2xl hover:bg-sky-700 transition shadow-lg shadow-sky-200'
            ], 'Go back home')
        ]);
    }
}
