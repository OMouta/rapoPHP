<?php

require_once __DIR__ . '/rapo/Bootstrap.php';

// Project detection
$projectRoot = __DIR__;
$appNamespace = 'App';

// Robust app path detection
$appPath = $projectRoot;
$possibleAppPaths = ['src', 'app', 'lib'];
foreach ($possibleAppPaths as $possible) {
    if (is_dir($projectRoot . '/' . $possible)) {
        $appPath = $projectRoot . '/' . $possible;
        break;
    }
}

// Public directory detection
$publicDir = is_dir($projectRoot . '/public') ? 'public' : '.';

$app = Rapo\Bootstrap::boot($appNamespace, $appPath);
$store = Rapo\Store::getDefault();

$command = $argv[1] ?? 'help';

switch ($command) {
    case 'db:init':
        $db = $store->get('db');
        $modelsDir = $appPath . '/Models';
        if (is_dir($modelsDir)) {
            $files = scandir($modelsDir);
            foreach ($files as $file) {
                if ($file === '.' || $file === '..') continue;
                $className = $appNamespace . '\\Models\\' . pathinfo($file, PATHINFO_FILENAME);
                if (class_exists($className) && is_subclass_of($className, 'Rapo\\Model')) {
                    $table = $className::getTable();
                    $fields = $className::getFields();

                    if ($table && $fields) {
                        $colDefs = [];
                        foreach ($fields as $col => $def) {
                            $colDefs[] = "$col $def";
                        }
                        $sql = "CREATE TABLE IF NOT EXISTS $table (" . implode(', ', $colDefs) . ")";
                        $db->query($sql);
                        echo "Initialized table: $table (from $className)\n";
                    }
                }
            }
        }
        break;

    case 'project:init':
        echo "Initializing new RapoPHP project...\n";
        
        $mode = $argv[2] ?? null;
        if (!$mode) {
            echo "Where should the entry point (index.php) be located? [root/public] (default: root): ";
            $mode = trim(fgets(STDIN)) ?: 'root';
        }
        
        echo "What should the application source directory be named? [src/app] (default: src): ";
        $srcName = trim(fgets(STDIN)) ?: 'src';
        
        // 1. Create directory structure
        $dirs = ["$srcName/Models", "$srcName/Pages", "$srcName/Api", "$srcName/Components", "$srcName/Middleware"];
        if ($mode === 'public') $dirs[] = 'public';

        foreach ($dirs as $dir) {
            if (!is_dir($projectRoot . '/' . $dir)) {
                mkdir($projectRoot . '/' . $dir, 0777, true);
                echo "Created directory: $dir\n";
            }
        }

        // 2. Create composer.json
        if (!file_exists($projectRoot . '/composer.json')) {
            $composer = [
                "name" => "app/rapo-project",
                "require" => [
                    "php" => ">=8.0"
                ],
                "autoload" => [
                    "psr-4" => [
                        "App\\" => "$srcName/"
                    ]
                ]
            ];
            file_put_contents($projectRoot . '/composer.json', json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            echo "Created composer.json\n";
        }

        // 3. Create .htaccess
        $htaccessPath = ($mode === 'public') ? $projectRoot . '/public/.htaccess' : $projectRoot . '/.htaccess';
        if (!file_exists($htaccessPath)) {
            $htaccess = "RewriteEngine On\nRewriteCond %{REQUEST_FILENAME} !-f\nRewriteCond %{REQUEST_FILENAME} !-d\nRewriteRule ^ index.php [L]\n";
            file_put_contents($htaccessPath, $htaccess);
            echo "Created $htaccessPath\n";
        }

        // 4. Create index.php
        $indexPath = ($mode === 'public') ? $projectRoot . '/public/index.php' : $projectRoot . '/index.php';
        if (!file_exists($indexPath)) {
            $relPath = ($mode === 'public') ? '/../' : '/';
            $index = "<?php\n\nrequire_once __DIR__ . '$relPath" . "rapo/Bootstrap.php';\n\n// Boot RapoPHP with App namespace\n\$app = Rapo\\Bootstrap::boot('App', __DIR__ . '$relPath" . "$srcName');\n\$store = Rapo\\Store::getDefault();\n\n// Enable File-based Routing\n\$store->get('router')->enableFileBasedRouting(__DIR__ . '$relPath" . "$srcName/Pages', 'App\\\\Pages');\n\$store->get('router')->enableApiRouting(__DIR__ . '$relPath" . "$srcName/Api', 'App\\\\Api');\n\n\$app->handle();\n";
            file_put_contents($indexPath, $index);
            echo "Created $indexPath\n";
        }

        // 5. Create .env & .gitignore
        if (!file_exists($projectRoot . '/.env')) {
            file_put_contents($projectRoot . '/.env', "APP_NAME=RapoApp\nDB_DRIVER=sqlite\nDB_PATH=database.db\n");
            echo "Created .env\n";
        }
        if (!file_exists($projectRoot . '/.gitignore')) {
            file_put_contents($projectRoot . '/.gitignore', "/vendor\n/.env\n*.db\n*.sqlite\n*.sqlite3\n/cache\n");
            echo "Created .gitignore\n";
        }

        echo "Project initialized successfully with source in '$srcName' and entry point in '$mode' mode!\n";
        break;

    case 'make:model':
        $name = $argv[2] ?? null;
        if (!$name) die("Please provide a model name. Example: make:model Product\n");
        $file = $appPath . "/Models/$name.php";
        if (file_exists($file)) die("Model $name already exists.\n");
        if (!is_dir(dirname($file))) mkdir(dirname($file), 0777, true);
        
        $table = strtolower($name) . 's';
        $tpl = "<?php\n\nnamespace $appNamespace\\Models;\n\nuse Rapo\Model;\n\nclass $name extends Model {\n    protected static \$table = '$table';\n    protected static \$fields = [\n        'id' => 'INTEGER PRIMARY KEY AUTOINCREMENT',\n        'title' => 'TEXT',\n        'created_at' => 'DATETIME DEFAULT CURRENT_TIMESTAMP'\n    ];\n}\n";
        file_put_contents($file, $tpl);
        echo "Model created at $file\n";
        break;

    case 'scaffold':
        $name = $argv[2] ?? null;
        if (!$name) die("Please provide a name for scaffolding. Example: scaffold Post\n");
        
        // Ensure project is initialized
        if (!file_exists($projectRoot . '/composer.json')) {
            echo "Project not initialized. Running project:init first...\n";
            passthru("php rapo.php project:init");
        }

        // 1. Create Model
        $modelName = ucfirst($name);
        passthru("php rapo.php make:model $modelName");

        // 2. Create API
        $apiName = $modelName . "s";
        passthru("php rapo.php make:api $apiName");

        // 3. Create Pages
        passthru("php rapo.php make:page $modelName/Index");
        passthru("php rapo.php make:page $modelName/Create");

        echo "Scaffolding completed for $modelName!\n";
        break;

    case 'serve':
        $port = $argv[2] ?? 8000;
        echo "Starting RapoPHP development server on http://localhost:$port\n";
        passthru("php -S localhost:$port -t $publicDir");
        break;

    case 'make:component':
        $name = $argv[2] ?? null;
        if (!$name) die("Please provide a component name. Example: make:component MyComponent\n");
        $file = $appPath . "/Components/$name.php";
        if (!is_dir(dirname($file))) mkdir(dirname($file), 0777, true);
        $tpl = "<?php\n\nnamespace $appNamespace\\Components;\n\nuse Rapo\\Component;\nuse function Rapo\\h;\n\nclass $name extends Component {\n    public function view(): string {\n        return h('div', [], 'New Component: $name');\n    }\n}\n";
        file_put_contents($file, $tpl);
        echo "Component created at $file\n";
        break;

    case 'make:page':
        $name = $argv[2] ?? null;
        if (!$name) die("Please provide a page name. Example: make:page Settings\n");
        $file = $appPath . "/Pages/$name.php";
        if (!is_dir(dirname($file))) mkdir(dirname($file), 0777, true);
        
        $pathParts = explode('/', str_replace('\\', '/', $name));
        $className = array_pop($pathParts);
        $subNamespace = !empty($pathParts) ? '\\' . implode('\\', $pathParts) : '';

        $tpl = "<?php\n\nnamespace $appNamespace\\Pages$subNamespace;\n\nuse Rapo\\Controller;\nuse function Rapo\\h;\n\nclass $className extends Controller {\n    public function index() {\n        return h('div', ['class' => 'p-6'], [\n            h('h1', ['class' => 'text-2xl font-bold'], 'Page: $className'),\n            h('p', ['class' => 'mt-2 text-gray-600'], 'This is a new page generated by RapoCLI.')\n        ]);\n    }\n}\n";
        file_put_contents($file, $tpl);
        echo "Page created at $file (URL: /" . strtolower(str_replace(['\\', '/'], '/', $name)) . ")\n";
        break;

    case 'make:layout':
        $name = $argv[2] ?? 'Layout';
        $path = $argv[3] ?? ''; // Optional subfolder
        $folder = "/Pages/" . trim($path, '/');
        $file = $appPath . "$folder/$name.php";
        if (!is_dir(dirname($file))) mkdir(dirname($file), 0777, true);
        $namespace = "$appNamespace\\Pages" . ($path ? "\\" . str_replace('/', '\\', trim($path, '/')) : "");
        $tpl = "<?php\n\nnamespace $namespace;\n\nuse Rapo\Component;\nuse function Rapo\h;\n\nclass $name extends Component {\n    public function view(): string {\n        return h('div', ['class' => 'layout'], \$this->props['children']);\n    }\n}\n";
        file_put_contents($file, $tpl);
        echo "Layout created at $file\n";
        break;

    case 'make:api':
        $name = $argv[2] ?? null;
        if (!$name) die("Please provide an API name. Example: make:api User\n");
        $file = $appPath . "/Api/$name.php";
        if (!is_dir(dirname($file))) mkdir(dirname($file), 0777, true);

        $pathParts = explode('/', str_replace('\\', '/', $name));
        $className = array_pop($pathParts);
        $subNamespace = !empty($pathParts) ? '\\' . implode('\\', $pathParts) : '';

        $tpl = "<?php\n\nnamespace $appNamespace\\Api$subNamespace;\n\nuse Rapo\\Controller;\n\nclass $className extends Controller {\n    public function index() {\n        return ['status' => 'success', 'message' => 'API $className active', 'timestamp' => time()];\n    }\n}\n";
        file_put_contents($file, $tpl);
        echo "API Route created at $file (URL: /api/" . strtolower(str_replace(['\\', '/'], '/', $name)) . ")\n";
        break;

    case 'make:middleware':
        $name = $argv[2] ?? null;
        if (!$name) die("Please provide a name. Example: make:middleware Auth\n");
        $file = $appPath . "/Middleware/$name.php";
        if (!is_dir(dirname($file))) mkdir(dirname($file), 0777, true);
        $tpl = "<?php\n\nnamespace $appNamespace\\Middleware;\n\nclass $name {\n    public function handle(\$request, \$response) {\n        // Logic here\n        return true; // continue\n    }\n}\n";
        file_put_contents($file, $tpl);
        echo "Middleware created at $file\n";
        break;

    default:
        echo "Available commands:\n";
        echo "  project:init [root|public]  Initialize project (default: root for Apache)\n";
        echo "  db:init                     Initialize the database from models\n";
        echo "  scaffold [name]    Create model, api, and pages for a resource\n";
        echo "  serve [port]       Start a local development server\n";
        echo "  make:model         Create a new model\n";
        echo "  make:component     Create a new component structure\n";
        echo "  make:page          Create a new file-based route page\n";
        echo "  make:layout        Create a root or nested layout\n";
        echo "  make:api           Create a new API route\n";
        echo "  make:middleware    Create a new middleware class\n";
        break;
}
