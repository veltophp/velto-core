<?php

/**
 * Class Route in namespace Velto\Core.
 *
 * Structure: Manages application routes, mapping URIs to controllers and actions, and handling middleware.
 * - `$controllerNamespaces`: Array of namespaces to search for controllers.
 * - `$routes`: Array storing registered route definitions.
 * - `$routeNames`: Associative array mapping route names to their URIs.
 * - `$currentGroupMiddleware`: Array to hold middleware applied to the current route group.
 * - `$currentRoute`: Stores the currently matched route.
 *
 * How it works:
 * - `get($uri, $controller)`: Registers a GET route for the given URI and controller.
 * - `post($uri, $controller)`: Registers a POST route for the given URI and controller.
 * - `getRouteNames()`: Returns the array of named routes.
 * - `getCurrentRoute()`: Returns the currently matched route array.
 * - `registerRoute($method, $uri, $controller)`: Creates a route array with the method, URI, controller, current group middleware, and an initial null name. Adds the route to the `$routes` array and sets `$currentRoute`. Returns an anonymous class with a `name()` method to assign a name to the route.
 * - `setRouteName(string $name, string $uri)`: Stores the route name and its URI in the `$routeNames` array.
 * - `getUrlByName(string $name)`: Retrieves the URI of a route by its name.
 * - `normalizeUri($uri)`: Ensures the URI starts with '/' and doesn't end with '/'.
 * - `updateRoute($index, $route)`: Allows updating an existing route at a specific index.
 * - `abort($code, $message = null)`: Sets the HTTP response code and displays a custom error view or a default message, then exits.
 * - `getRoutes()`: Returns the array of all registered routes.
 * - `current()`: Returns the currently matched route array.
 * - `addControllerNamespace($namespace)`: Adds a namespace to the list where controllers are searched.
 * - `group(array $attributes, callable $callback)`: Applies attributes (like middleware) to a group of routes defined within the `$callback` function. Resets the group middleware after the callback.
 * - `resolve($handler)`: Executes the route handler. If it's a callable, it's called directly. If it's a string in 'Controller@method' format, it instantiates the controller (searching through defined namespaces) and calls the specified method. Aborts with 404 if the controller or method is not found, or 500 for an invalid handler.
 * - `dispatch()`: Matches the current HTTP request method and URI against the registered routes. If a match is found, it executes any associated middleware and then resolves the route's controller. If the handler returns a string, it's echoed; if it's an object with a `send()` method, that method is called. If no route matches, it calls `abort(404)`.
 */

namespace Velto\Core;

class Route
{
    private static $controllerNamespaces = [
        'App\\Controllers\\',  
        'Velto\\Axion\\Controllers\\', 
    ];

    private static $routes = [];
    protected static $routeNames = []; 
    protected static array $currentGroupMiddleware = [];
    protected static $currentRoute = null; 

    public static function get($uri, $controller)
    {
        return self::registerRoute('GET', $uri, $controller);
    }

    public static function post($uri, $controller)
    {
        return self::registerRoute('POST', $uri, $controller);
    }

    public static function getRouteNames(): array
    {
        return self::$routeNames;
    }

    public static function getCurrentRoute(): ?array
    {
        return self::$currentRoute;
    }

    public static function registerRoute($method, $uri, $controller)
    {
        $route = [
            'method' => $method,
            'uri' => $uri,
            'controller' => $controller,
            'middleware' => self::$currentGroupMiddleware,
            'name' => null,
        ];

        self::$routes[] = $route;

        self::$currentRoute = &self::$routes[array_key_last(self::$routes)];

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

    public static function setRouteName(string $name, string $uri)
    {
        self::$routeNames[$name] = $uri;
    }

    public static function getUrlByName(string $name): ?string
    {
        return self::$routeNames[$name] ?? null;
    }

    private static function normalizeUri($uri)
    {
        return rtrim($uri, '/') ?: '/';
    }

    public static function updateRoute($index, $route)
    {
        if (isset(self::$routes[$index])) {
            self::$routes[$index] = $route;
        }
    }

    public static function abort($code, $message = null)
    {
        http_response_code($code);

        $viewFile = BASE_PATH . "/views/errors/{$code}.vel.php";

        if (file_exists($viewFile)) {
            require $viewFile;
        } else {
            echo $message ?? "{$code} Error";
        }

        exit;
    }

    public static function getRoutes()
    {
        return self::$routes;
    }

    public static function current()
    {
        return self::$currentRoute;
    }

    public static function addControllerNamespace($namespace)
    {
        self::$controllerNamespaces[] = rtrim($namespace, '\\') . '\\';
    }

    public static function group(array $attributes, callable $callback)
    {
        $previousGroupMiddleware = self::$currentGroupMiddleware;

        if (isset($attributes['middleware'])) {
            self::$currentGroupMiddleware = (array) $attributes['middleware'];
        }

        call_user_func($callback);

        self::$currentGroupMiddleware = $previousGroupMiddleware;
    }

    protected static function resolve($handler, $params = [])
    {
        if (is_callable($handler)) {
            
            return call_user_func_array($handler, $params);
        }

        if (is_string($handler) && str_contains($handler, '::')) {

            [$controller, $method] = explode('::', $handler);

            foreach (self::$controllerNamespaces as $namespace) {
                $class = $namespace . $controller;

                if (class_exists($class)) {
                    $instance = new $class();

                    if (!method_exists($instance, $method)) {
                        abort(404, "Method [{$method}] not found in controller [{$class}].");
                    }

                    return call_user_func_array([$instance, $method], $params);
                }
            }

            abort(404, message: "Controller [{$controller}] not found in any known namespace.");
        }

        abort(500, 'Invalid route handler. Use :: not @!');
    }
    
    public static function dispatch()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = self::normalizeUri(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

        foreach (self::$routes as $route) {
            if ($route['method'] !== $method) continue;

            $pattern = preg_replace('#\{[^}]+\}#', '([^/]+)', $route['uri']);
            $pattern = '#^' . $pattern . '$#';

            if (preg_match($pattern, $uri, $matches)) {
                array_shift($matches);

                self::$currentRoute = $route;

                if (!empty($route['middleware'])) {
                    foreach ($route['middleware'] as $middleware) {
                        if (!class_exists(\Velto\Axion\Axion::class) &&
                            str_starts_with($middleware, 'Velto\\Axion\\')) {
                            abort(404, message: "{{$middleware}}");
                        }

                        if (!class_exists($middleware)) {
                            abort(404, message: "{{$middleware}}");
                        }

                        $middleware::handle(new Request(), function () {});
                    }
                }

                $response = self::resolve($route['controller'], $matches);

                if (is_string($response)) {
                    echo $response;
                    return;
                }

                if (is_object($response) && method_exists($response, 'send')) {
                    $response->send();
                    return;
                }

                return;
            }
        }

        abort(404, message: "The page that you try to access is not available");
    }

}
