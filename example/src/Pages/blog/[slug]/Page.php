<?php

namespace App\Pages\blog;

use Rapo\Component;

class Page extends Component
{
    public function view(): string
    {
        // useRouter() now returns an object with 'params'
        $slug = $this->useRouter()->params['slug'] ?? 'Unknown';
        
        return h('article', ['class' => 'max-w-2xl mx-auto py-12'], [
            h('header', ['class' => 'mb-8'], [
                h('div', ['class' => 'text-sky-600 font-bold uppercase tracking-wider text-sm mb-2'], 'Case Study'),
                h('h1', ['class' => 'text-5xl font-black text-slate-900 capitalize'], str_replace('-', ' ', $slug)),
                h('div', ['class' => 'mt-4 text-slate-400'], 'Published on ' . date('M d, Y'))
            ]),
            h('div', ['class' => 'prose prose-slate lg:prose-xl'], [
                h('p', [], "This is a dynamic route example. The slug picked up from the URL is: " . h('span', ['class' => 'font-mono bg-slate-100 px-2 py-1 rounded'], $slug)),
                h('p', [], "In a real application, you would use this slug to fetch data from a database using an ORM Model.")
            ])
        ]);
    }
}
