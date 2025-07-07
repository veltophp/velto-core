<?php

namespace Velto\Core\Request;

class Request
{
    protected array $get;
    protected array $post;
    protected array $files;
    protected array $server;


    public function __construct()
    {
        $this->get    = $_GET ?? [];
        $this->post   = $_POST ?? [];
        $this->files  = $_FILES ?? [];
        $this->server = $_SERVER ?? [];
    }

    public function post(array|string|null $keys = null): array|string|null
    {
        return $this->fetchFrom($this->post, $keys);
    }

    public function get(array|string|null $keys = null): array|string|null
    {
        return $this->fetchFrom($this->get, $keys);
    }

    public function __get($key)
    {
        return $this->input($key);
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

    public function toArray(bool $includeServer = false): array
    {
        $base = [
            'get'    => $this->get,
            'post'   => $this->post,
            'files'  => $this->files,
        ];

        if ($includeServer) {
            $base['server'] = $this->server;
        }

        return $base;
    }

}
