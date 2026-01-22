<?php

require_once __DIR__ . '/rapo/Bootstrap.php';

$app = Rapo\Bootstrap::boot();
$store = Rapo\Store::getDefault();

$command = $argv[1] ?? 'help';

switch ($command) {
    case 'db:init':
        $db = $store->get('db');
        $db->query("CREATE TABLE IF NOT EXISTS notes (id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT, content TEXT, created_at DATETIME DEFAULT CURRENT_TIMESTAMP)");
        echo "Database initialized with 'notes' table.\n";
        break;

    case 'make:component':
        $name = $argv[2] ?? null;
        if (!$name) die("Please provide a component name. Example: make:component MyComponent\n");
        $file = __DIR__ . "/examples/modern-app/src/Components/$name.php";
        $tpl = "<?php\n\nnamespace App\Components;\n\nuse Rapo\Component;\nuse function Rapo\h;\n\nclass $name extends Component {\n    public function view(): string {\n        return h('div', [], 'New Component: $name');\n    }\n}\n";
        file_put_contents($file, $tpl);
        echo "Component created at $file\n";
        break;

    default:
        echo "Available commands:\n";
        echo "  db:init         Initialize the SQlite database\n";
        echo "  make:component  Create a new component structure\n";
        break;
}
