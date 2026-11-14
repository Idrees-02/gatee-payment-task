<?php

declare(strict_types=1);

namespace App\Catalog;

/** A colour option of a product, with the swatch colours used to draw it. */
final class Finish
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $description,
        public readonly string $shell,
        public readonly string $cushion,
    ) {
    }
}
