<?php

/**
 * Global helper functions for the VeltoPHP framework and Axion package.
 *
 * This file defines various utility functions that can be used throughout the application
 * to perform common tasks such as path generation, view rendering, asset handling,
 * error reporting, debugging, request management, validation, session manipulation,
 * routing, CSRF protection, flash messaging, authentication, and more.
 */

use Velto\Core\View;
use Velto\Core\Env;
use Velto\Core\Request;
use Velto\Core\Route;
use Velto\Core\Mail;
use Velto\Axion\Session;
use League\CommonMark\CommonMarkConverter;

if (!function_exists('markdown')) {
    function markdown(string $text): string
    {
        $converter = new CommonMarkConverter([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);

        return $converter->convert($text)->getContent();
    }
}
if (!function_exists('base_path')) {
    function base_path(string $path = ''): string
    {
        $base = dirname(__DIR__, 3);
        return $base . ($path ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : '');
    }
}
if (!function_exists('real_path')) {
    function real_path($path = '')
    {
        $cleanPath = ltrim($path, '/');

        $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/' . $cleanPath;

        return realpath($fullPath);
    }
}
if (!function_exists('root_path')) {
    function root_path(string $path = ''): string
    {

        $root = dirname(__DIR__, 4);
        return rtrim($root, DIRECTORY_SEPARATOR) . ($path ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : '');
    }
}
if (!function_exists('view_path')) {
    function view_path(string $view): string
    {
        $viewFile = str_replace('.', DIRECTORY_SEPARATOR, $view) . '.vel.php';
        $path = base_path('views' . DIRECTORY_SEPARATOR . $viewFile);
        
        if (!file_exists($path)) {
            throw new RuntimeException("View [{$view}] not found at: {$path}");
        }
        
        return $path;
    }
}
if (!function_exists('asset')) {
    function asset(string $path): string
    {
        return '/public/' . ltrim($path, '/');
    }
}
if (!function_exists('view')) {
    function view(string $view, array $data = []): string
    {
        return View::render($view, $data);
    }
}
if (!function_exists('abort')) {
    function abort(int $code = 500, string $message = 'Server Error'): void
    {
        if (php_sapi_name() === 'cli') {
            echo "❌ {$code} - {$message}\n";
            exit(1);
        }

        http_response_code($code);

        if (Env::isDebug()) {

            $debugView = BASE_PATH . '/vendor/veltophp/velto-core/core/Errors/debug.vel.php';

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
}
if (!function_exists('dd')) {
    function dd(...$vars): never
    {
        echo '<div style="
            background: #1a1a1a;
            color: #e2e2e2;
            padding: 25px;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            margin: 0;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 999999;
            overflow-y: auto;
        ">';
        
        echo '<div style="
            max-width: 1200px;
            margin: 0 auto;
        ">';
        
        // Header
        echo '<div style="
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #333;
        ">';
        echo '<div style="display: flex; align-items: center;">';
        echo '<svg style="width: 24px; height: 24px; margin-right: 10px; color: #fb503b;" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
              </svg>';
        echo '<h1 style="
            color: #fb503b;
            font-size: 24px;
            font-weight: 600;
            margin: 0;
        ">Debug</h1>';
        echo '</div>';
        echo '<div style="color: #888; font-size: 14px;">VeltoPHP</div>';
        echo '</div>';
        
        // Variables
        foreach ($vars as $i => $var) {
            echo '<div style="margin-bottom: 30px;">';
            echo '<div style="
                color: #b3b3b3;
                font-size: 14px;
                margin-bottom: 10px;
                display: flex;
                align-items: center;
            ">';
            echo '<svg style="width: 16px; height: 16px; margin-right: 8px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                  </svg>';
            echo 'Variable #' . ($i + 1);
            echo '</div>';
            
            echo '<pre style="
                background: #252525;
                padding: 15px;
                border-radius: 5px;
                overflow-x: auto;
                margin: 0;
                font-size: 14px;
                line-height: 1.5;
                border-left: 3px solid #fb503b;
                color: #e2e2e2;
            ">';
            echo htmlspecialchars(var_export($var, true));
            echo '</pre>';
            echo '</div>';
        }
        
        // Footer
        echo '<div style="
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #333;
            color: #888;
            font-size: 13px;
            display: flex;
            justify-content: space-between;
        ">';
        echo '<div>';
        echo '<span style="color: #fb503b;">Script terminated</span> by dd() call';
        echo '</div>';
        echo '<div>';
        echo 'Called from: ' . debug_backtrace()[0]['file'] . ' on line ' . debug_backtrace()[0]['line'];
        echo '</div>';
        echo '</div>';
        
        echo '</div>'; // Close max-width container
        echo '</div>'; // Close main container
        die();
    }
}
if (!function_exists('dump')) {
    function dump(...$vars): void
    {
        echo '<div style="
            background: #f8f9fa;
            color: #212529;
            padding: 20px;
            border-radius: 4px;
            font-family: Consolas, Monaco, \'Andale Mono\', monospace;
            margin: 20px;
            border: 1px solid #dee2e6;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            font-size: 14px;
            line-height: 1.5;
        ">';
        
        echo '<h4 style="
            color: #495057;
            font-size: 16px;
            margin-top: 0;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 1px solid #e9ecef;
        ">Debug Output</h4>';
        
        foreach ($vars as $i => $var) {
            echo '<pre style="
                background: white;
                padding: 12px;
                border-radius: 3px;
                overflow-x: auto;
                margin: 0 0 15px 0;
                border: 1px solid #e9ecef;
                white-space: pre-wrap;
            ">';
            echo htmlspecialchars(print_r($var, true));
            echo '</pre>';
        }
        
        echo '<div style="
            color: #6c757d;
            font-size: 12px;
            font-family: sans-serif;
        ">';
        echo 'Called from: ' . debug_backtrace()[0]['file'] . ' on line ' . debug_backtrace()[0]['line'];
        echo '</div>';
        echo '</div>';
    }
}
if (!function_exists('request')) {
    function request(): Request
    {
        static $request;

        if (!$request) {
            $request = new Request();
        }

        return $request;
    }
}
if (!function_exists('validate')) {
    function validate(array $data, array $rules): ?array
    {
        $errors = [];

        foreach ($rules as $field => $rule) {
            $rulesArr = explode('|', $rule);
            $value = $data[$field] ?? null;

            foreach ($rulesArr as $ruleItem) {
                [$ruleName, $param] = array_pad(explode(':', $ruleItem, 2), 2, null);

                switch ($ruleName) {
                    case 'required':
                        if ($value === null || $value === '' || (is_array($value) && empty($value))) {
                            $errors[$field][] = "$field is required.";
                        }
                        break;

                    case 'string':
                        if (!is_string($value)) {
                            $errors[$field][] = "$field must be a string.";
                        }
                        break;

                    case 'integer':
                        if (!filter_var($value, FILTER_VALIDATE_INT)) {
                            $errors[$field][] = "$field must be an integer.";
                        }
                        break;

                    case 'boolean':
                        if (!is_bool($value) && !in_array($value, ['true', 'false', 0, 1, '0', '1'], true)) {
                            $errors[$field][] = "$field must be a boolean.";
                        }
                        break;

                    case 'array':
                        if (!is_array($value)) {
                            $errors[$field][] = "$field must be an array.";
                        }
                        break;

                    case 'min':
                        if (is_string($value) && strlen($value) < (int)$param) {
                            $errors[$field][] = "$field must be at least $param characters.";
                        }
                        if (is_numeric($value) && $value < (int)$param) {
                            $errors[$field][] = "$field must be at least $param.";
                        }
                        break;

                    case 'max':
                        if (is_string($value) && strlen($value) > (int)$param) {
                            $errors[$field][] = "$field must not exceed $param characters.";
                        }
                        if (is_numeric($value) && $value > (int)$param) {
                            $errors[$field][] = "$field must not exceed $param.";
                        }
                        break;

                    case 'email':
                        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $errors[$field][] = "$field must be a valid email address.";
                        }
                        break;

                    case 'numeric':
                        if (!is_numeric($value)) {
                            $errors[$field][] = "$field must be numeric.";
                        }
                        break;

                    case 'confirmed':
                        if (($data[$field . '_confirmation'] ?? null) !== $value) {
                            $errors[$field][] = "$field confirmation does not match.";
                        }
                        break;

                    case 'same':
                        if (($data[$param] ?? null) !== $value) {
                            $errors[$field][] = "$field must be the same as $param.";
                        }
                        break;

                    case 'in':
                        $options = explode(',', $param);
                        if (!in_array($value, $options, true)) {
                            $errors[$field][] = "$field must be one of the following values: " . implode(', ', $options) . '.';
                        }
                        break;
                        
                    case 'image':
                        if (!is_array($value) || !isset($value['tmp_name']) || !is_uploaded_file($value['tmp_name'])) {
                            $errors[$field][] = "$field must be a valid uploaded image file.";
                        } else {
                            $allowedMime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                            $mimeType = mime_content_type($value['tmp_name'] ?? '');
                            if (!in_array($mimeType, $allowedMime)) {
                                $errors[$field][] = "$field must be an image (jpeg, png, gif, webp).";
                            }
                        }
                        break;

                    default:
                        break;
                }
            }
        }

        return !empty($errors) ? $errors : null;
    }
}
if (!function_exists('redirect')) {

    function redirect(string $to = '/', array $data = [])
    {
        Session::start();

        if (!empty($data)) {
            $_SESSION['_flash'] = $data;
        }

        return new class($to) {
            protected $to;

            public function __construct($to)
            {
                $this->to = $to;
            }

            public function to($to = null)
            {
                if ($to !== null) {
                    $this->to = $to;
                }
                header('Location: ' . $this->to);
                exit;
            }

            public function back()
            {
                $referer = $_SERVER['HTTP_REFERER'] ?? null;

                if (!$referer || filter_var($referer, FILTER_VALIDATE_URL) === false) {
                    $referer = '/';
                }

                header('Location: ' . $referer);
                exit;
            }

            public function route($name, $params = [])
            {
                $this->to = route($name, $params);
                header('Location: ' . $this->to);
                exit;
            }

            public function __destruct()
            {
                header('Location: ' . $this->to);
                exit;
            }
        };
    }
}
if (!function_exists('route')) {
    function route(string $name, array $params = []): string
    {
        $routes = Route::getRouteNames();

        if (!isset($routes[$name])) {
            throw new RuntimeException("Please check your Route or Link. Route with name [$name] not found.");
        }

        $uri = $routes[$name];

        // Cek apakah params associative atau indexed
        if (array_keys($params) === range(0, count($params) - 1)) {
            // Indexed array, ganti parameter berdasarkan urutan placeholder di URI
            preg_match_all('/\{(\w+)\}/', $uri, $matches);
            $placeholders = $matches[1];

            foreach ($params as $index => $value) {
                if (isset($placeholders[$index])) {
                    $uri = str_replace("{" . $placeholders[$index] . "}", urlencode($value), $uri);
                }
            }
        } else {
            // Associative array, ganti berdasarkan key
            foreach ($params as $key => $value) {
                $uri = str_replace("{" . $key . "}", urlencode($value), $uri);
            }
        }

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';

        $baseUrl = $scheme . '://' . $host;

        return rtrim($baseUrl, '/') . '/' . ltrim($uri, '/');
    }
}
if (!function_exists('to_route')) {
    function to_route(string $name, array $params = [], array $data = []): void
    {
        Session::start();

        if (!empty($data)) {
            $_SESSION['_flash'] = $data;
        }

        // Gunakan fungsi `route()` kamu yang sudah ada
        $url = route($name, $params);

        header('Location: ' . $url);
        exit;
    }
}
if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        Session::start(); 

        if (empty($_SESSION['_token'])) {
            $_SESSION['_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_token'];
    }
}
if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="_token" value="' . csrf_token() . '">';
    }
}
if (!function_exists('csrf_verify')) {
    function csrf_verify(string $token): bool
    {
        Session::start();

        return isset($_SESSION['_token']) && hash_equals($_SESSION['_token'], $token);
    }
}
if (!function_exists('active')) {
    function active(string|array $path, string $activeClass = 'active'): string
    {
        $currentPath = $_SERVER['REQUEST_URI'];

        if (is_array($path)) {
            foreach ($path as $p) {
                if ($currentPath === $p || (trim($p, '/') !== '' && str_starts_with($currentPath, trim($p, '/')))) {
                    return $activeClass;
                }
            }
        } else {
            if ($currentPath === $path || (trim($path, '/') !== '' && str_starts_with($currentPath, trim($path, '/')))) {
                return $activeClass;
            }
        }

        return '';
    }
}
if (!function_exists('with')) {
    function with(array $data): void
    {
        Session::start();

        foreach ($data as $key => $value) {
            $_SESSION[$key] = $value;
        }
    }
}
if (!function_exists('bcrypt')) {
    /**
     * Hash a password using BCRYPT.
     *
     * @param string $value
     * @return string
     */
    function bcrypt(string $value): string
    {
        return password_hash($value, PASSWORD_BCRYPT);
    }
}
if (!function_exists('hash_check')) {
    /**
     * Verify a plain password against a hash.
     *
     * @param string $plain
     * @param string $hashed
     * @return bool
     */
    function hash_check(string $plain, string $hashed): bool
    {
        return password_verify($plain, $hashed);
    }
}
if (!function_exists('now')) {
    function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
if (!function_exists('velto_log')) {
    function velto_log(string $message, string $filename = 'velto.log'): void
    {
        $logDir = BASE_PATH . '/storage/log';
        $logFile = $logDir . '/' . $filename;

        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }

        $formatted = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;

        file_put_contents($logFile, $formatted, FILE_APPEND);
    }
}
if (!function_exists('flash')) {
    function flash(): object
    {
        Session::start();

        return new class {
            public function error(string|array $message): void
            {
                foreach ($this->flatten($message) as $msg) {
                    $_SESSION['flash_messages']['error'][] = $msg;
                }
            }
        
            public function success(string|array $message): void
            {
                foreach ($this->flatten($message) as $msg) {
                    $_SESSION['flash_messages']['success'][] = $msg;
                }
            }
        
            private function flatten(array|string $messages): array
            {
                $result = [];
                foreach ((array) $messages as $item) {
                    if (is_array($item)) {
                        $result = array_merge($result, $this->flatten($item));
                    } else {
                        $result[] = (string) $item;
                    }
                }
                return $result;
            }
        
            public function get(): array
            {
                return $_SESSION['flash_messages'] ?? [];
            }
        
            public function clear(): void
            {
                unset($_SESSION['flash_messages']);
            }

            public function display(): string
            {
                if (isset($_COOKIE['velto_flash_shown'])) {
                    unset($_SESSION['flash_messages']);
                    return '';
                }

                $messages = $this->get();
                if (empty($messages)) return '';

                setcookie('velto_flash_shown', '1', time() + 10, '/');

                $output = '<div class="velto-flash-container space-y-2">';
                foreach ($messages as $type => $msgs) {
                    foreach ($msgs as $msg) {
                        $bgColor = $type === 'error' ? 'bg-red-100 border-red-400 text-red-700' : 'bg-blue-100 border-blue-400 text-blue-700';
                        $output .= '<div class="velto-flash-message border px-4 py-3 rounded relative '.$bgColor.'" data-type="'.$type.'">'.$msg.'</div>';
                    }
                }
                $output .= '</div>';

                $output .= '
                <script>
                    (function() {
                        var container = document.querySelector(".velto-flash-container");
                        if (container) {
                            setTimeout(function() {
                                container.style.transition = "opacity 0.5s";
                                container.style.opacity = "0";
                                setTimeout(function() {
                                    container.remove();

                                    // Hapus cookie supaya tidak tersimpan di browser
                                    document.cookie = "velto_flash_shown=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/";
                                }, 500);
                            }, 5000);
                        }
                    })();
                </script>';

                $this->clear();

                return $output;
            }

        };
    }
}
if (!function_exists('flash_erros')) {
    function flash_erros(): string
    {
        if (function_exists('render_flash')) {
            $legacyOutput = render_flash();
            if (!empty($legacyOutput)) {
                return $legacyOutput . flash()->display();
            }
        }
        return flash()->display();
    }
}
if (!function_exists('render_flash')) {
    function render_flash(): string
    {
        return flash()->display();
    }
}
if (!function_exists('is_email_verification_enabled')) {
    function is_email_verification_enabled(): bool
    {
        return Env::get('EMAIL_VERIFICATION') === 'true';
    }
}
if (!function_exists('send_verification_code')) {
    function send_verification_code(string $email): void
    {
        $code = random_int(10000, 99999);

        Session::set("verification_code.{$email}", $code);

        Mail::send(
            $email,
            'Your Verification Code',
            'axion::verification-code',
            ['code' => $code]
        );

    }
}
if (!function_exists('session')) {

    class SessionHelper
    {
        public function __construct()
        {
            Session::start();
        }

        public function __get(string $key)
        {
            return Session::get($key);
        }

        public function __set(string $key, $value)
        {
            Session::set($key, $value);
        }

        public function has(string $key): bool
        {
            return Session::has($key);
        }

        public function forget(string $key): void
        {
            Session::forget($key);
        }

        public function flush(): void
        {
            Session::flush();
        }

        public function all(): array
        {
            return Session::all();
        }
    }

    function session(): SessionHelper
    {
        static $instance = null;

        if ($instance === null) {
            $instance = new SessionHelper();
        }

        return $instance;
    }
}
if (!function_exists('isMaintenance')) {
    function isMaintenance(): bool
    {
        return file_exists(BASE_PATH . '/.maintenance');
    }
}
if (!class_exists('ImageUploader')) {
    class ImageUploader
    {
        protected $file;       // array file upload
        protected $directory;  // target directory

        public function __construct($fileOrKey, $defaultDir = 'assets/images')
        {
            if (is_string($fileOrKey)) {
                $file = request()->file($fileOrKey);
                $this->file = $file ?: null;
            } elseif (is_array($fileOrKey)) {
                $this->file = $fileOrKey;
            } else {
                $this->file = null;
            }

            $this->directory = rtrim($defaultDir, '/');
        }

        public function dir(string $dir)
        {
            // Pastikan folder tetap di dalam 'assets/images'
            $dir = ltrim($dir, '/');
            if (!str_starts_with($dir, 'assets/images')) {
                $dir = 'assets/images/' . $dir;
            }
            $this->directory = rtrim($dir, '/');
            return $this;
        }

        public function save()
        {
            $file = $this->file;

            if (
                !$file ||
                !isset($file['tmp_name'], $file['name']) ||
                !file_exists($file['tmp_name']) ||
                !is_uploaded_file($file['tmp_name']) ||
                $file['error'] !== 0
            ) {
                return null;
            }

            if (!is_dir($this->directory)) {
                mkdir($this->directory, 0755, true);
            }

            $filename = uniqid() . '_' . preg_replace('/\s+/', '_', basename($file['name']));
            $targetPath = $this->directory . '/' . $filename;

            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                return $targetPath;
            }

            return null;
        }

        public function __toString()
        {
            return $this->save() ?: '';
        }
    }
}
if (!function_exists('storeImage')) {
    function storeImage($fileOrKey = null)
    {
        if ($fileOrKey === null) {
            // Jika tidak ada parameter, return null langsung atau handle sesuai kebutuhan
            return null;
        }

        return new ImageUploader($fileOrKey);
    }
}
if (!function_exists('wysiwyg')) {

    function wysiwyg(string $selector = 'editor', string $textareaName = 'content', ?string $content = '')
    {
        return <<<HTML
        <div class="wysiwyg-editor-wrapper mt-2" data-editor-id="{$selector}">
            <!-- Toolbar with more options -->
            <div class="flex flex-wrap items-center gap-1 bg-gray-100 dark:bg-gray-800 p-2 rounded-t-lg border-b border-gray-300 dark:border-gray-600">
                <!-- Text formatting -->
                <div class="flex items-center gap-1 mr-2">
                    <button type="button" class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-700 transition" data-command="bold" title="Bold (Ctrl+B)">
                        <span class="font-semibold text-xl">B</span>
                    </button>
                    <button type="button" class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-700 transition" data-command="italic" title="Italic (Ctrl+I)">
                        <span class="font-semibold text-xl italic">I</span>
                    </button>
                    <button type="button" class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-700 transition" data-command="underline" title="Underline (Ctrl+U)">
                        <span class="font-semibold text-xl underline">U</span>
                    </button>
                    <button type="button" class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-700 transition" data-command="strikeThrough" title="Strikethrough">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14" />
                        </svg>
                    </button>
                </div>

                <div class="flex flex-wrap items-center gap-1 bg-gray-100 dark:bg-gray-800 p-2 rounded-t-lg border-b border-gray-300 dark:border-gray-600">
                    <!-- Format selector -->
                    <select class="format-selector bg-gray-200 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-800 dark:text-gray-200 rounded px-2 py-1 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500">
                        <option value="p" class="text-base">Paragraph</option>
                        <option value="h1" class="text-4xl font-bold">Heading 1</option>
                        <option value="h2" class="text-3xl font-bold">Heading 2</option>
                        <option value="h3" class="text-2xl font-bold">Heading 3</option>
                        <option value="h4" class="text-xl font-bold">Heading 4</option>
                        <option value="h5" class="text-lg font-bold">Heading 5</option>
                        <option value="h6" class="text-base font-bold">Heading 6</option>
                    </select>
                </div>

                <!-- Text alignment -->
                <div class="flex items-center gap-1 mr-2 border-l border-gray-300 dark:border-gray-600 pl-2">
                    <button type="button" class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-700 transition" data-command="justifyLeft" title="Align left">
                        <i class="fa fa-align-left" aria-hidden="true"></i>
                    </button>
                    <button type="button" class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-700 transition" data-command="justifyCenter" title="Align center">
                        <i class="fa fa-align-center" aria-hidden="true"></i>
                    </button>
                    <button type="button" class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-700 transition" data-command="justifyRight" title="Align right">
                        <i class="fa fa-align-right" aria-hidden="true"></i>
                    </button>
                </div>

                <!-- Lists and indentation -->
                <div class="flex items-center gap-1 mr-2 border-l border-gray-300 dark:border-gray-600 pl-2">
                    <button type="button" class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-700 transition" data-command="indent" title="Indent">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </button>
                    <button type="button" class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-700 transition" data-command="outdent" title="Outdent">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </button>
                </div>

                <!-- Links and images -->
                <div class="flex items-center gap-1 mr-2 border-l border-gray-300 dark:border-gray-600 pl-2">
                    <button type="button" class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-700 transition" data-command="insertImage" title="Insert image">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </button>
                    <button type="button" class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-700 transition" data-command="createLink" title="Insert link">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                        </svg>
                    </button>
                </div>

                <!-- Text color and highlight -->
                <div class="flex items-center gap-1 mr-2 border-l border-gray-300 dark:border-gray-600 pl-2">
                    <div class="relative group">
                        <button type="button" class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-700 transition flex items-center" title="Text color">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
                            </svg>
                        </button>
                        <div class="absolute z-10 hidden group-hover:block bg-white dark:bg-gray-800 shadow-lg rounded-md p-2 w-40">
                            <div class="grid grid-cols-5 gap-1">
                                <button type="button" class="w-6 h-6 rounded-full bg-black" data-command="foreColor" data-value="black" title="Black"></button>
                                <button type="button" class="w-6 h-6 rounded-full bg-white border border-gray-300" data-command="foreColor" data-value="white" title="White"></button>
                                <button type="button" class="w-6 h-6 rounded-full bg-red-500" data-command="foreColor" data-value="red" title="Red"></button>
                                <button type="button" class="w-6 h-6 rounded-full bg-blue-500" data-command="foreColor" data-value="blue" title="Blue"></button>
                                <button type="button" class="w-6 h-6 rounded-full bg-green-500" data-command="foreColor" data-value="green" title="Green"></button>
                                <button type="button" class="w-6 h-6 rounded-full bg-yellow-500" data-command="foreColor" data-value="yellow" title="Yellow"></button>
                                <button type="button" class="w-6 h-6 rounded-full bg-purple-500" data-command="foreColor" data-value="purple" title="Purple"></button>
                                <button type="button" class="w-6 h-6 rounded-full bg-pink-500" data-command="foreColor" data-value="pink" title="Pink"></button>
                                <button type="button" class="w-6 h-6 rounded-full bg-orange-500" data-command="foreColor" data-value="orange" title="Orange"></button>
                                <button type="button" class="w-6 h-6 rounded-full bg-gray-500" data-command="foreColor" data-value="gray" title="Gray"></button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Clear formatting -->
                <button type="button" class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-700 transition ml-auto" data-command="removeFormat" title="Clear formatting">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </button>
            </div>

            <!-- Editable area with placeholder -->
            <div id="{$selector}-editable" contenteditable="true" class="min-h-[20rem] p-4 rounded-b-lg border border-t-0 border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-black dark:text-white focus:outline-none focus:ring-1 focus:ring-blue-500 wysiwyg-content">
                {$content}
            </div>

            <!-- Status bar -->
            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1 flex justify-between items-center">
                <div class="editor-status">Velto WYSIWYG basic text editor.</div>
            </div>

            <!-- Hidden textarea to hold result -->
            <textarea id="{$selector}-textarea" name="{$textareaName}" class="hidden">{$content}</textarea>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const editor = document.getElementById('{$selector}-editable');
                const textarea = document.getElementById('{$selector}-textarea');
                const wrapper = document.querySelector(`[data-editor-id="{$selector}"]`);
                const statusBar = wrapper.querySelector('.editor-status');
                const formatSelector = wrapper.querySelector('.format-selector');

                // Initialize content
                if (textarea && textarea.value.trim()) {
                    editor.innerHTML = textarea.value;
                } else {
                    editor.innerHTML = '<p><br></p>'; // Ensure empty paragraph for better editing
                }

                // Update textarea on changes
                function updateTextarea() {
                    textarea.value = editor.innerHTML;
                    updateActiveButtons();
                    updateStatus();
                }

                // Update button states
                function updateActiveButtons() {
                    wrapper.querySelectorAll('[data-command]').forEach(button => {
                        const command = button.getAttribute('data-command');
                        const isActive = document.queryCommandState(command);
                        button.classList.toggle('bg-gray-300', isActive);
                        button.classList.toggle('dark:bg-gray-600', isActive);
                    });
                }

                // Format selector change event
                formatSelector.addEventListener('change', function() {
                    const format = formatSelector.value;
                    let className = '';
                    switch (format) {
                        case 'h1':
                            className = 'text-4xl font-bold';
                            break;
                        case 'h2':
                            className = 'text-3xl font-bold';
                            break;
                        case 'h3':
                            className = 'text-2xl font-bold';
                            break;
                        case 'h4':
                            className = 'text-xl font-bold';
                            break;
                        case 'h5':
                            className = 'text-lg font-bold';
                            break;
                        case 'h6':
                            className = 'text-base font-bold';
                            break;
                    }
                    document.execCommand('formatBlock', false, format);
                    const selectedElement = window.getSelection().anchorNode.parentNode;
                    if (selectedElement.tagName.toLowerCase() === format) {
                        selectedElement.className = className;
                    }
                    updateTextarea();
                });

                // Event listeners
                editor.addEventListener('input', updateTextarea);
                editor.addEventListener('keyup', updateTextarea);

                // Paste event
                editor.addEventListener('paste', function(e) {
                    e.preventDefault();
                    const clipboardData = e.clipboardData || window.clipboardData;
                    const htmlData = clipboardData.getData('text/html');

                    if (htmlData) {
                        // Sisipkan HTML langsung jika tersedia
                        document.execCommand('insertHTML', false, htmlData);
                    } else {
                        // Jika tidak ada HTML, sisipkan teks biasa
                        const textData = clipboardData.getData('text/plain');
                        document.execCommand('insertText', false, textData);
                    }
                    updateTextarea();
                });

                // Toolbar button actions
                wrapper.querySelectorAll('[data-command]').forEach(button => {
                    button.addEventListener('click', function(e) {
                        e.preventDefault();
                        const command = this.getAttribute('data-command');
                        const value = this.getAttribute('data-value');

                        if (command === 'createLink') {
                            const url = prompt('Enter the URL:', 'https://');
                            if (url) document.execCommand(command, false, url);
                        } else if (command === 'insertImage') {
                            const url = prompt('Enter the image URL:', 'https://');
                            if (url) document.execCommand(command, false, url);
                        } else {
                            document.execCommand(command, false, value || null);
                        }

                        editor.focus();
                        updateTextarea();
                    });
                });

                // Keyboard shortcuts
                editor.addEventListener('keydown', function(e) {
                    // Bold - Ctrl+B
                    if (e.ctrlKey && e.key === 'b') {
                        e.preventDefault();
                        document.execCommand('bold', false, null);
                        updateTextarea();
                    }

                    // Italic - Ctrl+I
                    if (e.ctrlKey && e.key === 'i') {
                        e.preventDefault();
                        document.execCommand('italic', false, null);
                        updateTextarea();
                    }

                    // Underline - Ctrl+U
                    if (e.ctrlKey && e.key === 'u') {
                        e.preventDefault();
                        document.execCommand('underline', false, null);
                        updateTextarea();
                    }
                });

                // Initial updates
                updateActiveButtons();
                updateStatus();

                // Prevent default button behavior
                wrapper.querySelectorAll('button').forEach(button => {
                    button.addEventListener('mousedown', function(e) {
                        e.preventDefault();
                    });
                });
            });
        </script>
HTML;
    }
}
if (!function_exists('fileInput')) {
    function fileInput(string $selector = 'input[type=file]')
    {
        $escapedSelector = addslashes($selector);
        return <<<HTML
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    document.querySelectorAll('$escapedSelector').forEach(function(input) {
                        if(input.dataset.enhanced === 'true') return;
                        input.dataset.enhanced = 'true';

                        // Hide the default input visually but keep accessible
                        input.classList.add('absolute', '-left-[9999px]');

                        // Create wrapper div
                        const wrapper = document.createElement('div');
                        wrapper.className = 'group relative border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-6 text-center cursor-pointer transition-colors duration-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700';

                        // Create label content
                        const labelContent = document.createElement('div');
                        labelContent.className = 'flex flex-col items-center justify-center space-y-2';
                        
                        // SVG icon
                        const svgIcon = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
                        svgIcon.setAttribute('class', 'w-12 h-12 text-gray-400 dark:text-gray-500');
                        svgIcon.setAttribute('fill', 'none');
                        svgIcon.setAttribute('viewBox', '0 0 24 24');
                        svgIcon.setAttribute('stroke', 'currentColor');
                        svgIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>';
                        
                        // Text content
                        const textDiv = document.createElement('div');
                        textDiv.className = 'space-y-1';
                        
                        const mainText = document.createElement('p');
                        mainText.className = 'text-sm font-medium text-gray-700 dark:text-gray-300';
                        mainText.textContent = 'Upload an image';
                        
                        const subText = document.createElement('p');
                        subText.className = 'text-xs text-gray-500 dark:text-gray-400';
                        subText.textContent = 'Drag and drop or click to browse';
                        
                        textDiv.appendChild(mainText);
                        textDiv.appendChild(subText);
                        
                        labelContent.appendChild(svgIcon);
                        labelContent.appendChild(textDiv);
                        
                        // Preview image element
                        const preview = document.createElement('div');
                        preview.className = 'mt-4 relative hidden';
                        
                        const imgElement = document.createElement('img');
                        imgElement.className = 'mx-auto max-h-64 rounded-lg shadow-md';
                        
                        // Remove button
                        const removeBtn = document.createElement('button');
                        removeBtn.type = 'button';
                        removeBtn.className = 'absolute -top-3 -right-3 bg-red-500 hover:bg-red-600 text-white p-1 rounded-full shadow-md transition-colors duration-300 hidden';
                        removeBtn.innerHTML = `
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        `;

                        preview.appendChild(imgElement);
                        preview.appendChild(removeBtn);
                        
                        // Insert elements in wrapper
                        wrapper.appendChild(labelContent);
                        wrapper.appendChild(preview);

                        // Insert wrapper before input, then move input inside wrapper
                        input.parentNode.insertBefore(wrapper, input);
                        wrapper.appendChild(input);

                        // Clicking wrapper triggers input click
                        wrapper.addEventListener('click', function(e) {
                            if (e.target !== removeBtn) {
                                input.click();
                            }
                        });

                        // Drag over/out effect
                        wrapper.addEventListener('dragover', function(e) {
                            e.preventDefault();
                            wrapper.classList.add('border-blue-400', 'bg-blue-50', 'dark:bg-blue-900/20');
                        });
                        
                        wrapper.addEventListener('dragleave', function() {
                            wrapper.classList.remove('border-blue-400', 'bg-blue-50', 'dark:bg-blue-900/20');
                        });
                        
                        wrapper.addEventListener('drop', function(e) {
                            e.preventDefault();
                            wrapper.classList.remove('border-blue-400', 'bg-blue-50', 'dark:bg-blue-900/20');
                            input.files = e.dataTransfer.files;
                            input.dispatchEvent(new Event('change'));
                        });

                        // Show preview on file select
                        input.addEventListener('change', function() {
                            const file = input.files[0];
                            if (!file || !file.type.startsWith('image/')) {
                                imgElement.src = '';
                                preview.classList.add('hidden');
                                return;
                            }
                            
                            const reader = new FileReader();
                            reader.onload = function(e) {
                                imgElement.src = e.target.result;
                                preview.classList.remove('hidden');
                                removeBtn.classList.remove('hidden');
                            };
                            reader.readAsDataURL(file);
                        });

                        // Remove button handler
                        removeBtn.addEventListener('click', function(e) {
                            e.stopPropagation();
                            input.value = '';
                            imgElement.src = '';
                            preview.classList.add('hidden');
                            removeBtn.classList.add('hidden');
                        });
                    });
                });
            </script>
        HTML;
    }
}
if (!function_exists('slug')) {
    function slug(string $title, string $separator = '-'): string
    {
        $slug = mb_strtolower($title, 'UTF-8');

        $slug = preg_replace('/[^a-z0-9]+/i', $separator, $slug);

        $slug = preg_replace('/' . preg_quote($separator, '/') . '+/', $separator, $slug);

        $slug = trim($slug, $separator);

        return $slug;
    }
}
if (!function_exists('uvid')) {
    function uvid(int $length = 32): string {
        if (!in_array($length, [6, 8, 12, 32])) {
            throw new InvalidArgumentException('Length must be one of: 6, 8, 12, 32');
        }
        
        $bytes = random_bytes($length / 2);
        $hex = bin2hex($bytes);
        
        $interval = ($length === 6 || $length === 8) ? 2 : 4;
        
        $parts = str_split($hex, $interval);
        return implode('-', $parts);
    }
}
if (!function_exists('format')) {
    /**
     * Format tanggal post dalam format "F d, Y" (contoh: May 15, 2023).
     *
     * @param  \DateTime|string|null  $date
     * @return string
     */
    function format($date)
    {
        if (!$date) {
            return '';
        }

        if (is_string($date)) {
            $date = new DateTime($date);
        }

        return $date->format('F d, Y');
    }
}
if (!function_exists('auth')) {
    function auth() {
        if (array_key_exists('user', $_SESSION) && (is_array($_SESSION['user']) || is_object($_SESSION['user']))) {
            return (object) $_SESSION['user'];
        }

        return (object) ['role' => null];
    }
}
if (!function_exists('str_limit')) {
    function str_limit($string, $limit = 200, $end = '...') {
        $string = strip_tags($string);
        return mb_strimwidth($string, 0, $limit, $end);
    }
}

if (!function_exists('env')) {
    function env(string $key, $default = false): bool
    {
        $value = Env::get($key, $default);

        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            $lower = strtolower(trim($value));
            return in_array($lower, ['true', '1', 'yes', 'on']);
        }

        return (bool) $value;
    }
}