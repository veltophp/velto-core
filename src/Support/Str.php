<?php

namespace Velto\Core\Support;

class Str
{
    public static function random($length = 16)
    {
        return substr(str_shuffle(str_repeat('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', $length)), 0, $length);
    }

    public static function upper($value)
    {
        return mb_strtoupper($value, 'UTF-8');
    }

    public static function lower($value)
    {
        return mb_strtolower($value, 'UTF-8');
    }

    public static function slug($value, $separator = '-')
    {
        $value = preg_replace('/[^\p{L}\p{Nd}]+/u', $separator, $value);
        $value = trim($value, $separator);
        $value = mb_strtolower($value, 'UTF-8');
        return $value;
    }

    public static function limit($value, $limit = 100, $end = '...')
    {
        return mb_strwidth($value, 'UTF-8') <= $limit
            ? $value
            : rtrim(mb_strimwidth($value, 0, $limit, '', 'UTF-8')) . $end;
    }

}
