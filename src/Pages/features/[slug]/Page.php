<?php

namespace App\Pages\features;

use Rapo\Component;
use Rapo\Components\Link;

class Page extends Component
{
    private $featureData = [
        'file-based-routing' => [
            'icon' => '🛣️',
            'title' => 'Zero-Config File-based Routing',
            'content' => 'RapoPHP brings the Next.js App Router experience to PHP. Your directory structure defines your routes, eliminating the need for a central routes file.',
            'details' => [
                'Folder-based URLs: Each folder in src/Pages maps to a URL segment.',
                'Special Files: Use Page.php, Layout.php, Error.php, and NotFound.php for structure and handling.',
                'Route Groups: Use (folder) to organize code without affecting the URL.',
                'Dynamic Segments: Create dynamic paths using [slug] or catch-all routes with [...slug].',
                'Private Folders: Prefix folders with _ to keep them internal.'
            ]
        ],
        'component-centric-ui' => [
            'icon' => '🍱',
            'title' => 'Component-Centric Architecture',
            'content' => 'Move away from traditional MVC. In RapoPHP, every page is a component, and components are just PHP classes.',
            'details' => [
                'JSX-style PHP: Use the h() helper to build declarative UI structures.',
                'Hierarchical Layouts: Layouts nest automatically following the folder structure.',
                'Reusable Components: Build your UI using pure PHP classes that extend the Component class.',
                'Direct Data Handling: Fetch data directly within your components.'
            ]
        ],
        'rapo-live' => [
            'icon' => '⚡',
            'title' => 'Rapo-Live Reactivity',
            'content' => 'Build interactive, real-time user interfaces without writing a single line of JavaScript. Rapo-Live handles everything on the server.',
            'details' => [
                'Server-side State: Manage UI state entirely in PHP.',
                'Automatic DOM Diffing: Only the changed parts of the UI are updated.',
                'Seamless Experience: Feels like a Single Page App (SPA) but powered by the server.',
                'Zero JS overhead: Minimize the client-side bundle size.'
            ]
        ],
        'performance-dx' => [
            'icon' => '🚀',
            'title' => 'Performance & Developer Experience',
            'content' => 'RapoPHP is built with modern performance patterns and a CLI that makes development a breeze.',
            'details' => [
                'Image Optimization: Built-in Image component for lazy-loading and format optimization.',
                'ISR & Caching: Support for Incremental Static Regeneration for lightning-fast loads.',
                'Modern CLI: Scaffold pages, models, and migrations with simple commands.',
                'Data Fetching: Built-in support for getServerSideProps for pre-rendering.'
            ]
        ],
        'modern-data-auth' => [
            'icon' => '💾',
            'title' => 'Modern Data & Authentication',
            'content' => 'A robust backend foundation with a fluent ORM and built-in authentication scaffolding.',
            'details' => [
                'Active Record ORM: Interact with your database using intuitive PHP methods.',
                'Automatic Migrations: Define your schema in models and sync effortlessly.',
                'Auth Scaffolding: Ready-to-use login, registration, and session management.',
                'Query Builder: A powerful and safe way to build complex SQL queries.'
            ]
        ],
        'functional-hooks' => [
            'icon' => '🪝',
            'title' => 'Functional PHP Hooks',
            'content' => 'Manage state, metadata, and routing using a functional approach similar to React Hooks.',
            'details' => [
                'useRouter(): Access routing data, parameters, and navigation features.',
                'useHead(): Declaratively manage title, meta tags, and head elements.',
                'useState(): Persisted component state that survives page reloads.',
                'useForm(): Simplified form handling, data binding, and validation.'
            ]
        ]
    ];

    public function view(): string
    {
        $slug = $this->useRouter()->params['slug'] ?? '';
        $feature = $this->featureData[$slug] ?? null;

        if (!$feature) {
            return h('div', ['class' => 'text-center py-20'], [
                h('h1', ['class' => 'text-4xl font-bold'], '404 - Feature Not Found'),
                component(Link::class, ['href' => '/features', 'class' => 'text-sky-600 underline mt-4 inline-block'], 'Back to features')
            ]);
        }

        return h('div', ['class' => 'max-w-3xl mx-auto py-12'], [
            component(Link::class, ['href' => '/features', 'class' => 'text-slate-400 hover:text-sky-600 transition mb-8 inline-block'], '← Back to Features'),
            h('div', ['class' => 'flex items-center gap-4 mb-6'], [
                h('span', ['class' => 'text-6xl'], $feature['icon']),
                h('h1', ['class' => 'text-5xl font-black text-slate-900'], $feature['title'])
            ]),
            h('div', ['class' => 'p-10 bg-white rounded-3xl border border-slate-100 shadow-sm'], [
                h('p', ['class' => 'text-xl leading-relaxed text-slate-600 mb-10'], $feature['content']),
                
                h('div', ['class' => 'space-y-4'], [
                    h('h2', ['class' => 'text-2xl font-bold text-slate-800 mb-6'], 'Detailed Features:'),
                    h('ul', ['class' => 'space-y-4'], array_map(function($detail) {
                        return h('li', ['class' => 'flex items-start gap-3 text-slate-600'], [
                            h('div', ['class' => 'mt-1.5 w-2 h-2 rounded-full bg-sky-500'], ''),
                            h('span', [], $detail)
                        ]);
                    }, $feature['details'] ?? []))
                ]),

                h('div', ['class' => 'mt-12 p-6 bg-slate-50 rounded-2xl border border-slate-200'], [
                    h('h3', ['class' => 'font-bold mb-4 flex items-center gap-2'], [
                        h('span', ['class' => 'w-2 h-2 bg-sky-500 rounded-full'], ''),
                        'Developer Note'
                    ]),
                    h('p', ['class' => 'text-sm text-slate-500'], "This page is rendered dynamically using the slug: "),
                    h('code', ['class' => 'bg-sky-100 text-sky-700 px-2 py-1 rounded text-xs'], "/features/{$slug}")
                ])
            ])
        ]);
    }
}
