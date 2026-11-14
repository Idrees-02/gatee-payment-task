<?php

declare(strict_types=1);

namespace App\Http;

/** Thin wrapper over the PHP session, with the few things the app keeps in it. */
final class Session
{
    private const ORDERS = 'orders';

    public static function start(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Lax']);
        }
    }

    public static function get(string $key): mixed
    {
        self::start();

        return $_SESSION[$key] ?? null;
    }

    public static function set(string $key, mixed $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    /** Remember that this browser started the order, so only it may poll the order's status. */
    public static function rememberOrder(string $orderCode): void
    {
        $orders = self::get(self::ORDERS) ?? [];
        $orders[] = $orderCode;
        self::set(self::ORDERS, array_slice(array_unique($orders), -20));
    }

    public static function ownsOrder(string $orderCode): bool
    {
        return in_array($orderCode, self::get(self::ORDERS) ?? [], true);
    }
}
