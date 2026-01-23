<?php

namespace App\Pages\features;

use Rapo\Component;
use Rapo\Components\Link;

class Page extends Component
{
    private $features = [
        [
            'slug' => 'directory-routing',
            'title' => '📁 Directory Routing',
            'desc' => 'Clean URLs based on your folder structure without any manual route definitions.'
        ],
        [
            'slug' => 'rapo-live',
            'title' => '⚡ Rapo-Live',
            'desc' => 'Interactive components that update in real-time without writing a single line of JavaScript.'
        ],
        [
            'slug' => 'typesafe-ui',
            'title' => '🏗 Typesafe UI',
            'desc' => 'Build complex UIs using standard PHP classes with full IDE support and type safety.'
        ],
        [
            'slug' => 'nested-layouts',
            'title' => '🪆 Nested Layouts',
            'desc' => 'Automatically inherit UI structures across different sections of your application.'
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
