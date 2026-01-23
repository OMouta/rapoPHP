<?php

namespace App\Pages;

use Rapo\Controller;
use Rapo\Components\Link;
use function Rapo\h;

class Error extends Controller {
    public function index() {
        $exception = $this->props['exception'] ?? null;
        $statusCode = $this->props['statusCode'] ?? 500;
        $message = $statusCode === 404 ? "Oops! We couldn't find that page." : "Something went wrong on our end.";
        
        return h('div', ['class' => 'max-w-xl mx-auto py-20 text-center'],
            h('div', ['class' => 'text-9xl font-black text-gray-100 mb-8'], $statusCode),
            h('h1', ['class' => 'text-4xl font-bold text-gray-900 mb-4'], $message),
            h('p', ['class' => 'text-lg text-gray-500 mb-10'], 'Try going back to home or contact support.'),
            
            $exception && $statusCode !== 404 ? h('pre', ['class' => 'bg-gray-50 p-4 rounded-xl text-left overflow-auto max-w-full inline-block text-xs text-gray-400 mb-8'], $exception->getMessage()) : '',
            
            (new Link(['href' => '/', 'class' => 'inline-block bg-black text-white px-8 py-4 rounded-2xl font-bold hover:bg-gray-800 transition-all shadow-lg', 'children' => 'Back to Safety']))->render()
        );
    }
}
