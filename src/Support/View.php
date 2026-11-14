<?php

declare(strict_types=1);

namespace App\Support;

/** Renders a PHP template from /templates with the given variables in scope. */
final class View
{
    private const ROOT = __DIR__ . '/../../templates/';

    /** @param array<string, mixed> $data */
    public static function render(string $template, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        require self::ROOT . $template . '.php';
    }
}
