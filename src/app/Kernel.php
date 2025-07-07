<?php

namespace Velto\Core\App;

use Velto\Core\Env\Env;
use Velto\Core\Route\Route;
use Velto\Core\Request\Request;
use Velto\Core\Middleware\VerifyCsrfToken;

use Throwable;


class Kernel
{

    public function run()
    {
        try {
            $this->loadModuleRoutes();
            $this->loadModuleApiRoutes();
    
            if (function_exists('registerVeltoAlertRedirectMacros')) {
                registerVeltoAlertRedirectMacros();
            }
    
            $request = new Request();
    
            $middlewarePipeline = fn($req) => Route::dispatch($req);
    
            if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
                $middlewarePipeline = fn($req) =>
                    (new VerifyCsrfToken())->handle($req, fn($r) => Route::dispatch($r));
            }
    
            $response = $middlewarePipeline($request);
    
            if (is_string($response) || is_numeric($response)) {
                echo $response;
            } elseif (is_object($response) && method_exists($response, 'send')) {
                $response->send();
            }
    
        } catch (Throwable $e) {
            self::handleException($e);
        }
    }
      

    protected function loadModuleRoutes(): void
    {
        $modulesPath = defined('MODULES_PATH') ? MODULES_PATH : dirname(__DIR__, 2) . '/modules';

        if (!is_dir($modulesPath)) {
            return;
        }

        $modules = scandir($modulesPath);

        foreach ($modules as $module) {
            if ($module === '.' || $module === '..') continue;

            $routeFile = $modulesPath . '/' . $module . '/routes.php';

            if (file_exists($routeFile)) {
                require_once $routeFile;
            }
        }
    }

    public static function loadModuleApiRoutes(): void
    {
        $modulesPath = defined('MODULES_PATH') ? MODULES_PATH : dirname(__DIR__, 2) . '/modules';

        if (!is_dir($modulesPath)) {
            return;
        }

        $modules = scandir($modulesPath);

        foreach ($modules as $module) {
            if ($module === '.' || $module === '..') continue;

            $apiFile = $modulesPath . '/' . $module . '/api.php';

            if (file_exists($apiFile)) {
                require_once $apiFile;
            }
        }
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

        $viewFile = BASE_PATH . '/resources/views/errors/debug.vel.php';

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

        $viewFile = BASE_PATH . '/resources/views/errors/debug.vel.php';

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
