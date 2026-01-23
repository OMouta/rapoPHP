<?php

namespace App\Pages;

use Rapo\Component;
use Rapo\Components\Link;

class Page extends Component
{
    public function view(): string
    {
        return h('div', ['class' => 'max-w-4xl mx-auto space-y-12 py-12'], [
            h('div', ['class' => 'text-center space-y-4'], [
                h('h1', ['class' => 'text-6xl font-black text-slate-900'], 'Next-gen PHP Framework'),
                h('p', ['class' => 'text-xl text-slate-600'], 'Zero-config, folder-based routing, and server-side components.'),
                h('div', ['class' => 'flex justify-center gap-4'], [
                    component(Link::class, ['href' => '/features', 'class' => 'bg-slate-900 text-white px-8 py-3 rounded-lg font-bold'], 'Explore Features'),
                    component(Link::class, ['href' => '/login', 'class' => 'border border-slate-200 px-8 py-3 rounded-lg font-bold hover:bg-slate-100'], 'Login Demo'),
                ])
            ]),
            
            h('div', ['class' => 'grid md:grid-cols-3 gap-8 pt-12'], [
                $this->featureCard('📁 Directory Routing', 'Clean URLs based on your folder structure.', 'directory-routing'),
                $this->featureCard('⚡ Rapo-Live', 'Reactive components without writing JavaScript.', 'rapo-live'),
                $this->featureCard('🏗 Typesafe UI', 'Build components with nested layout support.', 'typesafe-ui')
            ])
        ]);
    }

    private function featureCard($title, $desc, $slug)
    {
        return component(Link::class, ['href' => "/features/{$slug}", 'class' => 'block transition-transform hover:-translate-y-2'], [
            h('div', ['class' => 'p-6 bg-white rounded-2xl shadow-sm border border-slate-100 h-full'], [
                h('h3', ['class' => 'font-bold text-lg mb-2'], $title),
                h('p', ['class' => 'text-slate-500 mb-4'], $desc),
                h('span', ['class' => 'text-sky-600 text-sm font-bold'], 'Read docs →')
            ])
        ]);
    }
}
