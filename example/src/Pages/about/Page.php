<?php

namespace App\Pages\about;

use Rapo\Component;
use Rapo\Components\Link;

class Page extends Component
{
    public function view(): string
    {
        return h('div', ['class' => 'max-w-2xl mx-auto space-y-6'], [
            h('h1', ['class' => 'text-4xl font-bold'], 'About RapoPHP'),
            h('p', ['class' => 'text-lg text-slate-600 leading-relaxed'], "
                RapoPHP was built to bridge the gap between the simplicity of PHP 
                and the modern development experience of frameworks like Next.js.
            "),
            h('div', ['class' => 'bg-sky-50 p-6 rounded-xl border border-sky-100 italic'], "
                'Folders are the source of truth for your application architecture.'
            "),
            component(Link::class, ['href' => '/', 'class' => 'text-sky-600 font-semibold'], '← Back to Home')
        ]);
    }
}
