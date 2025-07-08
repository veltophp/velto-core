<?php

namespace Velto\Core\Route;

use Velto\Core\Request\Request;
use Velto\Core\View\ViewResponse;

class Route
{
    protected static array $routes = [
        'GET' => [],
        'POST' => []
    ];

    protected static array $routeNames = [];
    protected static array $currentGroupMiddleware = [];
    protected static ?array $currentRoute = null;
    protected static array $controllerNamespaces = [
        'Modules\\',
    ];

    protected static function normalizePath(string $path): string
    {
        $path = rtrim($path, '/');
        return $path === '' ? '/' : $path;
    }

    public static function get(string $path, $action)
    {
        return self::registerRoute('GET', $path, $action);
    }

    public static function post(string $path, $action)
    {
        return self::registerRoute('POST', $path, $action);
    }

    protected static function registerRoute(string $method, string $path, $action)
    {
        $path = self::normalizePath($path);

        $route = [
            'method' => $method,
            'uri' => $path,
            'controller' => $action,
            'middleware' => self::$currentGroupMiddleware,
            'name' => null,
        ];

        self::$routes[$method][$path] = $route;
        self::$currentRoute = &self::$routes[$method][$path];

        return new class {
            public function name(string $name)
            {
                $currentRoute = Route::getCurrentRoute();
                if ($currentRoute) {
                    $currentRoute['name'] = $name;
                    Route::setRouteName($name, $currentRoute['uri']);
                }
            }
        };
    }

    public static function group(array $attributes, callable $callback)
    {
        $previous = self::$currentGroupMiddleware;

        if (isset($attributes['middleware'])) {
            self::$currentGroupMiddleware = (array) $attributes['middleware'];
        }

        $callback();

        self::$currentGroupMiddleware = $previous;
    }

    public static function dispatch($request)
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $scriptName = dirname($_SERVER['SCRIPT_NAME']);

        if (strpos($uri, $scriptName) === 0) {
            $uri = substr($uri, strlen($scriptName));
        }

        $uri = self::normalizePath('/' . ltrim($uri, '/'));
        $routes = self::$routes[$method] ?? [];

        foreach ($routes as $route) {
            $pattern = preg_replace('#\{[^}]+\}#', '([^/]+)', $route['uri']);
            $pattern = '#^' . $pattern . '$#';

            if (preg_match($pattern, $uri, $matches)) {
                array_shift($matches);
                self::$currentRoute = $route;

                $middlewareStack = $route['middleware'] ?? [];

                $pipeline = array_reduce(
                    array_reverse($middlewareStack),
                    fn ($next, $middleware) => fn ($req) => (new $middleware())->handle($req, $next),
                    fn ($req) => self::runController($route, $matches)
                );

                $response = $pipeline($request);

                if ($response instanceof ViewResponse) {
                    echo $response;
                    return;
                }

                if (is_string($response) || is_numeric($response)) {
                    echo $response;
                } elseif (is_object($response) && method_exists($response, 'send')) {
                    $response->send();
                }

                return;
            }
        }

        abort(404, message: "The page that you try to access is not available");
    }

    protected static function runController(array $route, array $matches)
    {
        $controller = $route['controller'];
        $response = null;

        if (is_callable($controller)) {
            $response = call_user_func_array($controller, $matches);
        } elseif (is_array($controller)) {
            [$class, $method] = $controller;

            if (!class_exists($class)) {
                abort(404, "Controller [$class] not found.");
            }

            if (!method_exists($class, $method)) {
                abort(404, "Method [$method] not found in controller [$class].");
            }

            $response = self::invokeControllerMethod($class, $method, $matches);
        } elseif (is_string($controller) && str_contains($controller, '::')) {
            [$ctrl, $method] = explode('::', $controller);
            $resolved = false;

            foreach (self::$controllerNamespaces ?? [] as $ns) {
                $class = $ns . $ctrl;
                if (class_exists($class)) {
                    if (!method_exists($class, $method)) {
                        abort(404, "Method [$method] not found in controller [$class].");
                    }

                    $response = self::invokeControllerMethod($class, $method, $matches);
                    $resolved = true;
                    break;
                }
            }

            if (!$resolved) {
                abort(404, "Controller [$ctrl] not found in any known namespace.");
            }
        } else {
            abort(500, 'Invalid route handler');
        }

        return $response;
    }

    protected static function invokeControllerMethod(string $class, string $method, array $matches)
    {
        $instance = new $class();
        $refMethod = (new \ReflectionClass($instance))->getMethod($method);
        $params = [];

        $request = new Request();
        $matchIndex = 0;

        foreach ($refMethod->getParameters() as $param) {
            $type = $param->getType();

            if ($type && !$type->isBuiltin()) {
                $paramClass = $type->getName();

                if ($paramClass === Request::class) {
                    $params[] = $request;
                } else {
                    $params[] = new $paramClass();
                }
            } else {
                $params[] = $matches[$matchIndex++] ?? null;
            }
        }

        return $refMethod->invokeArgs($instance, $params);
    }
    
    public static function getRouteNames(): array
    {
        return self::$routeNames;
    }

    public static function getUrlByName(string $name): ?string
    {
        return self::$routeNames[$name] ?? null;
    }

    public static function setRouteName(string $name, string $uri)
    {
        self::$routeNames[$name] = $uri;
    }

    public static function getCurrentRoute(): ?array
    {
        return self::$currentRoute;
    }

    public static function current()
    {
        return self::$currentRoute;
    }

    public static function addControllerNamespace(string $namespace)
    {
        self::$controllerNamespaces[] = rtrim($namespace, '\\') . '\\';
    }
} 
