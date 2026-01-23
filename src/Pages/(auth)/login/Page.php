<?php

namespace App\Pages\login; // Note: (auth) group is flattend in namespace by convention if desired, or we just use the leaf folder name

use Rapo\Component;

class Page extends Component
{
    public function view(): string
    {
        return h('div', ['class' => 'max-w-md mx-auto mt-20 p-8 bg-white rounded-3xl shadow-xl border border-slate-100'], [
            h('div', ['class' => 'text-center mb-8'], [
                h('h2', ['class' => 'text-3xl font-bold'], 'Welcome back'),
                h('p', ['class' => 'text-slate-500'], 'Login to access your dashboard')
            ]),
            h('form', ['class' => 'space-y-4'], [
                h('div', [], [
                    h('label', ['class' => 'block text-sm font-medium mb-1'], 'Email Address'),
                    h('input', ['type' => 'email', 'class' => 'w-full p-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-sky-500 outline-none'])
                ]),
                h('div', [], [
                    h('label', ['class' => 'block text-sm font-medium mb-1'], 'Password'),
                    h('input', ['type' => 'password', 'class' => 'w-full p-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-sky-500 outline-none'])
                ]),
                h('button', ['class' => 'w-full bg-sky-600 text-white p-3 rounded-xl font-bold hover:bg-sky-700 transition'], 'Sign In')
            ]),
            h('div', ['class' => 'mt-6 text-center text-sm text-slate-400'], [
                "This page path is ",
                h('code', [], 'src/Pages/(auth)/login/Page.php'),
                " but the URL is just ",
                h('code', [], '/login'),
                "."
            ])
        ]);
    }
}
