<?php

declare(strict_types=1);

namespace App\Http;

final class Request
{
    public static function isPost(): bool
    {
        return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
    }

    public static function input(string $key, string $default = ''): string
    {
        $value = $_POST[$key] ?? $_GET[$key] ?? $default;

        return is_string($value) ? trim($value) : $default;
    }

    /** True when the browser is loading this URL inside an <iframe>. */
    public static function isFramed(): bool
    {
        return ($_SERVER['HTTP_SEC_FETCH_DEST'] ?? '') === 'iframe';
    }

    /**
     * Public base URL of the app as the visitor reached it, e.g. https://xyz.trycloudflare.com.
     * Honours the proxy headers set by the tunnel so Gate-E redirects back to the same origin.
     */
    public static function baseUrl(): string
    {
        $scheme = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ((($_SERVER['HTTPS'] ?? '') === 'on') ? 'https' : 'http');
        $host   = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'] ?? 'localhost';

        return $scheme . '://' . $host;
    }
}
