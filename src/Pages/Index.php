<?php

namespace App\Pages;

use Rapo\Controller;
use Rapo\Components\Link;
use App\Components\TaskBoard;
use function Rapo\h;
use function Rapo\env;

class Index extends Controller {
    public function login() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $_SESSION['is_logged_in'] = true;
    }

    public function logout() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        unset($_SESSION['is_logged_in']);
    }

    public static function getMetadata($request, $params) {
        return [
            'title' => 'Home | ' . env('APP_NAME', 'RapoPHP'),
            'description' => 'A modern PHP framework inspired by Next.js'
        ];
    }

    public function index() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $isLoggedIn = isset($_SESSION['is_logged_in']);

        return h('div', ['class' => 'space-y-12'],
            h('header', ['class' => 'text-center py-12'],
                h('h1', ['class' => 'text-6xl font-black text-gray-900 mb-4 tracking-tight'], 'RapoPHP Framework'),
                h('p', ['class' => 'text-2xl text-gray-500 mt-2 max-w-2xl mx-auto'], 'The Next.js experience, built entirely in pure PHP.'),
                
                h('div', ['class' => 'mt-10 flex justify-center gap-4'],
                    (new Link(['href' => '/dashboard', 'class' => 'bg-blue-600 text-white px-8 py-3 rounded-2xl font-bold shadow-lg shadow-blue-200 hover:bg-blue-700 transition-all', 'children' => 'Enter Dashboard']))->render(),
                    (new Link(['href' => '/about', 'class' => 'bg-white text-gray-900 border-2 border-gray-100 px-8 py-3 rounded-2xl font-bold hover:border-gray-900 transition-all', 'children' => 'Learn More']))->render()
                ),

                // Auth Demo
                h('div', ['class' => 'mt-12 flex justify-center items-center gap-4'],
                    h('form', ['method' => 'POST'], 
                        $isLoggedIn ? \Rapo\formAction('logout') : \Rapo\formAction('login'),
                        h('button', ['type' => 'submit', 'class' => 'text-sm font-medium text-gray-400 hover:text-blue-600 border-b border-transparent hover:border-blue-600 transition-all'], 
                            $isLoggedIn ? 'Logout Session' : 'Login for Admin Access (Mock)'
                        )
                    ),
                    $isLoggedIn ? (new Link(['href' => '/admin/secret', 'class' => 'text-sm font-bold text-red-600 bg-red-50 px-3 py-1 rounded-full', 'children' => '→ Secret Admin Link']))->render() : ''
                )
            ),
            h('div', ['class' => 'grid grid-cols-1 md:grid-cols-2 gap-12'],
                h('div', ['class' => 'space-y-6'],
                    h('h3', ['class' => 'text-2xl font-bold text-gray-800'], 'Why RapoPHP?'),
                    h('ul', ['class' => 'space-y-4'],
                        $this->feature('React-like Components', 'Build UI logic entirely in PHP.'),
                        $this->feature('Live Interactions', 'Handle clicks and inputs without custom JS.'),
                        $this->feature('Stateful Hooks', 'Persist data in sessions with simple hooks.'),
                        $this->feature('File-based Routing', 'Next.js style routing for your pages.')
                    )
                ),
                new TaskBoard()
            )
        );
    }

    private function feature($title, $desc) {
        return h('li', ['class' => 'flex gap-4'],
            h('span', ['class' => 'text-blue-600 text-2xl font-bold'], '→'),
            h('div', [], 
                h('h4', ['class' => 'font-bold text-gray-800'], $title),
                h('p', ['class' => 'text-gray-500'], $desc)
            )
        );
    }
}
