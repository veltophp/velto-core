<?php

namespace Velto\Core\Session;

class Session
{

    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {

            $sessionPath = BASE_PATH . '/storage/sessions';

            if (!is_dir($sessionPath)) {
                mkdir($sessionPath, 0777, true);
            }

            $lifetime = getenv('SESSION_LIFETIME') ?: 86400;

            ini_set('session.save_handler', 'files');
            session_save_path($sessionPath);
            ini_set('session.gc_maxlifetime', $lifetime);

            session_start([
                'cookie_lifetime' => $lifetime,
                'cookie_httponly' => true,
                'cookie_secure' => isset($_SERVER['HTTPS']),
                'use_strict_mode' => true,
                'cookie_samesite' => 'Lax'
            ]);
        }
    }

    public static function regenerate(bool $deleteOldSession = true): bool
    {
        self::start();

        if (session_status() !== PHP_SESSION_ACTIVE) {
            return false;
        }

        $result = session_regenerate_id($deleteOldSession);

        if ($result === false) {
            return false;
        }

        return true;
    }

    public static function updateActivity(): void
    {
        self::start();
        $_SESSION['last_activity'] = time();
    }

    public static function isIdle(int $timeout = 600): bool
    {
        self::start();
        return isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout;
    }

    protected static function getValueByDot(array $array, string $key, $default = null)
    {
        if (empty($key)) return $default;
        if (isset($array[$key])) return $array[$key];

        foreach (explode('.', $key) as $segment) {
            if (is_array($array) && array_key_exists($segment, $array)) {
                $array = $array[$segment];
            } else {
                return $default;
            }
        }

        return $array;
    }

    protected static function setValueByDot(array &$array, string $key, $value): void
    {
        if (empty($key)) return;

        $pos = strpos($key, '.');

        if ($pos === false) {
            $array[$key] = $value;
            return;
        }

        $firstKey = substr($key, 0, $pos);
        $remainingKey = substr($key, $pos + 1);

        // Buat nested jika belum ada
        if (!isset($array[$firstKey]) || !is_array($array[$firstKey])) {
            $array[$firstKey] = [];
        }

        $array[$firstKey][$remainingKey] = $value;
    }


    protected static function forgetByDot(array &$array, string $key): void
    {
        if (empty($key)) return;

        $pos = strpos($key, '.');

        if ($pos === false) {
            unset($array[$key]);
            return;
        }

        $firstKey = substr($key, 0, $pos);
        $remainingKey = substr($key, $pos + 1);

        if (!isset($array[$firstKey]) || !is_array($array[$firstKey])) {
            return;
        }

        unset($array[$firstKey][$remainingKey]);
    }


    public static function set(string $key, $value): void
    {
        self::start();
        self::setValueByDot($_SESSION, $key, $value);
    }

    public static function get(string $key, $default = null)
    {
        self::start();
        return self::getValueByDot($_SESSION, $key, $default);
    }

    public static function has(string $key): bool
    {
        self::start();
        $value = self::getValueByDot($_SESSION, $key, null);
        return $value !== null;
    }

    public static function forget(string $key): void
    {
        self::start();
        self::forgetByDot($_SESSION, $key);
    }

    public static function destroy(): void
    {
        self::start();

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'], $params['secure'], $params['httponly']
            );
        }

        session_destroy();
        session_write_close();
        setcookie(session_name(), '', 0, '/');
    }

    public static function flash(string $key, $value): void
    {
        self::set('_flash.' . $key, $value);
    }

    public static function getFlash(string $key)
    {
        $value = self::get('_flash.' . $key);
        self::forget('_flash.' . $key);
        return $value;
    }

    public static function isExpired(int $timeout = 86400): bool
    {
        self::start();
        $startTime = $_SESSION['login_time'] ?? null;
        return !$startTime || (time() - $startTime) > $timeout;
    }

    public static function remove(string $key): void
    {
        self::start();

        if (strpos($key, '.') !== false) {
            $keys = explode('.', $key);
            $temp = &$_SESSION;

            foreach ($keys as $index => $segment) {
                if (isset($temp[$segment])) {
                    if ($index === count($keys) - 1) {
                        unset($temp[$segment]);
                        return;
                    }
                    $temp = &$temp[$segment];
                } else {
                    return;
                }
            }
        } else {
            if (isset($_SESSION[$key])) {
                unset($_SESSION[$key]);
            }
        }
    }

    public static function flush(): void
    {
        self::start();
        $_SESSION = [];
    }

    public static function all(): array
    {
        self::start();
        return $_SESSION;
    }

}