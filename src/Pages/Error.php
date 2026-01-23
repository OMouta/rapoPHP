<?php

namespace App\Pages;

use Rapo\Component;
use Rapo\Components\Link;

class Error extends Component
{
    public function view(): string
    {
        $code = $this->props['statusCode'] ?? 500;
        $message = $this->props['exception']->getMessage() ?? 'An unexpected error occurred.';

        return h('div', ['class' => 'min-h-[70vh] flex flex-col items-center justify-center text-center px-4'], [
            h('div', ['class' => 'bg-red-50 text-red-600 px-4 py-1 rounded-full text-sm font-bold mb-4 uppercase tracking-widest'], "Error {$code}"),
            h('h1', ['class' => 'text-4xl font-bold text-slate-900 mb-6'], 'Something went wrong'),
            h('p', ['class' => 'text-lg text-slate-500 mb-10 max-w-md'], $message),
            h('div', ['class' => 'flex gap-4'], [
                component(Link::class, [
                    'href' => '/', 
                    'class' => 'px-6 py-3 bg-slate-900 text-white font-bold rounded-xl hover:bg-slate-800 transition'
                ], 'Back Home'),
                h('button', [
                    'onclick' => 'window.location.reload()',
                    'class' => 'px-6 py-3 bg-white border border-slate-200 text-slate-600 font-bold rounded-xl hover:bg-slate-50 transition'
                ], 'Try again')
            ])
        ]);
    }
}
