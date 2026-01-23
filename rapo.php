<?php

require_once __DIR__ . '/rapo/Bootstrap.php';

$app = Rapo\Bootstrap::boot();
$store = Rapo\Store::getDefault();

$command = $argv[1] ?? 'help';

switch ($command) {
    case 'db:init':
        $db = $store->get('db');
        $db->query("CREATE TABLE IF NOT EXISTS notes (id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT, content TEXT, created_at DATETIME DEFAULT CURRENT_TIMESTAMP)");
        $db->query("CREATE TABLE IF NOT EXISTS tasks (id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT, status TEXT, created_at DATETIME DEFAULT CURRENT_TIMESTAMP)");
        echo "Database initialized with 'notes' and 'tasks' tables.\n";
        break;

    case 'make:component':
        $name = $argv[2] ?? null;
        if (!$name) die("Please provide a component name. Example: make:component MyComponent\n");
        $file = __DIR__ . "/example/src/Components/$name.php";
        if (!is_dir(dirname($file))) mkdir(dirname($file), 0777, true);
        $tpl = "<?php\n\nnamespace App\Components;\n\nuse Rapo\Component;\nuse function Rapo\h;\n\nclass $name extends Component {\n    public function view(): string {\n        return h('div', [], 'New Component: $name');\n    }\n}\n";
        file_put_contents($file, $tpl);
        echo "Component created at $file\n";
        break;

    case 'make:page':
        $name = $argv[2] ?? null;
        if (!$name) die("Please provide a page name. Example: make:page Settings\n");
        $file = __DIR__ . "/example/src/Pages/$name.php";
        if (!is_dir(dirname($file))) mkdir(dirname($file), 0777, true);
        $tpl = "<?php\n\nnamespace App\Pages;\n\nuse Rapo\Controller;\nuse function Rapo\h;\n\nclass $name extends Controller {\n    public function index() {\n        return h('div', [], 'New Page: $name');\n    }\n}\n";
        file_put_contents($file, $tpl);
        echo "Page created at $file (URL: /" . strtolower($name) . ")\n";
        break;

    case 'make:layout':
        $name = $argv[2] ?? 'Layout';
        $path = $argv[3] ?? ''; // Optional subfolder
        $folder = "/example/src/Pages/" . trim($path, '/');
        $file = __DIR__ . "$folder/$name.php";
        if (!is_dir(dirname($file))) mkdir(dirname($file), 0777, true);
        $namespace = "App\\Pages" . ($path ? "\\" . str_replace('/', '\\', trim($path, '/')) : "");
        $tpl = "<?php\n\nnamespace $namespace;\n\nuse Rapo\Component;\nuse function Rapo\h;\n\nclass $name extends Component {\n    public function view(): string {\n        return h('div', ['class' => 'layout'], \$this->props['children']);\n    }\n}\n";
        file_put_contents($file, $tpl);
        echo "Layout created at $file\n";
        break;

    case 'make:api':
        $name = $argv[2] ?? null;
        if (!$name) die("Please provide an API name. Example: make:api User\n");
        $file = __DIR__ . "/example/src/Api/$name.php";
        if (!is_dir(dirname($file))) mkdir(dirname($file), 0777, true);
        $tpl = "<?php\n\nnamespace App\Api;\n\nuse Rapo\Controller;\n\nclass $name extends Controller {\n    public function index() {\n        return ['status' => 'success', 'message' => 'API $name active'];\n    }\n}\n";
        file_put_contents($file, $tpl);
        echo "API Route created at $file (URL: /api/" . strtolower($name) . ")\n";
        break;

    case 'make:middleware':
        $name = $argv[2] ?? null;
        if (!$name) die("Please provide a name. Example: make:middleware Auth\n");
        $file = __DIR__ . "/example/src/Middleware/$name.php";
        if (!is_dir(dirname($file))) mkdir(dirname($file), 0777, true);
        $tpl = "<?php\n\nnamespace App\Middleware;\n\nclass $name {\n    public function handle(\$request, \$response) {\n        // Logic here\n        return true; // continue\n    }\n}\n";
        file_put_contents($file, $tpl);
        echo "Middleware created at $file\n";
        break;

    default:
        echo "Available commands:\n";
        echo "  db:init            Initialize the SQlite database\n";
        echo "  make:component     Create a new component structure\n";
        echo "  make:page          Create a new file-based route page\n";
        echo "  make:layout        Create a root or nested layout\n";
        echo "  make:api           Create a new API route\n";
        echo "  make:middleware    Create a new middleware class\n";
        break;
}
