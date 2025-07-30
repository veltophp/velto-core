<?php

namespace Velto\Core\Support;

class Hash
{
    public static function make(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    public static function check(string $plain, string $hashed): bool
    {

        if (!password_get_info($hashed)['algo']) {
            return false;
        }

        return password_verify($plain, $hashed);
    }

    public static function needsRehash(string $hashed): bool
    {
        return password_needs_rehash($hashed, PASSWORD_BCRYPT);
    }
}
