<?php

declare(strict_types=1);

namespace App\Catalog;

/** An item for sale. Prices live on the server and are never taken from the browser. */
final class Product
{
    /**
     * @param array<string, Finish> $finishes keyed by finish id
     */
    public function __construct(
        public readonly string $sku,
        public readonly string $name,
        public readonly string $summary,
        public readonly float $unitPrice,
        public readonly int $maxQuantity,
        public readonly array $finishes,
    ) {
    }

    public function finish(string $id): ?Finish
    {
        return $this->finishes[$id] ?? null;
    }

    public function defaultFinish(): Finish
    {
        return $this->finishes[array_key_first($this->finishes)];
    }

    public function priceFor(int $quantity): float
    {
        return round($this->unitPrice * $quantity, 3);
    }
}
