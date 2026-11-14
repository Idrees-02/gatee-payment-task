<?php

declare(strict_types=1);

namespace App\Database;

use PDO;

final class Connection
{
    public static function make(string $host, string $database, string $user, string $password): PDO
    {
        $pdo = new PDO("mysql:host={$host};dbname={$database};charset=utf8mb4", $user, $password, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        $pdo->exec("SET time_zone = '+03:00'");

        return $pdo;
    }
}
