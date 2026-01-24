#!/usr/bin/env php
<?php

// Project detection
$projectRoot = getcwd();
$appNamespace = 'App';

// Try to find the framework
$hasFramework = false;

// 1. Try local folder (for monorepo development)
if (file_exists($projectRoot . '/rapo/Bootstrap.php')) {
    require_once $projectRoot . '/rapo/Bootstrap.php';
    $hasFramework = true;
    $appPath = is_dir($projectRoot . '/example/src') ? $projectRoot . '/example/src' : $projectRoot . '/src';
} 
// 2. Try vendor (for installed projects)
elseif (file_exists($projectRoot . '/vendor/autoload.php')) {
    require_once $projectRoot . '/vendor/autoload.php';
    $hasFramework = class_exists('\\Rapo\\Bootstrap');
    $appPath = $projectRoot . '/src';
}

if ($hasFramework) {
    $app = Rapo\Bootstrap::boot($appNamespace, $appPath);
    $store = Rapo\Store::getDefault();
}

// Current CLI runner for internal calls
$cli = PHP_BINARY . ' ' . escapeshellarg(realpath(__FILE__));

$command = $argv[1] ?? 'help';

if (!$hasFramework && !in_array($command, ['help', 'project:init'])) {
    die("RapoPHP Framework not found. Run 'php rapo.php project:init' to initialize a new project.\n");
}

switch ($command) {
    case 'serve':
        $port = $argv[2] ?? 8000;
        echo "Rapo Runtime starting on http://localhost:$port\n";
        $index = is_dir($projectRoot . '/example') ? 'example/index.php' : 'index.php';
        passthru("php -S localhost:$port $index");
        break;
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
                    "php" => ">=8.0",
                    "rapo/framework" => "^1.0"
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
            $index = "<?php\n\nif (file_exists(__DIR__ . '$relPath" . "vendor/autoload.php')) {\n    require_once __DIR__ . '$relPath" . "vendor/autoload.php';\n} else {\n    require_once __DIR__ . '$relPath" . "rapo/Bootstrap.php';\n}\n\n// Boot RapoPHP with App namespace\n// It will automatically detect Pages/ and Api/ folders in the source directory\n\$app = Rapo\\Bootstrap::boot('App', __DIR__ . '$relPath" . "$srcName');\n\n\$app->handle();\n";
            file_put_contents($indexPath, $index);
            echo "Created $indexPath\n";
        }

        // 5. Create .env & .gitignore
        if (!file_exists($projectRoot . '/.env')) {
            $env = "APP_NAME=RapoApp\nAPP_ENV=local\nDEBUG=true\n\nDB_DRIVER=sqlite\nDB_DATABASE=database.sqlite\n";
            file_put_contents($projectRoot . '/.env', $env);
            echo "Created .env\n";
        }
        if (!file_exists($projectRoot . '/.gitignore')) {
            file_put_contents($projectRoot . '/.gitignore', "/vendor\n/.env\n*.db\n*.sqlite\n*.sqlite3\n/cache\n");
            echo "Created .gitignore\n";
        }

        // 6. Create default Layout and Page
        if (!file_exists("$projectRoot/$srcName/Pages/Layout.php")) {
            $layout = "<?php\n\nnamespace App\\Pages;\n\nuse Rapo\\Component;\nuse function Rapo\\h;\n\nclass Layout extends Component {\n    public function view(): string {\n        return h('html', ['lang' => 'en'], [\n            h('head', [], [\n                h('title', [], 'My Rapo App'),\n                h('script', ['src' => 'https://cdn.tailwindcss.com'], '')\n            ]),\n            h('body', ['class' => 'bg-gray-50'], \$this->children)\n        ]);\n    }\n}\n";
            file_put_contents("$projectRoot/$srcName/Pages/Layout.php", $layout);
            echo "Created $srcName/Pages/Layout.php\n";
        }

        if (!file_exists("$projectRoot/$srcName/Pages/Page.php")) {
            $page = "<?php\n\nnamespace App\\Pages;\n\nuse Rapo\\Component;\nuse function Rapo\\h;\n\nclass Page extends Component {\n    public function view(): string {\n        return h('div', ['class' => 'min-h-screen flex items-center justify-center'], [\n            h('div', ['class' => 'text-center'], [\n                h('h1', ['class' => 'text-5xl font-extrabold text-blue-600'], 'RapoPHP'),\n                h('p', ['class' => 'mt-4 text-xl text-gray-600'], 'Welcome to your modern PHP application.')\n            ])\n        ]);\n    }\n}\n";
            file_put_contents("$projectRoot/$srcName/Pages/Page.php", $page);
            echo "Created $srcName/Pages/Page.php\n";
        }

        // 7. Shortcut creation
        $isVendor = strpos(realpath(__FILE__), 'vendor') !== false;
        if ($isVendor && !file_exists($projectRoot . '/rapo') && !file_exists($projectRoot . '/rapo.bat')) {
            echo "\nWould you like to create a shortcut 'rapo' in your project root? [y/n]: ";
            $createShortcut = strtolower(trim(fgets(STDIN))) === 'y';
            if ($createShortcut) {
                if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                    file_put_contents($projectRoot . '/rapo.bat', "@php \"%~dp0vendor/bin/rapo\" %*");
                    echo "Created rapo.bat\n";
                }
                
                // Always create the bash version too for Git Bash/Linux/macOS users
                file_put_contents($projectRoot . '/rapo', "#!/usr/bin/env php\n<?php require __DIR__ . '/vendor/bin/rapo';");
                if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
                    chmod($projectRoot . '/rapo', 0755);
                }
                echo "Created rapo shortcut\n";
            }
        }

        echo "\nProject initialized successfully!\n";
        if (file_exists($projectRoot . '/rapo.bat') || file_exists($projectRoot . '/rapo')) {
            echo "Run 'php rapo serve' to start development.\n";
        } else {
            echo "Run 'php vendor/bin/rapo serve' to start development.\n";
        }
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
            passthru("$cli project:init");
        }

        // 1. Create Model
        $modelName = ucfirst($name);
        passthru("$cli make:model $modelName");

        // 2. Create API
        $apiName = $modelName . "s";
        passthru("$cli make:api $apiName");

        // 3. Create Pages
        passthru("$cli make:page $modelName/Index");
        passthru("$cli make:page $modelName/Create");

        echo "Scaffolding completed for $modelName!\n";
        break;

    case 'add:tailwind':
        echo "Adding Tailwind CSS support...\n";
        
        $tailwindConfig = "/** @type {import('tailwindcss').Config} */\nmodule.exports = {\n  content: [\n    './$srcName/**/*.php',\n    './rapo/**/*.php',\n    './*.php',\n  ],\n  theme: {\n    extend: {},\n  },\n  plugins: [],\n}\n";
        file_put_contents($projectRoot . '/tailwind.config.js', $tailwindConfig);
        
        $postcssConfig = "module.exports = {\n  plugins: {\n    tailwindcss: {},\n    autoprefixer: {},\n  },\n}\n";
        file_put_contents($projectRoot . '/postcss.config.js', $postcssConfig);
        
        $cssFile = $appPath . "/Styles/app.css";
        if (!is_dir(dirname($cssFile))) mkdir(dirname($cssFile), 0777, true);
        file_put_contents($cssFile, "@tailwind base;\n@tailwind components;\n@tailwind utilities;\n");
        
        echo "Tailwind CSS configured. You can now use 'npx tailwindcss -i ./$srcName/Styles/app.css -o ./public/app.css --watch' to build your styles.\n";
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
        if (!$name) die("Please provide a path. Examples: make:page blog/[slug] or make:page dashboard\n");
        
        $path = trim($name, '/ ');
        $dir = $appPath . "/Pages/" . $path;
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        
        $file = $dir . "/Page.php";
        if (file_exists($file)) die("Page already exists at $file\n");

        $pathParts = explode('/', $path);
        // Find if any part is dynamic or group
        $namespaceParts = array_map(function($p) {
            if (preg_match('/^\[(.+)\]$/', $p)) return 'Dynamic' . ucfirst(trim($p, '[]'));
            if (preg_match('/^\((.+)\)$/', $p)) return $p;
            return ucfirst($p);
        }, $pathParts);

        $subNamespace = !empty($namespaceParts) ? '\\' . implode('\\', $namespaceParts) : '';

        $tpl = "<?php\n\nnamespace $appNamespace\\Pages$subNamespace;\n\nuse Rapo\\Component;\nuse function Rapo\\h;\n\nclass Page extends Component {\n    public function getServerSideProps(\$request, \$params) {\n        return [\n            'title' => 'Rapo Page',\n            'params' => \$params\n        ];\n    }\n\n    public function view(): string {\n        return h('div', ['class' => 'p-8'], [\n            h('h1', ['class' => 'text-3xl font-bold'], 'Page: $path'),\n            h('p', ['class' => 'mt-4'], 'Welcome to your new modern Rapo page.')\n        ]);\n    }\n}\n";
        file_put_contents($file, $tpl);
        echo "Page created at $file\n";
        break;

    case 'make:layout':
        $path = $argv[2] ?? '';
        $dir = $appPath . "/Pages/" . trim($path, '/');
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        
        $file = $dir . "/Layout.php";
        if (file_exists($file)) die("Layout already exists at $file\n");

        $pathParts = array_filter(explode('/', trim($path, '/')));
        $namespaceParts = array_map(function($p) {
            if (preg_match('/^\[(.+)\]$/', $p)) return 'Dynamic' . ucfirst(trim($p, '[]'));
            return ucfirst($p);
        }, $pathParts);

        $subNamespace = !empty($namespaceParts) ? '\\' . implode('\\', $namespaceParts) : '';

        $tpl = "<?php\n\nnamespace $appNamespace\\Pages$subNamespace;\n\nuse Rapo\\Component;\nuse function Rapo\\h;\n\nclass Layout extends Component {\n    public function view(): string {\n        return h('div', ['class' => 'layout-container'], [\n            h('header', ['class' => 'p-4 bg-gray-100'], 'Rapo Header'),\n            h('main', [], \$this->props['children']),\n            h('footer', ['class' => 'p-4 border-t'], 'Rapo Footer')\n        ]);\n    }\n}\n";
        file_put_contents($file, $tpl);
        echo "Layout created at $file\n";
        break;

    case 'make:api':
        $path = $argv[2] ?? null;
        if (!$path) die("Please provide an API path. Example: make:api users\n");
        
        $dir = $appPath . "/Api/" . trim($path, '/');
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        
        $file = $dir . "/Route.php";
        if (file_exists($file)) die("API Route already exists at $file\n");

        $pathParts = array_filter(explode('/', trim($path, '/')));
        $namespaceParts = array_map(function($p) {
            if (preg_match('/^\[(.+)\]$/', $p)) return 'Dynamic' . ucfirst(trim($p, '[]'));
            return ucfirst($p);
        }, $pathParts);

        $subNamespace = !empty($namespaceParts) ? '\\' . implode('\\', $namespaceParts) : '';

        $tpl = "<?php\n\nnamespace $appNamespace\\Api$subNamespace;\n\nuse Rapo\\Controller;\n\nclass Route extends Controller {\n    public function index() {\n        return ['status' => 'success', 'path' => '$path'];\n    }\n}\n";
        file_put_contents($file, $tpl);
        echo "API Route created at $file\n";
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

    case 'make:migration':
        $name = $argv[2] ?? null;
        if (!$name) die("Please provide a name. Example: make:migration CreateUsersTable\n");
        $migrationsDir = $appPath . "/Migrations";
        if (!is_dir($migrationsDir)) mkdir($migrationsDir, 0777, true);
        
        $timestamp = date('Y_m_d_His');
        $fileName = "{$timestamp}_{$name}.php";
        $file = "$migrationsDir/$fileName";
        
        $tpl = "<?php\n\nnamespace $appNamespace\\Migrations;\n\nuse Rapo\\Migration;\n\nclass $name extends Migration {\n    public function up() {\n        // \$this->query(\"CREATE TABLE ...\");\n    }\n\n    public function down() {\n        // \$this->query(\"DROP TABLE ...\");\n    }\n}\n";
        file_put_contents($file, $tpl);
        echo "Migration created at $file\n";
        break;

    case 'make:auth':
        echo "Scaffolding Authentication...\n";
        
        // 1. Create User Model
        $userModelFile = $appPath . "/Models/User.php";
        if (!file_exists($userModelFile)) {
            $userTpl = "<?php\n\nnamespace $appNamespace\\Models;\n\nuse Rapo\\Model;\n\nclass User extends Model {\n    protected static \$table = 'users';\n    protected static \$fields = [\n        'id' => 'INTEGER PRIMARY KEY AUTOINCREMENT',\n        'name' => 'TEXT',\n        'email' => 'TEXT UNIQUE',\n        'password' => 'TEXT',\n        'created_at' => 'DATETIME DEFAULT CURRENT_TIMESTAMP'\n    ];\n}\n";
            file_put_contents($userModelFile, $userTpl);
            echo "Created User Model\n";
        }

        // 2. Create Migration
        $timestamp = date('Y_m_d_His');
        $migFile = $appPath . "/Migrations/{$timestamp}_CreateUsersTable.php";
        if (!is_dir(dirname($migFile))) mkdir(dirname($migFile), 0777, true);
        $migTpl = "<?php\n\nnamespace $appNamespace\\Migrations;\n\nuse Rapo\\Migration;\n\nclass CreateUsersTable extends Migration {\n    public function up() {\n        \$this->query(\"CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, email TEXT UNIQUE, password TEXT, created_at DATETIME DEFAULT CURRENT_TIMESTAMP)\");\n    }\n\n    public function down() {\n        \$this->query(\"DROP TABLE users\");\n    }\n}\n";
        file_put_contents($migFile, $migTpl);
        echo "Created migration: $migFile\n";

        // 3. Create Login Page (Simplified)
        $loginFile = $appPath . "/Pages/Login.php";
        $loginTpl = "<?php\n\nnamespace $appNamespace\\Pages;\n\nuse Rapo\\Controller;\nuse Rapo\\Auth;\nuse function Rapo\\h;\nuse function Rapo\\csrf_field;\n\nclass Login extends Controller {\n    public function index() {\n        return h('div', ['class' => 'p-6'], [\n            h('h2', ['class' => 'text-xl mb-4'], 'Login'),\n            h('form', ['method' => 'POST'], [\n                csrf_field(),\n                h('input', ['name' => 'email', 'placeholder' => 'Email', 'class' => 'block border mb-2']),\n                h('input', ['name' => 'password', 'type' => 'password', 'placeholder' => 'Password', 'class' => 'block border mb-2']),\n                h('button', ['type' => 'submit'], 'Login')\n            ])\n        ]);\n    }\n\n    public function post() {\n        \$request = \Rapo\Store::getDefault()->get('request');\n        \$data = \$request->getPost();\n        if (Auth::attempt(\$data['email'], \$data['password'])) {\n            header('Location: /');\n            exit;\n        }\n        return 'Login failed';\n    }\n}\n";
        file_put_contents($loginFile, $loginTpl);
        echo "Created Login Page at $loginFile\n";
        
        echo "Auth scaffolding complete. Run 'php rapo.php migrate' to update the database.\n";
        break;

    case 'migrate':
        $db = $store->get('db');
        $migrationsDir = $appPath . "/Migrations";
        
        // 1. Ensure migrations table exists
        $db->query("CREATE TABLE IF NOT EXISTS migrations (id INTEGER PRIMARY KEY AUTOINCREMENT, migration TEXT, batch INTEGER)");

        // 2. Get ran migrations
        $ran = $db->fetchAll("SELECT migration FROM migrations");
        $ranNames = array_column($ran, 'migration');

        // 3. Scan Migration files
        if (!is_dir($migrationsDir)) die("No migrations directory found.\n");
        $files = scandir($migrationsDir);
        $pending = [];
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            if (!in_array($file, $ranNames)) {
                $pending[] = $file;
            }
        }

        if (empty($pending)) {
            echo "Nothing to migrate.\n";
            break;
        }

        sort($pending); // Ensure chronological order

        // 4. Run pending migrations
        $batch = (int)$db->fetch("SELECT MAX(batch) as max_batch FROM migrations")['max_batch'] + 1;
        foreach ($pending as $file) {
            require_once $migrationsDir . '/' . $file;
            // Extract class name from filename: 2023_01_01_000000_CreateUsersTable.php -> CreateUsersTable
            $className = substr(pathinfo($file, PATHINFO_FILENAME), 18);
            $fullClassName = "$appNamespace\\Migrations\\$className";
            
            if (class_exists($fullClassName)) {
                $migration = new $fullClassName();
                echo "Migrating: $file... ";
                $migration->up();
                $db->query("INSERT INTO migrations (migration, batch) VALUES (?, ?)", [$file, $batch]);
                echo "Done.\n";
            } else {
                echo "Class $fullClassName not found in $file\n";
            }
        }
        break;

    case 'migrate:rollback':
        $db = $store->get('db');
        $migrationsDir = $appPath . "/Migrations";

        // 1. Find latest batch
        $latestBatchResults = $db->fetch("SELECT MAX(batch) as max_batch FROM migrations");
        $batch = $latestBatchResults ? (int)$latestBatchResults['max_batch'] : 0;

        if ($batch === 0) {
            echo "Nothing to rollback.\n";
            break;
        }

        // 2. Get migrations in latest batch (in reverse order)
        $migrations = $db->fetchAll("SELECT migration FROM migrations WHERE batch = ? ORDER BY id DESC", [$batch]);

        foreach ($migrations as $m) {
            $file = $m['migration'];
            $filePath = $migrationsDir . '/' . $file;
            if (file_exists($filePath)) {
                require_once $filePath;
                $className = substr(pathinfo($file, PATHINFO_FILENAME), 18);
                $fullClassName = "$appNamespace\\Migrations\\$className";
                
                if (class_exists($fullClassName)) {
                    $migration = new $fullClassName();
                    echo "Rolling back: $file... ";
                    $migration->down();
                    $db->query("DELETE FROM migrations WHERE migration = ?", [$file]);
                    echo "Done.\n";
                }
            } else {
                echo "Migration file not found: $file\n";
            }
        }
        break;

    default:
        echo "RapoPHP Framework CLI\n\n";
        echo "Usage: php rapo.php [command] [args...]\n\n";
        echo "Available commands:\n";
        echo "  serve [port]       Start a local development server (default: 8000)\n";
        echo "  project:init       Initialize project structure in current directory\n";
        echo "  db:init            Initialize the database from models\n";
        echo "  migrate            Run pending migrations\n";
        echo "  migrate:rollback   Rollback the last migration batch\n";
        echo "  scaffold [name]    Create model, api, and pages for a resource\n";
        echo "  make:model         Create a new model\n";
        echo "  make:migration     Create a new migration file\n";
        echo "  make:auth          Scaffold authentication (user, login, signup)\n";
        echo "  add:tailwind       Initialize Tailwind CSS configuration\n";
        echo "  make:component     Create a new component structure\n";
        echo "  make:page          Create a new file-based route page\n";
        echo "  make:layout        Create a root or nested layout\n";
        echo "  make:api           Create a new API route\n";
        echo "  make:middleware    Create a new middleware class\n\n";

        $isVendor = strpos(realpath(__FILE__), 'vendor') !== false;
        if ($isVendor && !file_exists($projectRoot . '/rapo') && !file_exists($projectRoot . '/rapo.bat')) {
            echo "Tip: Run 'php vendor/bin/rapo project:init' to create a root 'rapo' shortcut.\n";
        }
        break;
}
