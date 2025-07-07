<?php

namespace Velto\Core\Support;

use InvalidArgumentException;

class Uvid
{
    /**
     * Generate default UVID (hex + dash-separated)
     */
    public static function generate(int $length = 32): string
    {
        if (!in_array($length, [6, 8, 12, 32])) {
            throw new InvalidArgumentException('Length must be one of: 6, 8, 12, 32');
        }

        $bytes = random_bytes($length / 2);
        $hex = bin2hex($bytes);

        $interval = ($length === 6 || $length === 8) ? 2 : 4;
        return implode('-', str_split($hex, $interval));
    }

    /**
     * Generate base62 ID (shorter than hex)
     */
    public static function base62(int $length = 10): string
    {
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $id = '';
        for ($i = 0; $i < $length; $i++) {
            $id .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $id;
    }

    /**
     * Generate sortable ID (timestamp + random)
     * e.g. 202507021420-A3B2
     */
    public static function sortable(): string
    {
        $prefix = date('YmdHi'); // Y-m-d H:i
        $random = strtoupper(bin2hex(random_bytes(2))); // 4 chars
        return $prefix . '-' . $random;
    }

    /**
     * Generate slug with prefix (e.g. post-A9F3)
     */
    public static function slug(string $prefix = 'id'): string
    {
        $random = strtoupper(bin2hex(random_bytes(2))); // 4 chars
        return $prefix . '-' . $random;
    }

    /**
     * Generate NanoID-style (base62, longer & safer)
     */
    public static function nano(int $length = 21): string
    {
        return self::base62($length); // simple replacement, not cryptographic like real NanoID
    }

    /**
     * Generate ULID (sortable + unique) — RFC 4122 alternative
     */
    public static function ulid(): string
    {
        // Compatible fallback if `ulid/ulid` package not used
        $time = base_convert((int)(microtime(true) * 1000), 10, 32);
        $random = self::base62(16);
        return strtoupper($time . $random);
    }
}
