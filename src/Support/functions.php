<?php

declare(strict_types=1);

/*
 * Small global helpers used by templates. Kept deliberately few.
 */

/** Escape a value for HTML output. */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Format a money amount the way Bahraini dinars are written: three decimals. */
function money(float|string|null $amount, string $currency = 'BHD'): string
{
    return number_format((float) $amount, 3) . ' ' . $currency;
}

function format_date(?string $value): string
{
    return $value ? date('j M Y, H:i', strtotime($value)) : '—';
}
