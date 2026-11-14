<?php

declare(strict_types=1);

namespace App\Http;

/** Per-session token that protects the checkout form from cross-site submissions. */
final class Csrf
{
    private const KEY = 'csrf_token';

    public static function token(): string
    {
        $token = Session::get(self::KEY);
        if (!is_string($token)) {
            $token = bin2hex(random_bytes(32));
            Session::set(self::KEY, $token);
        }

        return $token;
    }

    public static function isValid(string $token): bool
    {
        $expected = Session::get(self::KEY);

        return is_string($expected) && hash_equals($expected, $token);
    }
}
