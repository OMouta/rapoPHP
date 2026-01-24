<?php

namespace App\Pages\features;

use Rapo\Component;
use Rapo\Components\Link;

class Page extends Component
{
    private $features = [
        [
            'slug' => 'file-based-routing',
            'title' => '🛣️ File-based Routing',
            'desc' => 'Zero-config routing where folders map to URLs, supporting dynamic segments and nested layouts.'
        ],
        [
            'slug' => 'component-centric-ui',
            'title' => '🍱 Component-Centric UI',
            'desc' => 'Pages are PHP components with JSX-style syntax and built-in hooks for a modern DX.'
        ],
        [
            'slug' => 'rapo-live',
            'title' => '⚡ Rapo-Live',
            'desc' => 'Livewire-style server-side reactivity for interactive components without writing JavaScript.'
        ],
        [
            'slug' => 'performance-dx',
            'title' => '🚀 Performance & DX',
            'desc' => 'Built-in Image optimization, ISR, Caching, and a modern CLI for fast development.'
        ],
        [
            'slug' => 'modern-data-auth',
            'title' => '💾 Modern Data & Auth',
            'desc' => 'Fluent Active Record ORM, automatic migrations, and full auth scaffolding in seconds.'
        ],
        [
            'slug' => 'functional-hooks',
            'title' => '🪝 Functional Hooks',
            'desc' => 'Use useRouter, useHead, useState, and useForm to manage application state and metadata.'
        ]
    ];

    public function view(): string
    {
        return h('div', ['class' => 'max-w-4xl mx-auto py-12'], [
            h('div', ['class' => 'text-center mb-16'], [
                h('h1', ['class' => 'text-5xl font-black text-slate-900 mb-4'], 'Everything you need'),
                h('p', ['class' => 'text-xl text-slate-600'], 'Discover the powerful features of RapoPHP Framework.')
            ]),
            
            h('div', ['class' => 'grid md:grid-cols-2 gap-8'], array_map(function($feature) {
                return component(Link::class, ['href' => '/features/' . $feature['slug'], 'class' => 'block'], [
                    h('div', ['class' => 'p-8 bg-white rounded-3xl shadow-sm border border-slate-100 hover:shadow-xl hover:border-sky-200 transition-all duration-300'], [
                        h('h3', ['class' => 'text-2xl font-bold mb-3'], $feature['title']),
                        h('p', ['class' => 'text-slate-500 mb-4'], $feature['desc']),
                        h('span', ['class' => 'text-sky-600 font-bold flex items-center gap-1'], [
                            'Learn more ',
                            h('span', [], '→')
                        ])
                    ])
                ]);
            }, $this->features))
        ]);
    }
}
