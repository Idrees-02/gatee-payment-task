<?php

declare(strict_types=1);

namespace App\Gateway;

/** Thrown when Gate-E cannot be reached or answers with status != 1. */
final class GateEException extends \RuntimeException
{
    /** @param array<string, mixed>|null $response the decoded API response, when there was one */
    public function __construct(string $message, public readonly ?array $response = null)
    {
        parent::__construct($message);
    }
}
