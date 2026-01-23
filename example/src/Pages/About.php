<?php

namespace App\Pages;

use Rapo\Controller;
use Rapo\Components\Link;
use App\Components\ContactForm;
use function Rapo\h;
use function Rapo\env;

class About extends Controller {
    public function index() {
        return h('div', ['class' => 'max-w-2xl mx-auto text-center'],
            h('h1', ['class' => 'text-4xl font-black text-gray-900 mb-6'], 'About ' . env('APP_NAME', 'RapoPHP')),
            h('p', ['class' => 'text-lg text-gray-600 mb-8'], 'RapoPHP was born from the desire to bring the best ideas from JavaScript frameworks back to the simplicity of PHP.'),
            
            h('div', ['class' => 'p-8 bg-blue-50 rounded-2xl border-2 border-blue-100 mb-12'],
                h('p', ['class' => 'text-blue-800 font-bold italic'], '"PHP is still cool and good."')
            ),

            // Use the real-time hydrated component
            new ContactForm(),

            h('div', ['class' => 'mt-12'],
                 (new Link(['href' => '/', 'class' => 'bg-gray-900 text-white px-8 py-3 rounded-xl font-bold', 'children' => 'Back Home']))->render()
            )
        );
    }
}
