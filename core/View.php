<?php

namespace Velto\Core;

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
        // Mengatur jalur views dan cache berdasarkan argumen yang diberikan
        self::$viewsPath = rtrim($viewsPath, '/');
        self::$cachePath = rtrim($cachePath, '/');
        
        // Pastikan direktori cache ada, jika tidak, buat direktori
        if (!file_exists(self::$cachePath)) {
            mkdir(self::$cachePath, 0755, true);
        }
    }

    protected static function viewPath(string $view): string
    {
        // Gunakan self::$viewsPath untuk jalur view
        return self::$viewsPath . '/' . str_replace('.', '/', $view) . '.vel.php';
    }

    public static function render(string $view, array $data = []): string
    {
        // Reset state before each render
        self::reset();

        extract($data, EXTR_SKIP);
        ob_start();

        try {
            // Render main view first to capture sections
            $viewPath = self::resolveViewPath($view);
            $compiledView = self::compileView($viewPath);
            include $compiledView;
            $content = ob_get_clean();

            // Render layout if exists
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
        if (self::$viewsPath === null) {
            throw new \RuntimeException('Views path not configured. Call View::configure() first.');
        }

        $path = self::$viewsPath . '/' . str_replace('.', '/', $view) . '.vel.php';
        
        if (!file_exists($path)) {
            throw new \RuntimeException("View [{$view}] not found at: {$path}");
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
        $replacements = [
            // Layout and sections
            '/@extends\(\'(.*?)\'\)/' => '<?php \Velto\Core\View::setLayout(\'$1\'); ?>',
            '/@section\(\'(.*?)\'\)/' => '<?php \Velto\Core\View::startSection(\'$1\'); ?>',
            '/@endsection/' => '<?php \Velto\Core\View::endSection(); ?>',
            '/@yield\(\'(.*?)\'(?:,\s*\'(.*?)\')?\)/' => '<?php echo \Velto\Core\View::yieldSection(\'$1\', \'$2\' ?? \'\'); ?>',
            '/@include\(\'(.*?)\'\)/' => '<?php \Velto\Core\View::include(\'$1\'); ?>',

            // Control structures
            '/@if\s*\((.*?)\)/' => '<?php if ($1): ?>',
            '/@elseif\s*\((.*?)\)/' => '<?php elseif ($1): ?>',
            '/@else/' => '<?php else: ?>',
            '/@endif/' => '<?php endif; ?>',

            '/@foreach\s*\((.*?)\)/' => '<?php foreach ($1): ?>',
            '/@endforeach/' => '<?php endforeach; ?>',

            '/@for\s*\((.*?)\)/' => '<?php for ($1): ?>',
            '/@endfor/' => '<?php endfor; ?>',

            '/@while\s*\((.*?)\)/' => '<?php while ($1): ?>',
            '/@endwhile/' => '<?php endwhile; ?>',

            '/@php/' => '<?php ',
            '/@endphp/' => ' ?>',
        ];

        $compiled = preg_replace(array_keys($replacements), array_values($replacements), $content);

        $compiled = preg_replace('/{{\s*(.*?)\s*}}/', '<?= htmlspecialchars($1, ENT_QUOTES, "UTF-8") ?>', $compiled);
        $compiled = preg_replace('/{!!\s*(.+?)\s*!!}/', '<?= $1 ?>', $compiled);

        $compiled = preg_replace_callback('/@(\w+)/', function ($matches) {
            $name = $matches[1];
            if (function_exists($name)) {
                return '<?= ' . $name . '() ?>';
            }
            return $matches[0];
        }, $compiled);

        // Custom directives
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
}
