<?php

/**
 * Class Request in namespace Velto\Core.
 *
 * Structure: Represents an HTTP request, encapsulating GET, POST, FILES, and SERVER superglobal arrays.
 *
 * How it works:
 * - `__construct()`: Initializes the `$get`, `$post`, `$files`, and `$server` properties with the corresponding PHP superglobal arrays, providing a structured way to access request data.
 * - `input(string $key, $default = null)`: Retrieves a request parameter by its `$key`. It prioritizes POST data, then GET data, and returns the `$default` value if the key is not found in either.
 * - `all(): array`: Returns a merged array containing all GET and POST parameters.
 * - `only(array $keys): array`: Returns an array containing only the request parameters specified in the `$keys` array.
 * - `except(array $keys): array`: Returns an array containing all request parameters except those specified in the `$keys` array.
 * - `has(string $key): bool`: Checks if a request parameter with the given `$key` exists in either the GET or POST data.
 * - `method(): string`: Returns the HTTP request method in uppercase (e.g., 'GET', 'POST').
 * - `isMethod(string $method): bool`: Checks if the request method matches the provided `$method` (case-insensitive).
 * - `file(string $key)`: Retrieves information about an uploaded file by its `$key` from the `$_FILES` array. Returns `null` if the file is not found.
 * - `uri(): string`: Returns the request URI, removing any query string parameters.
 * - `isAjax(): bool`: Determines if the request was made via AJAX by checking the 'HTTP_X_REQUESTED_WITH' server header for the value 'XMLHttpRequest'.
 */

namespace Velto\Core;

class Request
{
    protected array $get;
    protected array $post;
    protected array $files;
    protected array $server;

    public function post(array|string|null $keys = null): array|string|null
    {
        return $this->fetchFrom($this->post, $keys);
    }

    public function get(array|string|null $keys = null): array|string|null
    {
        return $this->fetchFrom($this->get, $keys);
    }

    private function fetchFrom(array $source, array|string|null $keys): array|string|null
    {
        if ($keys === null) {
            return $source;
        }

        if (is_string($keys)) {
            return $source[$keys] ?? null;
        }

        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $source[$key] ?? null;
        }
        return $result;
    }


    public function __construct()
    {
        $this->get    = $_GET ?? [];
        $this->post   = $_POST ?? [];
        $this->files  = $_FILES ?? [];
        $this->server = $_SERVER ?? [];
    }

    public function input(string $key, $default = null)
    {
        return $this->post[$key] ?? $this->get[$key] ?? $default;
    }

    public function all(): array
    {
        return array_merge($this->get, $this->post, $this->files);
    }

    public function only(array $keys): array
    {
        return array_filter($this->all(), fn($key) => in_array($key, $keys), ARRAY_FILTER_USE_KEY);
    }

    public function except(array $keys): array
    {
        return array_filter($this->all(), fn($key) => !in_array($key, $keys), ARRAY_FILTER_USE_KEY);
    }

    public function has(string $key): bool
    {
        return isset($this->get[$key]) || isset($this->post[$key]);
    }

    public function method(): string
    {
        return strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
    }

    public function isMethod(string $method): bool
    {
        return $this->method() === strtoupper($method);
    }

    public function file(string $key)
    {
        return $this->files[$key] ?? null;
    }

    public function uri(): string
    {
        $uri = $this->server['REQUEST_URI'] ?? '/';
        return strtok($uri, '?');
    }

    public function isAjax(): bool
    {
        return ($this->server['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
    }

}
