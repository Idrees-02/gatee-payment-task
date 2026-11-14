<?php

declare(strict_types=1);

namespace App\Service;

final class CustomerDetails
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $mobileCode,
        public readonly string $mobile,
    ) {
    }
}
