<?php

namespace Velto\Core\App;

use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirect;
use Closure;

class RedirectResponse extends SymfonyRedirect
{
    /**
     * Macro registry.
     */
    protected static array $macros = [];

    /**
     * Register a macro.
     */
    public static function macro(string $name, Closure $callback): void
    {
        static::$macros[$name] = $callback;
    }

    /**
     * Call macro method if registered.
     */
    public function __call(string $method, array $parameters)
    {
        if (isset(static::$macros[$method])) {
            return static::$macros[$method]->call($this, ...$parameters);
        }

        throw new \BadMethodCallException("Method [$method] does not exist.");
    }

    /**
     * Change the target URL to a named route.
     */
    public function route(string $name, array $params = []): static
    {
        $this->targetUrl = route($name, $params);
        return $this;
    }

    /**
     * Redirect to the previous page.
     */
    public function back(): static
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        $this->targetUrl = $referer;
        return $this;
    }

    /**
     * Send the redirect response (manual).
     */
    public function send(bool $flush = true): static
    {
        parent::send($flush);
        exit;
    }

}
