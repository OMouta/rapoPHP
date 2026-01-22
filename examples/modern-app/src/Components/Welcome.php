<?php

namespace App\Components;

use Rapo\Component;

class Welcome extends Component {
    public function view(): string {
        $name = $this->props['name'] ?? 'Visitor';
        return <<<HTML
<div class="bg-white p-6 rounded-xl shadow-md">
    <h2 class="text-3xl font-bold text-gray-800 mb-4">Welcome, {$name}!</h2>
    <p class="text-gray-600 mb-4">You are viewing a component-based PHP page. It feels a bit like React, but it's all PHP.</p>
    <div class="flex gap-4">
        <a href="?name=Raphael" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition">Say hi to Raphael</a>
        <a href="?name=Developer" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 transition">Say hi to Developer</a>
    </div>
</div>
HTML;
    }
}
