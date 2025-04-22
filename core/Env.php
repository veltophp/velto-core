<?php

namespace Velto\Core;

class Env
{
    public static function load(string $path = __DIR__ . '/../.env')
    {
        if (!file_exists($path)) return;

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) continue;

            if (strpos($line, '=') === false) continue;

            $parts = explode('=', $line, 2);
            if (count($parts) === 2) {
                [$name, $value] = array_map('trim', $parts);

                $value = trim($value, "\"'");

                putenv("$name=$value");
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }

    public static function get(string $key, $default = null)
    {
        return $_ENV[$key] ?? getenv($key) ?? $default;
    }

    public static function isDebug(): bool
    // Check the debug status || True or False 
    {
        $debug = strtolower(static::get('APP_DEBUG', 'false'));
        // dd("APP_DEBUG:", $debug, $debug === 'true');

        return $debug === 'true';
    }

}
