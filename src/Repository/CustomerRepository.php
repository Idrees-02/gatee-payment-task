<?php

declare(strict_types=1);

namespace App\Repository;

use App\Service\CustomerDetails;
use PDO;

final class CustomerRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function create(CustomerDetails $customer): int
    {
        $this->db
            ->prepare('INSERT INTO customers (name, email, mobile_code, mobile) VALUES (:name, :email, :mobile_code, :mobile)')
            ->execute([
                'name'        => $customer->name,
                'email'       => $customer->email,
                'mobile_code' => $customer->mobileCode,
                'mobile'      => $customer->mobile,
            ]);

        return (int) $this->db->lastInsertId();
    }
}
