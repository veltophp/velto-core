<?php

use Velto\Core\View;
use Velto\Core\Env;

/**
 * Returns the base path of the project
 * @param string $path Optional subpath to append
 * @return string Full path
 */
function base_path(string $path = ''): string
{
    // Adjust the level based on your project structure
    $base = dirname(__DIR__, 3); // Typically 3-4 levels up from Helpers.php
    return $base . ($path ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : '');
}

/**
 * Returns the view path for a given view file
 * @param string $view The view name (dot notation)
 * @return string The full path to the view
 * @throws RuntimeException If view file not found
 */
function view_path(string $view): string
{
    $viewFile = str_replace('.', DIRECTORY_SEPARATOR, $view) . '.vel.php';
    $path = base_path('views' . DIRECTORY_SEPARATOR . $viewFile);
    
    if (!file_exists($path)) {
        throw new RuntimeException("View [{$view}] not found at: {$path}");
    }
    
    return $path;
}

/**
 * Returns the URL for static assets
 * @param string $path The asset file path
 * @return string The asset URL
 */
function asset(string $path): string
{
    return '/public/' . ltrim($path, '/');
}

/**
 * Returns the route URI for the given URI
 * @param string $uri The URI you want to use
 * @return string The route URI
 */
function route(string $uri): string
{
    return $uri;
}

if (!function_exists('view')) {
    /**
     * Render a view using the View class
     * @param string $view View name (dot notation)
     * @param array $data Data to pass to view
     * @return string Rendered content
     */
    function view(string $view, array $data = []): string
    {
        return View::render($view, $data);
    }
}

/**
 * Redirect to a URL
 * @param string $url The URL to redirect to
 */
function redirect(string $url): void
{
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

    if (Env::isDebug()) {
        $debugView = BASE_PATH . '/views/errors/debug.vel.php';
        if (file_exists($debugView)) {
            include $debugView;
            exit;
        }
    } else {
        $errorView = BASE_PATH . "/views/errors/{$code}.vel.php";
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
 * Dump and die - debug helper
 * @param mixed ...$vars Variables to dump
 */
function dd(...$vars): void
{
    echo '<div style="background:#2d2d30;color:#fff;padding:20px;border-radius:5px;font-family:monospace;">';
    echo '<h4 style="color:#f7c242;">Debug Dump</h4>';
    foreach ($vars as $var) {
        echo '<pre style="background:#1d1d20;padding:10px;border-radius:3px;overflow:auto;">';
        var_dump($var);
        echo '</pre>';
    }
    echo '</div>';
    die();
}

/**
 * Debug dump without dying
 * @param mixed ...$vars Variables to dump
 */
function dump(...$vars): void
{
    echo '<div style="background:#2d2d30;color:#fff;padding:20px;border-radius:5px;font-family:monospace;margin:10px; margin-top:150px;">';
    echo '<h4 style="color:#f7c242;">Debug</h4>';
    foreach ($vars as $var) {
        echo '<pre style="background:#1d1d20;padding:10px;border-radius:3px;overflow:auto;">';
        var_dump($var);
        echo '</pre>';
    }
    echo '</div>';
}

function css_framework(): string
{
    return Env::get('CSS_FRAMEWORK', 'tailwind');
}

function css_link(): string
{
    $framework = css_framework();
    return match ($framework) {
        'bootstrap' => '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-SgOJa3DmI69IUzQ2PVdRZhwQ+dy64/BUtbMJw1MZ8t5HZApcHrRKUc4W0kG879m7" crossorigin="anonymous">',
        'tailwind'  => '<script src="https://cdn.tailwindcss.com"></script>',
        default     => '',
    };
}
