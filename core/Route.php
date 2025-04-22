<?php

namespace Velto\Core;

class Route
{
    private static $routes = [];
    private static $currentGroupMiddleware = [];

    public static function get($uri, $controller)
    {
        return self::registerRoute('GET', $uri, $controller);
    }

    public static function post($uri, $controller)
    {
        return self::registerRoute('POST', $uri, $controller);
    }

    private static function registerRoute($method, $uri, $controller)
    {
        $route = [
            'method' => $method,
            'uri' => self::normalizeUri($uri),
            'controller' => $controller,
            'middleware' => self::$currentGroupMiddleware
        ];

        self::$routes[] = $route;
        
        // return new RouteBuilder(count(self::$routes) - 1, $route);
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
            // echo "Error!!";
        } else {
            echo $message ?? "{$code} Error";
        }

        exit;
    }


    public static function getRoutes()
    {
        return self::$routes;
    }

    public static function resolve($controller)
    {
        if (is_callable($controller)) {
            return call_user_func($controller);
        }

        if (is_string($controller) && strpos($controller, '@') !== false) {
            [$class, $method] = explode('@', $controller);
            $class = 'App\\Controllers\\' . str_replace('/', '\\', $class);

            if (class_exists($class)) {
                $instance = new $class;

                if (method_exists($instance, $method)) {
                    return call_user_func([$instance, $method]);
                }
            }
        }

        self::abort(404);
    }

    public static function dispatch()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = self::normalizeUri(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

        foreach (self::$routes as $route) {
            if ($route['method'] === $method && $route['uri'] === $uri) {
                // Eksekusi middleware terlebih dahulu
                if (!empty($route['middleware'])) {
                    foreach ($route['middleware'] as $middleware) {
                        $middleware::handle();
                    }
                }

                // Resolve dan kembalikan response
                $response = self::resolve($route['controller']);
                
                // Handle jika controller return string langsung
                if (is_string($response)) {
                    echo $response;
                    return;
                }
                
                // Jika menggunakan Response object
                if (is_object($response) && method_exists($response, 'send')) {
                    $response->send();
                    return;
                }
                
                return;
            }
        }

        self::abort(404);
    }

}