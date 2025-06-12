<?php

/**
 * Class View in namespace Velto\Core.
 *
 * Structure: Manages view rendering, layout inheritance, sections, includes, custom directives, and caching.
 * - `$layout`: Stores the name of the layout to extend.
 * - `$sections`: Associative array to store content for different sections.
 * - `$currentSection`: Keeps track of the currently open section.
 * - `$customDirectives`: Array to store user-defined view directives.
 * - `$viewsPath`: Path to the application's view files.
 * - `$cachePath`: Path to store compiled view files.
 *
 * How it works:
 * - `configure(string $viewsPath, string $cachePath)`: Sets the paths for views and the cache directory, creating the cache directory if it doesn't exist.
 * - `viewPath(string $view)`: Constructs the full path to a view file.
 * - `render(string $view, array $data = []): string`: Renders a specified view with optional data. It resets view state, extracts data, starts output buffering, resolves and compiles the view, includes the compiled view, captures the content, and then handles layout extension if specified. Returns the final rendered content.
 * - `resolveViewPath(string $view)`: Determines the full path to a view file, handling namespaced views (e.g., 'axion::...') and standard application views. Throws an exception if the view file is not found.
 * - `compileView(string $viewPath): string`: Compiles a view file into a PHP file in the cache directory. It checks if a compiled version exists and is up-to-date; if not, it reads the view content, compiles it using `compile()`, and saves the compiled output.
 * - `compile(string $content): string`: Parses the view content for VeltoPHP directives (@extends, @section, @yield, @include, control structures, echo syntax {{ }}, {!! !!}, custom directives) and replaces them with corresponding PHP code. It also handles function calls prefixed with `@`.
 * - `section(string $name)`: Alias for `startSection()`.
 * - `startSection(string $name)`: Starts a new section with the given name, initiating output buffering.
 * - `endSection()`: Ends the current section, capturing the buffered content and storing it in the `$sections` array under the section's name.
 * - `yieldSection(string $name, ?string $default = null): string`: Retrieves and returns the content of a named section. If the section is not found, it returns the `$default` value (or an empty string, with a debug comment in debug mode).
 * - `setLayout(string $layout)`: Sets the layout to be used for the current view.
 * - `component(string $name, array $data = []): void`: Includes a compiled component view with the provided data.
 * - `include(string $view): void`: Includes a compiled view file.
 * - `directive(string $name, callable $handler): void`: Registers a custom view directive with a given name and handler function.
 * - `reset()`: Resets the `$layout`, `$sections`, and `$currentSection` properties for a new rendering process.
 * - `debugSections(): array`: Returns the current section state for debugging.
 * - `debugPaths(string $view): array`: Returns relevant view paths for debugging.
 */

namespace Velto\Core;
use Velto\Axion\Session;

class View
{
    protected static ?string $layout = null;
    protected static array $sections = [];
    protected static string $currentSection = '';
    protected static array $customDirectives = [];
    protected static string $viewsPath = __DIR__ . '/../views';
    protected static string $cachePath = __DIR__ . '/../storage/cache/views';

    public static function configure(string $viewsPath, string $cachePath): void
    {
        self::$viewsPath = rtrim($viewsPath, '/');
        self::$cachePath = rtrim($cachePath, '/');
        
        if (!file_exists(self::$cachePath)) {

            mkdir(self::$cachePath, 0755, true);
        }
    }

    protected static function viewPath(string $view): string
    {
        return self::$viewsPath . '/' . str_replace('.', '/', $view) . '.vel.php';
    }

    public static function render(string $view, array $data = []): string
    {
        self::reset();

        extract($data, EXTR_SKIP);
        ob_start();

        try {
            $viewPath = self::resolveViewPath($view);
            $compiledView = self::compileView($viewPath);
            include $compiledView;
            $content = ob_get_clean();

            if (!empty(self::$layout)) {
                ob_start();
                try {
                    $layoutPath = self::resolveViewPath(self::$layout);
                    $compiledLayout = self::compileView($layoutPath);
                    include $compiledLayout;
                    $content = ob_get_clean();
                } catch (\Throwable $e) {
                    ob_end_clean();
                    throw $e;
                }
            }

            return $content;
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
    }

    protected static function resolveViewPath(string $view): string
    {
        if (str_contains($view, '::')) {

            [$namespace, $file] = explode('::', $view, 2);
            $filePath = str_replace('.', '/', $file) . '.vel.php';

            if ($namespace === 'axion') {
                
                $path = dirname(base_path(), 1) . '/axion/views/' . $filePath;

            } else {
                throw new \RuntimeException("Unknown view namespace: [$namespace]");
            }
        } else {

            if (self::$viewsPath === null) {

                throw new \RuntimeException('Views path not configured. Call View::configure() first.');
            }

            $path = self::$viewsPath . '/' . str_replace('.', '/', $view) . '.vel.php';
        }

        if (!file_exists($path)) {
            
            throw new \RuntimeException("View [$view] not found at: $path");
        }

        return $path;
    }

    protected static function compileView(string $viewPath): string
    {

        $compiledPath = self::$cachePath . '/' . md5($viewPath) . '.php';

        if (!file_exists($viewPath)) {
            throw new \RuntimeException("View file not found: {$viewPath}. Please check the file location.");
        }

        if (!file_exists($compiledPath) || filemtime($viewPath) > filemtime($compiledPath)) {
            $content = file_get_contents($viewPath);
            $compiled = self::compile($content);
            file_put_contents($compiledPath, $compiled);
        }

        return $compiledPath;
    }
    
    protected static function compile(string $content): string
    {
        $content = preg_replace('/\{\{--(.*?)--\}\}/s', '', $content);

        $replacements = [
            '/@extends\(\'(.*?)\'\)/' => '<?php \Velto\Core\View::setLayout(\'$1\'); ?>',
            '/@section\(\'(.*?)\'\)/' => '<?php \Velto\Core\View::startSection(\'$1\'); ?>',
            '/@endsection/' => '<?php \Velto\Core\View::endSection(); ?>',
            '/@yield\(\'(.*?)\'(?:,\s*\'(.*?)\')?\)/' => '<?php echo \Velto\Core\View::yieldSection(\'$1\', \'$2\' ?? \'\'); ?>',
            '/@include\(\s*\'(.*?)\'\s*,\s*(\[[^\)]*\])\s*\)/' => '<?php \Velto\Core\View::include(\'$1\', $2); ?>',
            '/@include\(\'(.*?)\'\)/' => '<?php \Velto\Core\View::include(\'$1\'); ?>',

            '/@if\s*\((.+)\)/U'       => '<?php if (\1): ?>',
            '/@elseif\s*\((.+)\)/U'   => '<?php elseif (\1): ?>',
            '/@else/'                 => '<?php else: ?>',
            '/@endif/'                => '<?php endif; ?>',
            

            '/@error\s*\(\s*[\'"](.*?)[\'"]\s*\)/' => '<?php if (!empty($errors["$1"])): foreach ((array)$errors["$1"] as $message): ?>',
            '/@enderror/' => '<?php endforeach; endif; ?>',


            '/@foreach\s*\((.*?)\)/' => '<?php foreach ($1): ?>',
            '/@endforeach/'          => '<?php endforeach ?>',

            '/@forelse\s*\((.*?)\)/' => '<?php foreach ($1): ?><?php if (!empty($1)): ?>',
            '/@empty/' => '<?php endforeach; else: ?>',
            '/@endforelse/' => '<?php endif ?>',



            '/@for\s*\((.*?)\)/' => '<?php for ($1): ?>',
            '/@endfor/' => '<?php endfor; ?>',

            '/@while\s*\((.*?)\)/' => '<?php while ($1): ?>',
            '/@endwhile/' => '<?php endwhile; ?>',

            '/@php/' => '<?php ',
            '/@endphp/' => ' ?>',

            '/@active\(\'(.*?)\'\)/' => '<?php echo active(\'$1\'); ?>',
            '/@active\(\'(.*?)\'\s*,\s*\'(.*?)\'\)/' => '<?php echo active(\'$1\', \'$2\'); ?>',

            '/@auth/' => '<?php if (auth()): ?>',
            '/@endauth/' => '<?php endif; ?>',
            '/@guest/' => '<?php if (!auth()): ?>',
            '/@endguest/' => '<?php endif; ?>',

            '/@csrf/' => '<?= csrf_field() ?>',

            '/@flash_errors/' => '<?= render_flash(); ?>'

        ];  
        
        $patterns = [
            '/@if\s*\(((?:[^()]|\(.*?\))*?)\)/' 
            => function ($match) {
                return '<?php if (' . trim($match[1]) . '): ?>';
            },
            '/@if\s*\(((?:(?:[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\$?[a-zA-Z0-9_\x7f-\xff]*|\$[a-zA-Z0-9_\x7f-\xff]+|\(.*?\)|->|\(|\))*?)\)/' 
            => function ($match) {
                $condition = $match[1];
                return '<?php if (' . $condition . '): ?>';
            },
            '/@elseif\s*\(((?:(?:[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\$?[a-zA-Z0-9_\x7f-\xff]*|\$[a-zA-Z0-9_\x7f-\xff]+|\(.*?\)|->|\(|\))*?)\)/' 
            => function ($match) {
                $condition = $match[1];
                return '<?php elseif (' . $condition . '): ?>';
            },
            '/@else/' => '<?php else: ?>',
            '/@endif/' => '<?php endif; ?>',
        ];
        
        foreach ($patterns as $pattern => $replacement) {
            if (is_callable($replacement)) {
                $content = preg_replace_callback($pattern, $replacement, $content);
            } else {
                $content = preg_replace($pattern, $replacement, $content);
            }
        }
        
        $content = preg_replace_callback("/@role\(['\"](.+?)['\"]\)/", function ($matches) {
            return "<?php if(auth() && auth()->role === '{$matches[1]}'): ?>";
        }, $content);
        
        $content = str_replace('@end_role', '<?php endif; ?>', $content);
        
    

        $compiled = preg_replace(array_keys($replacements), array_values($replacements), $content);

        $compiled = preg_replace(
            '/{{\s*(.*?)\s*}}/',
            '<?= htmlspecialchars((string)($1), ENT_QUOTES, "UTF-8") ?>',
            $compiled
        );
        
        $compiled = preg_replace(
            '/{!!\s*(.+?)\s*!!}/',
            '<?= $1 ?>',
            $compiled
        );

        $compiled = preg_replace_callback('/@(\w+)/', function ($matches) {
            $name = $matches[1];
            if (function_exists($name)) {
                return '<?= ' . $name . '() ?>';
            }
            return $matches[0];
        }, $compiled);

        foreach (self::$customDirectives as $name => $handler) {
            $compiled = preg_replace_callback(
                '/@' . preg_quote($name) . '\((.*)\)/',
                fn($matches) => $handler($matches[1]),
                $compiled
            );
        }
        
        return $compiled;
    }

    public static function section(string $name): void
    {
        self::startSection($name);
    }

    public static function startSection(string $name): void
    {
        if (self::$currentSection !== '') {

            throw new \RuntimeException("Cannot nest sections");
        }
        self::$currentSection = $name;

        ob_start();
    }

    public static function endSection(): void
    {
        if (self::$currentSection === '') {

            throw new \RuntimeException("No active section to end");
        }

        self::$sections[self::$currentSection] = ob_get_clean();
        self::$currentSection = '';
    }

    public static function yieldSection(string $name, ?string $default = null): string
    {
        $content = self::$sections[$name] ?? $default ?? '';
        
        if ($content === '' && Env::get('APP_DEBUG') === 'true') {

            return "<!-- Section {$name} empty -->";
        }
        
        return $content;
    }

    public static function setLayout(string $layout): void
    {
        self::$layout = $layout;
    }

    public static function component(string $name, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        include self::compileView(self::resolveViewPath('components.' . $name));
    }

    public static function include(string $view): void
    {
        $path = self::viewPath($view);
        $compiled = self::compileView($path);
        require $compiled;
    }

    public static function directive(string $name, callable $handler): void
    {
        self::$customDirectives[$name] = $handler;
    }

    protected static function reset(): void
    {
        self::$layout = null;
        self::$sections = [];
        self::$currentSection = '';
    }

    public static function debugSections(): array
    {
        return [
            'current_section' => self::$currentSection,
            'sections' => self::$sections,
            'layout' => self::$layout
        ];
    }

    public static function debugPaths(string $view): array
    {
        return [
            'views_path' => self::$viewsPath,
            'cache_path' => self::$cachePath,
            'requested_view' => self::resolveViewPath($view),
            'layout_path' => self::$layout ? self::resolveViewPath(self::$layout) : null
        ];
    }

    public static function renderFlash(): string
    {
        Session::start();

        $flashes = $_SESSION['flash_messages'] ?? [];
        unset($_SESSION['flash_messages']);

        $output = '';

        $types = [
            'error'   => 'bg-red-100 border-red-400 text-red-700',
            'success' => 'bg-green-100 border-green-400 text-green-700',
            'info'    => 'bg-blue-100 border-blue-400 text-blue-700',
            'warning' => 'bg-yellow-100 border-yellow-400 text-yellow-700',
        ];

        foreach ($types as $type => $classes) {
            if (!empty($flashes[$type])) {
                $output .= "<div class=\"{$classes} border px-4 py-3 rounded mb-3\">";
                foreach ($flashes[$type] as $message) {
                    $output .= '<p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>';
                }
                $output .= '</div>';
            }
        }

        return $output;
    }

}
