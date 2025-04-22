<?php

$controllerName = $argv[2] ?? null;

if (!$controllerName) {
    echo "❌ Please provide the controller name.\n";
    exit(1);
}

$originalName = ucfirst($controllerName);
$lowerName = strtolower($controllerName);

// Tambahkan suffix jika belum ada
if (!str_ends_with($originalName, 'Controller')) {
    $controllerClass = $originalName . 'Controller';
} else {
    $controllerClass = $originalName;
}

$controllerFile = BASE_PATH . '/app/Controllers/' . $controllerClass . '.php';

if (file_exists($controllerFile)) {
    echo "❌ Controller {$controllerClass} already exists.\n";
    exit(1);
}

$content = <<<PHP
<?php

namespace App\Controllers;

use Velto\Core\Controller;

class {$controllerClass} extends Controller
{
    public function index()
    {
        // return view ('some-view');
    }
}
PHP;

file_put_contents($controllerFile, $content);
echo "✅ Controller {$controllerClass} created at app/Controllers/{$controllerClass}.php\n";
