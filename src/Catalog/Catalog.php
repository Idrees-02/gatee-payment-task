<?php

declare(strict_types=1);

namespace App\Catalog;

/** The shop sells a single product; the catalogue is the one place it is defined. */
final class Catalog
{
    public function headphones(): Product
    {
        return new Product(
            sku: 'HALO-ONE',
            name: 'Halo One',
            summary: 'Over-ear wireless headphones with adaptive noise cancelling and a 40-hour battery.',
            unitPrice: 25.000,
            maxQuantity: 5,
            finishes: [
                'tide'  => new Finish('tide', 'Tide', 'Deep teal with sea-glass cushions', '#06525C', '#9ED3CB'),
                'sand'  => new Finish('sand', 'Sand', 'Warm stone with oat cushions', '#B7A58A', '#EFE6D6'),
                'night' => new Finish('night', 'Night', 'Ink blue with graphite cushions', '#1C2A3A', '#5D6B7A'),
            ],
        );
    }
}
