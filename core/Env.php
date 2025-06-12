<?php

/**
 * Class Env in namespace Velto\Core.
 *
 * Structure: Provides static methods for loading environment variables from a `.env` file and accessing them.
 *
 * How it works:
 * - `load(string $path = __DIR__ . '/../.env')`: Reads the `.env` file (or a specified path).
 * - Ignores empty lines and lines starting with '#'.
 * - Parses each line for key-value pairs separated by '='.
 * - Trims whitespace and surrounding quotes from keys and values.
 * - Sets the environment variable using `putenv()`, `$_ENV`, and `$_SERVER` superglobals.
 *
 * - `get(string $key, $default = null)`: Retrieves the value of an environment variable by its key.
 * - Checks `$_ENV` first, then `getenv()`, and returns the `$default` value if the key is not found.
 *
 * - `isDebug(): bool`: Checks the value of the `APP_DEBUG` environment variable.
 * - Returns `true` if `APP_DEBUG` is set to 'true' (case-insensitive), `false` otherwise (defaults to false).
 */

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
    {
        $debug = strtolower(static::get('APP_DEBUG', 'false'));

        return $debug === 'true';
    }

}
