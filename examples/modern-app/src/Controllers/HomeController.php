<?php

namespace App\Controllers;

use Rapo\Controller;
use App\Components\Layout;
use App\Components\Welcome;
use App\Components\Counter;
use App\Components\TodoList;
use App\Components\Notes;
use function Rapo\h;

class HomeController extends Controller {
    public function index() {
        $name = $this->request->getQuery('name', 'Friend');
        
        // Use the components themselves, h() will call __toString() automatically
        $content = h('div', ['class' => 'space-y-8 max-w-3xl mx-auto'],
            new Welcome(['name' => $name]),
            h('div', ['class' => 'grid grid-cols-1 md:grid-cols-2 gap-8'],
                new Counter(),
                new TodoList()
            ),
            new Notes()
        );
        
        $layout = new Layout([
            'title' => 'Stateful RapoPHP',
            'children' => $content
        ]);

        return $layout->render();
    }

    public function user($id) {
        return "User Profile for ID: " . htmlspecialchars($id);
    }
}
