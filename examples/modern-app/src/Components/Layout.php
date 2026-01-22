<?php

namespace App\Components;

use Rapo\Component;

class Layout extends Component {
    public function view(): string {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <title>{$this->props['title']}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
    <header class="bg-blue-600 text-white p-4 shadow-lg">
        <h1 class="text-2xl font-bold">RapoPHP Modern App</h1>
    </header>
    <main class="flex-grow container mx-auto p-8">
        {$this->props['children']}
    </main>
    <footer class="bg-gray-800 text-white p-4 text-center">
        Powered by RapoPHP (Cool & Good)
    </footer>
</body>
</html>
HTML;
    }
}
