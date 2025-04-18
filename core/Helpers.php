<?php

use Velto\Core\View;

/**
 * Returns the base path of the project
 * @param string $path Optional subpath to append
 * @return string Full path
 */
function base_path($path = '')
{
    // Ensures the path is relative to the project root directory
    return __DIR__ . '/../' . ($path ? '/' . ltrim($path, '/') : '');
}

/**
 * Returns the view path for a given view file
 * @param string $view The view name
 * @return string The full path to the view
 */
function view_path($view)
{
    // Path to the views folder, with the view name as parameter
    return base_path("views/{$view}.vel.php");
}

/**
 * Returns the URL for static assets like images, css, js
 * @param string $path The asset file path
 * @return string The asset URL
 */
function asset($path)
{
    // Returns the asset URL with the '/public' prefix
    return '/public/' . ltrim($path, '/');
}

/**
 * Returns the route URI for the given URI
 * @param string $uri The URI you want to use
 * @return string The route URI
 */
function route($uri)
{
    // Can be extended to resolve route names
    return $uri;
}


if (!function_exists('view')) {
    function view(string $view, array $data = []): void
    {
        echo View::render($view, $data);
    }
}

function redirect($url) {
    header("Location: " . filter_var($url, FILTER_SANITIZE_URL));
    exit();
}

/**
 * Handles errors and displays error pages
 * @param int $code Error code (default: 500)
 * @param string $message Error message (default: 'Server Error')
 */
function abort(int $code = 500, string $message = 'Server Error')
{
    if (php_sapi_name() === 'cli') {
        echo "❌ {$code} - {$message}\n";
        exit(1);
    }

    http_response_code($code);

    if (Velto\Core\Env::isDebug()) {
        $debugView = base_path('views/errors/debug.vel.php');
        if (file_exists($debugView)) {
            include $debugView;
            exit;
        }
    } else {
        $errorView = base_path('views/errors/500.vel.php');
        if (file_exists($errorView)) {
            include $errorView;
        } else {
            echo "<h1>$code | Server Error</h1>";
            echo "<p>$message</p>";
        }
        exit;
    }
}

/**
 * Dumps the variable(s) and halts execution
 * @param mixed ...$vars The variables to be dumped
 */
function dd(...$vars)
{
    echo '<div style="background-color:#2d2d30; color:white; font-family:monospace; padding:20px; border-radius:8px;">';
    echo '<h4 style="color:#f7c242;">Dumping Variable(s)</h4>';
    
    foreach ($vars as $var) {
        echo '<pre style="background-color:#1d1d20; padding:10px; border-radius:8px; font-size:12px;">';
        var_dump($var);
        echo '</pre>';
    }

    // Halt execution after dumping the variables
    die();
}


function compile_view($viewPath) {
    $content = file_get_contents($viewPath);
    
    // Convert @include to Template::include
    $content = preg_replace('/@include\(\'(.*?)\'\)/', '<?php Template::include(\'$1\'); ?>', $content);
    
    // Convert @component to Template::component
    $content = preg_replace('/@component\(\'(.*?)\'(?:,\s*(.*?))?\)/', '<?php Template::component(\'$1\', $2 ?? []); ?>', $content);
    
    // Convert @yield
    $content = preg_replace('/@yield\(\'(.*?)\'(?:,\s*\'(.*?)\')?\)/', '<?php Template::yield(\'$1\', \'$2\' ?? \'\'); ?>', $content);
    
    // Convert @section/@endsection
    $content = preg_replace('/@section\(\'(.*?)\'\)/', '<?php Template::section(\'$1\'); ?>', $content);
    $content = str_replace('@endsection', '<?php Template::endSection(); ?>', $content);
    
    // Convert @extends
    $content = preg_replace('/@extends\(\'(.*?)\'\)/', '<?php Template::extends(\'$1\'); ?>', $content);
    
    $compiledPath = "cache/views/".md5($viewPath).".php";
    file_put_contents($compiledPath, $content);
    return $compiledPath;
}


