<?php

namespace App\Pages\examples;

use Rapo\Component;
use Rapo\Components\Link;

class Page extends Component
{
    private $examples = [
        [
            'slug' => 'note_taking',
            'title' => '📝 Note Taking App',
            'desc' => 'A simple CRUD application demonstrating models, forms, and state management in RapoPHP.',
            'status' => 'Live'
        ],
        [
            'slug' => 'active-ecommerce',
            'title' => '🛒 Real-time E-commerce',
            'desc' => 'Showcasing Rapo-Live reactivity with a dynamic shopping cart and product filtering.',
            'status' => 'Coming Soon'
        ],
        [
            'slug' => 'auth-dashboard',
            'title' => '🔒 Secure Dashboard',
            'desc' => 'A protected dashboard example exploring built-in authentication and middleware.',
            'status' => 'Coming Soon'
        ]
    ];

    public function view(): string
    {
        return h('div', ['class' => 'max-w-4xl mx-auto py-12'], [
            h('div', ['class' => 'text-center mb-16'], [
                h('h1', ['class' => 'text-5xl font-black text-slate-900 mb-4'], 'Built with RapoPHP'),
                h('p', ['class' => 'text-xl text-slate-600'], 'Explore these example applications to see RapoPHP in action.')
            ]),
            
            h('div', ['class' => 'grid md:grid-cols-2 gap-8'], array_map(function($example) {
                return component(Link::class, ['href' => '/examples/' . $example['slug'], 'class' => 'block'], [
                    h('div', ['class' => 'p-8 bg-white rounded-3xl shadow-sm border border-slate-100 hover:shadow-xl hover:border-sky-200 transition-all duration-300 relative overflow-hidden'], [
                        $example['status'] === 'Coming Soon' ? 
                            h('div', ['class' => 'absolute top-0 right-0 bg-slate-100 text-slate-400 text-[10px] font-bold px-3 py-1 rounded-bl-xl uppercase'], 'Soon') :
                            h('div', ['class' => 'absolute top-0 right-0 bg-emerald-100 text-emerald-600 text-[10px] font-bold px-3 py-1 rounded-bl-xl uppercase'], 'Available'),
                        
                        h('h3', ['class' => 'text-2xl font-bold mb-3'], $example['title']),
                        h('p', ['class' => 'text-slate-500 mb-4'], $example['desc']),
                        h('span', ['class' => 'text-sky-600 font-bold flex items-center gap-1'], [
                            'View Example ',
                            h('span', [], '→')
                        ])
                    ])
                ]);
            }, $this->examples))
        ]);
    }
}
