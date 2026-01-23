<?php

namespace App\Pages;

use App\Components\Navbar;
use Rapo\Component;

class Layout extends Component {
    public function view(): string {
        $navbar = new Navbar();
        $navbarHtml = $navbar->render();
        $head = $this->useStore('head');
        $scripts = \Rapo\renderAssets('js');
        $styles = \Rapo\renderAssets('css');

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$head->getTitle()}</title>
    {$head->renderTags()}
    {$styles}
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@200;800&display=swap" rel="stylesheet">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="bg-gray-50 min-h-screen">
    {$navbarHtml}
    <main class="max-w-4xl mx-auto px-4 py-12">
        {$this->props['children']}
    </main>
    {$scripts}
    <footer class="mt-20 border-t border-gray-100 py-12 text-center text-gray-400">
        &copy; 2026 RapoPHP Framework. Root Layout from Pages folder.
    </footer>
</body>
</html>
HTML;
    }
}
