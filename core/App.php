<?php

namespace Velto\Core;

use Velto\Core\Env;
use Velto\Core\Route;
use Throwable;

class App
{
    public static function run(): void
    {
        try {
            // Initialize environment and session
            self::initialize();

            // Load application routes
            self::loadRoutes();

            // Dispatch the request
            Route::dispatch();

        } catch (Throwable $e) {
            self::handleException($e);
        }
    }

    protected static function initialize(): void
    {
        // Load environment variables
        Env::load();

        // Initialize secure session
        if (session_status() === PHP_SESSION_NONE) {
            session_start([
                'cookie_lifetime' => 86400, // 1 day
                'cookie_httponly' => true,
                'cookie_secure' => isset($_SERVER['HTTPS']),
                'cookie_samesite' => 'Lax',
                'use_strict_mode' => true
            ]);
        }
    }

    protected static function loadRoutes(): void
    {
        // BASE_PATH = root project, 1 level di atas public/
        $basePath = dirname(getcwd());
        $routesPath = $basePath . '/routes/web.php';

        if (!file_exists($routesPath)) {
            throw new \Exception("Routes file not found: $routesPath");
        }

        require_once $routesPath;
    }

    public static function handleException(Throwable $e): void
    {
        $code = self::getValidHttpCode($e->getCode());
        http_response_code($code);

        if (Env::isDebug()) {
            self::renderDebugView($e, $code);
        } else {
            self::renderProductionError($e, $code);
        }

        exit(1);
    }

    protected static function getValidHttpCode(int $code): int
    {
        return ($code >= 400 && $code < 600) ? $code : 500;
    }

    protected static function renderDebugView(Throwable $e, int $code): void
    {

        $viewFile = BASE_PATH . '/views/errors/debug.vel.php';

        if (file_exists($viewFile)) {
            extract([
                'code' => $code,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTrace(),
            ], EXTR_SKIP);

            require $viewFile;
        } else {
            self::renderFallbackError($code, $e);
        }
    }

    protected static function renderProductionError(Throwable $e, int $code): void
    {


        $viewFile = BASE_PATH . '/views/errors/debug.vel.php';

        if (file_exists($viewFile)) {
            extract([
                'code' => $code,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTrace(),
            ], EXTR_SKIP);

            require $viewFile;
        } else {
            self::renderFallbackError($code);
        }
    }

    protected static function renderFallbackError(int $code, ?Throwable $e = null): void
    {
        echo "<h1>{$code} | Server Error</h1>";

        if ($e && Env::isDebug()) {
            echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
        } else {
            echo "<p>Something went wrong. Please try again later.</p>";
        }
    }
}
