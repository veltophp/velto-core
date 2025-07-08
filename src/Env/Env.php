<?php

namespace Velto\Core\Env;

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
        $value = $_ENV[$key] ?? getenv($key) ?? $default;

        if (is_string($value)) {
            $value = strtolower(trim($value));

            // dd($value);

            return match($value) {
                'true' => true,
                'false' => false,
                default => $value,
            };
        }

        return $value;
    }

    public static function isDebug(): bool
    {
        return static::get('APP_DEBUG', false, true) === true;
    }

}
