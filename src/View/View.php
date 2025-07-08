<?php

namespace Velto\Core\View;

use Velto\Core\Session\Session;
use Velto\Core\Env\Env;

class View
{
    protected static ?string $layout = null;
    protected static array $sections = [];
    protected static string $currentSection = '';
    protected static array $customDirectives = [];
    protected static string $viewsPath;
    protected static string $cachePath;


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

    public static function render(string $view, array $data = []): ViewResponse
    {
        return new ViewResponse($view, $data);
    }

    public static function renderRaw(string $view, array $data = []): string
    {
        self::reset();
    
        // extract($data, EXTR_SKIP);
        $rawData = $data;

        foreach ($rawData as $key => $value) {
            $$key = $value;
        }

        // restore $data as object if it was passed
        if (isset($rawData['data']) && is_object($rawData['data'])) {
            $data = $rawData['data'];
        }

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
        
        $original = $view;

        $viewPath = str_replace(['::', '.'], '/', $view) . '.vel.php';

        $globalPath = BASE_PATH . "/resources/Views/{$viewPath}";
        if (file_exists($globalPath)) {
            return $globalPath;
        }

        $segments = explode('/', $viewPath);
        if (count($segments) < 2) {
            $module = 'Home';
            $relativePath = $segments[0];
        } else {
            $module = ucfirst(array_shift($segments));
            $relativePath = implode('/', $segments);
        }

        $templateDirs = ['Views'];
        foreach ($templateDirs as $dir) {
            $modulePath = self::$viewsPath . "/$module/$dir/$relativePath";
            if (file_exists($modulePath)) {
                return $modulePath;
            }
        }

        $tried = array_map(fn($d) => self::$viewsPath . "/$module/$d/$relativePath", $templateDirs);
        $tried[] = $globalPath;
        

        throw new \RuntimeException("View [$original] not found. Tried:\n- " . implode("\n- ", $tried));
    }

    protected static function compileView(string $viewPath): string
    {
        if (!file_exists($viewPath)) {
            throw new \RuntimeException("View file not found: {$viewPath}. Please check the file location.");
        }

        $compiledPath = self::$cachePath . '/' . md5($viewPath) . '.php';

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
            '/@extends\(\'(.*?)\'\)/' => '<?php \Velto\Core\View\View::setLayout(\'$1\'); ?>',
            '/@section\(\'(.*?)\'\)/' => '<?php \Velto\Core\View\View::startSection(\'$1\'); ?>',
            '/@endsection/' => '<?php \Velto\Core\View\View::endSection(); ?>',
            '/@yield\(\'(.*?)\'(?:,\s*\'(.*?)\')?\)/' => '<?php echo \Velto\Core\View\View::yieldSection(\'$1\', \'$2\' ?? \'\'); ?>',
            '/@include\(\s*\'(.*?)\'\s*,\s*(\[[^\)]*\])\s*\)/' => '<?php \Velto\Core\View\View::include(\'$1\', $2); ?>',
            '/@include\(\'(.*?)\'\)/' => '<?php \Velto\Core\View\View::include(\'$1\'); ?>',

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

            '/@flash_errors/' => '<?= render_flash(); ?>',

            '/@flash_info\((.*?)\)/' => '<?php echo flash()->display($1); ?>',

        ];  
        
        $compiled = preg_replace(array_keys($replacements), array_values($replacements), $content);
        
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

        $content = preg_replace_callback('/@component\([\'"](.+?)[\'"]\)/', function ($matches) {
            $name = $matches[1];
            return "<?php \Velto\Core\View\View::component('{$name}'); ?>";
        }, $content);
        
        $content = preg_replace_callback("/@role\(['\"](.+?)['\"]\)/", function ($matches) {
            return "<?php if(auth() && auth()->role === '{$matches[1]}'): ?>";
        }, $content);
        
        $content = str_replace('@end_role', '<?php endif; ?>', $content);
        
        $content = preg_replace(
            '/@php\s+echo\s+(VeltoImage\([^)]+\));\s+@endphp/',
            '{{ $1 }}',
            $content
        );        

        $compiled = preg_replace(array_keys($replacements), array_values($replacements), $content);

        $compiled = preg_replace_callback('/{{\s*(.*?)\s*}}/', function ($matches) {
            $expr = trim($matches[1]);
            return "<?php echo htmlspecialchars((string)($expr), ENT_QUOTES, 'UTF-8'); ?>";
        }, $compiled);
        
        
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
