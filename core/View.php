<?php

namespace Velto\Core;

class View
{
    protected static string $layout;
    protected static array $sections = [];
    protected static string $currentSection = '';
    protected static array $customDirectives = [];
    protected static string $viewsPath = __DIR__ . '/views';
    protected static string $cachePath = __DIR__ . '/cache';

    public static function render(string $view, array $data = []): void
    {
        extract($data);
        $viewPath = self::viewPath($view);

        if (!file_exists($viewPath)) {
            throw new \Exception("View file not found: {$viewPath}");
        }

        $compiled = self::compileView($viewPath);
        require $compiled;

        if (isset(self::$layout)) {
            $layoutPath = self::viewPath(self::$layout);
            $compiledLayout = self::compileView($layoutPath);
            require $compiledLayout;
        }
    }

    protected static function viewPath(string $view): string
    {
        return dirname(__DIR__) . '/views/' . str_replace('.', '/', $view) . '.vel.php';
    }

    protected static function compileView(string $viewPath): string
    {
        $compiledPath = sys_get_temp_dir() . '/velto_' . md5($viewPath . filemtime($viewPath)) . '.php';

        if (file_exists($compiledPath)) {
            return $compiledPath;
        }

        $content = file_get_contents($viewPath);

        $replacements = [
            // Layout and sections
            '/@extends\(\'(.*?)\'\)/' => '<?php \Core\View::setLayout(\'$1\'); ?>',
            '/@section\(\'(.*?)\'\)/' => '<?php \Core\View::startSection(\'$1\'); ?>',
            '/@endsection/' => '<?php \Core\View::endSection(); ?>',
            '/@yield\(\'(.*?)\'(?:,\s*\'(.*?)\')?\)/' => '<?php echo \Core\View::yieldSection(\'$1\', \'$2\' ?? \'\'); ?>',
            '/@include\(\'(.*?)\'\)/' => '<?php \Core\View::includeView(\'$1\'); ?>',

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

        $compiled = preg_replace('/{{\s*(.+?)\s*}}/', '<?= htmlspecialchars($1) ?>', $compiled);
        $compiled = preg_replace('/{!!\s*(.+?)\s*!!}/', '<?= $1 ?>', $compiled);

        // Custom directives
        foreach (self::$customDirectives as $name => $handler) {
            $pattern = '/@' . preg_quote($name) . '\((.*?)\)/';
            $compiled = preg_replace_callback($pattern, function ($matches) use ($handler) {
                return call_user_func($handler, $matches[1]);
            }, $compiled);
        }

        file_put_contents($compiledPath, $compiled);
        return $compiledPath;
    }

    public static function setLayout(string $layout): void
    {
        self::$layout = $layout;
    }

    public static function startSection(string $name): void
    {
        self::$currentSection = $name;
        ob_start();
    }

    public static function endSection(): void
    {
        self::$sections[self::$currentSection] = ob_get_clean();
        self::$currentSection = '';
    }

    public static function yieldSection(string $name, string $default = ''): void
    {
        echo self::$sections[$name] ?? $default;
    }

    public static function includeView(string $view): void
    {
        $path = self::viewPath($view);
        $compiled = self::compileView($path);
        require $compiled;
    }

    public static function directive(string $name, callable $handler): void
    {
        self::$customDirectives[$name] = $handler;
    }
}
