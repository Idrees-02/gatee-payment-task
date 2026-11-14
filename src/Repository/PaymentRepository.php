<?php

declare(strict_types=1);

namespace App\Repository;

use App\Service\PaymentStatus;
use PDO;

final class PaymentRepository
{
    private const SELECT_WITH_CUSTOMER = '
        SELECT p.*, c.name AS customer_name, c.email AS customer_email, c.mobile_code, c.mobile
          FROM payments p
          JOIN customers c ON c.id = p.customer_id';

    public function __construct(private readonly PDO $db)
    {
    }

    /** @param array<string, mixed> $row */
    public function create(array $row): int
    {
        $columns = array_keys($row);
        $sql = sprintf(
            'INSERT INTO payments (%s) VALUES (%s)',
            implode(', ', $columns),
            implode(', ', array_map(static fn ($c) => ':' . $c, $columns)),
        );
        $this->db->prepare($sql)->execute($row);

        return (int) $this->db->lastInsertId();
    }

    /** @param array<string, mixed> $createResponse */
    public function attachGatewayPayment(int $id, string $gatePaymentId, string $link, array $createResponse): void
    {
        $this->db
            ->prepare('UPDATE payments SET gate_payment_id = ?, payment_link = ?, create_response = ? WHERE id = ?')
            ->execute([$gatePaymentId, $link, self::json($createResponse), $id]);
    }

    /** @param array<string, mixed>|null $response */
    public function markError(int $id, ?array $response): void
    {
        $this->db
            ->prepare('UPDATE payments SET status = ?, create_response = ? WHERE id = ?')
            ->execute([PaymentStatus::Error->value, $response ? self::json($response) : null, $id]);
    }

    /** @param array<string, mixed> $gatewayResponse */
    public function updateFromGateway(int $id, PaymentStatus $status, string $gatewayStatus, float $amountPaid, array $gatewayResponse): void
    {
        $this->db
            ->prepare(
                'UPDATE payments
                    SET status = :status,
                        gateway_status = :gateway_status,
                        amount_paid = :amount_paid,
                        last_response = :response,
                        completed_at = IF(:is_final AND completed_at IS NULL, NOW(), completed_at)
                  WHERE id = :id'
            )
            ->execute([
                'status'         => $status->value,
                'gateway_status' => $gatewayStatus,
                'amount_paid'    => $amountPaid,
                'response'       => self::json($gatewayResponse),
                'is_final'       => (int) $status->isFinal(),
                'id'             => $id,
            ]);
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        return $this->first(self::SELECT_WITH_CUSTOMER . ' WHERE p.id = ?', [$id]);
    }

    /** @return array<string, mixed>|null */
    public function findByGatePaymentId(string $gatePaymentId): ?array
    {
        return $this->first(self::SELECT_WITH_CUSTOMER . ' WHERE p.gate_payment_id = ?', [$gatePaymentId]);
    }

    /** @return array<string, mixed>|null */
    public function findByOrderCode(string $orderCode): ?array
    {
        return $this->first(self::SELECT_WITH_CUSTOMER . ' WHERE p.order_code = ?', [$orderCode]);
    }

    /** @return list<array<string, mixed>> */
    public function latest(int $limit = 200): array
    {
        $stmt = $this->db->prepare(
            self::SELECT_WITH_CUSTOMER . ' ORDER BY p.created_at DESC, p.id DESC LIMIT ' . max(1, $limit)
        );
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /** @return array{total: int, paid: int, failed: int, open: int, collected: float} */
    public function summary(): array
    {
        $row = $this->db->query(
            "SELECT COUNT(*)                                              AS total,
                    COALESCE(SUM(status = 'paid'), 0)                     AS paid,
                    COALESCE(SUM(status = 'failed'), 0)                   AS failed,
                    COALESCE(SUM(status IN ('initiated', 'pending')), 0)  AS open,
                    COALESCE(SUM(amount_paid), 0)                         AS collected
               FROM payments"
        )->fetch();

        return [
            'total'     => (int) $row['total'],
            'paid'      => (int) $row['paid'],
            'failed'    => (int) $row['failed'],
            'open'      => (int) $row['open'],
            'collected' => (float) $row['collected'],
        ];
    }

    /**
     * @param list<mixed> $params
     * @return array<string, mixed>|null
     */
    private function first(string $sql, array $params): ?array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetch() ?: null;
    }

    /** @param array<string, mixed> $value */
    private static function json(array $value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }
}
