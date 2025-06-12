<?php

/**
 * Class App in namespace Velto\Core.
 *
 * Structure: Main application class with static methods for running the app and handling errors.
 *
 * How it works:
 * - `run()`: Initializes the app, loads routes (including Axion's), dispatches the request, and handles exceptions.
 * - `initialize()`: Loads environment variables and starts the session with security settings.
 * - `loadRoutes()`: Includes the application's `routes/web.php` file.
 * - `handleException(Throwable $e)`: Catches and processes exceptions, displaying debug info in development or a generic error in production.
 * - `getValidHttpCode()`: Ensures a valid HTTP error code.
 * - `renderDebugView()`: Shows detailed error information in debug mode.
 * - `renderProductionError()`: Shows a simple error page in production.
 * - `renderFallbackError()`: Displays a basic error message if custom error views are missing.
 */

namespace Velto\Core;

use Velto\Core\Env;
use Velto\Core\Route;
use Velto\Axion\Axion;
use Velto\Axion\Session;

use Throwable;

class App
{
    public static function run(): void
    {
        try {
            self::initialize();

            if (class_exists('\Velto\Axion\Axion')) {

                Axion::loadRoutes();
            }
            
            self::loadRoutes();

            Route::dispatch();

        } catch (Throwable $e) {

            self::handleException($e);
        }
    }

    protected static function initialize(): void
    {
        Env::load();

        Session::start();

    }

    protected static function loadRoutes(): void
    {
        $basePath = dirname(getcwd());
        $routesPath = $basePath . '/routes/web.php';

        if (!file_exists($routesPath)) {
            throw new \Exception("Routes file not found: $routesPath");
        }

        require_once $routesPath;
    }

    public static function handleException(Throwable $e): void
    {
        $code = (int) $e->getCode(); 
        $code = self::getValidHttpCode($code);
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

        $viewFile = BASE_PATH . '/vendor/veltophp/velto-core/core/Errors/debug.vel.php';

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
        $message = $e?->getMessage() ?? 'Server Error';

        abort($code, $message);
    }

}
