<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

final class TransactionRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    /**
     * Insert a transaction, or refresh it when Gate-E reports it again.
     * Keyed on Gate-E's transaction id, so redirect, webhook and modal syncs never duplicate rows.
     *
     * @param array<string, mixed> $row
     */
    public function upsert(array $row): void
    {
        $columns = array_keys($row);
        $updates = array_filter($columns, static fn ($c) => !in_array($c, ['payment_id', 'gate_transaction_id', 'synced_from'], true));

        $sql = sprintf(
            'INSERT INTO transactions (%s) VALUES (%s) ON DUPLICATE KEY UPDATE %s',
            implode(', ', $columns),
            implode(', ', array_map(static fn ($c) => ':' . $c, $columns)),
            implode(', ', array_map(static fn ($c) => "{$c} = VALUES({$c})", $updates)),
        );
        $this->db->prepare($sql)->execute($row);
    }

    /** @return list<array<string, mixed>> */
    public function forPayment(int $paymentId): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, gate_transaction_id, reference_number, status, amount, paid_amount, currency,
                    response_code, response_message, card_brand, card_masked, rrn, authorization_code,
                    gateway_name, mode, paid_at, synced_from, created_at
               FROM transactions
              WHERE payment_id = ?
           ORDER BY paid_at DESC, id DESC'
        );
        $stmt->execute([$paymentId]);

        return $stmt->fetchAll();
    }
}
