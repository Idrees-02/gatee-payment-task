<?php

declare(strict_types=1);

/*
 * Application bootstrap: PSR-4 style autoloading for the App\ namespace,
 * timezone, and a tiny service container shared by every entry point.
 */

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (str_starts_with($class, $prefix)) {
        $file = __DIR__ . '/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (is_file($file)) {
            require $file;
        }
    }
});

date_default_timezone_set('Asia/Bahrain');

require __DIR__ . '/Support/functions.php';

return new App\Container();
