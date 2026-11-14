<?php

declare(strict_types=1);

namespace App\Support;

/** Read configuration from environment variables (docker-compose injects .env). */
final class Env
{
    public static function get(string $key, ?string $default = null): ?string
    {
        $value = getenv($key);

        return ($value === false || $value === '') ? $default : $value;
    }

    public static function require(string $key): string
    {
        return self::get($key) ?? throw new \RuntimeException("Missing required environment variable: {$key}");
    }
}
