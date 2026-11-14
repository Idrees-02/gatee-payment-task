<?php

declare(strict_types=1);

namespace App\Service;

use App\Gateway\GateEClient;
use App\Repository\PaymentRepository;
use App\Repository\TransactionRepository;

/**
 * Brings a local payment in line with Gate-E.
 *
 * The redirect query string and the callback body are only *hints* that something happened;
 * the truth is always fetched with payment/get. Safe to run any number of times.
 */
final class PaymentSyncService
{
    public const FROM_REDIRECT = 'redirect';
    public const FROM_WEBHOOK  = 'webhook';
    public const FROM_VIEW     = 'view';

    public function __construct(
        private readonly PaymentRepository $payments,
        private readonly TransactionRepository $transactions,
        private readonly GateEClient $gateway,
    ) {
    }

    /**
     * @param array<string, mixed> $payment local payment row
     * @return array{payment: array<string, mixed>, gateway: array<string, mixed>}
     */
    public function sync(array $payment, string $source): array
    {
        $remote = $this->gateway->getPayment((string) $payment['gate_payment_id']);
        $remoteTransactions = $remote['transaction'] ?? [];

        foreach ($remoteTransactions as $transaction) {
            $this->transactions->upsert(self::transactionRow((int) $payment['id'], $transaction, $source));
        }

        $status = self::resolveStatus(
            PaymentStatus::from($payment['status']),
            (string) ($remote['payment']['status'] ?? ''),
            $remoteTransactions,
            $source,
        );

        $this->payments->updateFromGateway(
            (int) $payment['id'],
            $status,
            (string) ($remote['payment']['status'] ?? ''),
            (float) ($remote['payment']['amount_paid'] ?? 0),
            $remote,
        );

        return ['payment' => $this->payments->find((int) $payment['id']), 'gateway' => $remote];
    }

    /**
     * Gate-E reports the payment as paid/unpaid; failures only show up as failed transactions.
     * A paid payment is never downgraded.
     *
     * @param list<array<string, mixed>> $transactions
     */
    private static function resolveStatus(PaymentStatus $current, string $remoteStatus, array $transactions, string $source): PaymentStatus
    {
        if ($current === PaymentStatus::Paid || $remoteStatus === 'paid') {
            return PaymentStatus::Paid;
        }

        $hasFailedAttempt = array_filter($transactions, static fn (array $t) => ($t['status'] ?? '') === 'failed') !== [];
        if ($hasFailedAttempt) {
            return PaymentStatus::Failed;
        }

        // Merely opening the details must not change an untouched payment.
        return $source === self::FROM_VIEW ? $current : PaymentStatus::Pending;
    }

    /**
     * @param array<string, mixed> $t a transaction object from payment/get
     * @return array<string, mixed>
     */
    private static function transactionRow(int $paymentId, array $t, string $source): array
    {
        $card = $t['card'] ?? [];
        $masked = (!empty($card['first_digits']) && !empty($card['last_digits']))
            ? $card['first_digits'] . '******' . $card['last_digits']
            : null;

        return [
            'payment_id'          => $paymentId,
            'gate_transaction_id' => (string) $t['id'],
            'reference_number'    => $t['reference_number'] ?? null,
            'status'              => (string) ($t['status'] ?? 'unknown'),
            'amount'              => (float) ($t['amount'] ?? 0),
            'paid_amount'         => (float) ($t['paid_amount'] ?? 0),
            'currency'            => (string) ($t['currency'] ?? 'BHD'),
            'response_code'       => $t['response_code'] ?? null,
            'response_message'    => $t['response_message'] ?? null,
            'card_brand'          => $card['brand'] ?? $card['type'] ?? null,
            'card_masked'         => $masked,
            'rrn'                 => $t['rrn'] ?? null,
            'authorization_code'  => $t['authorization_code'] ?? null,
            'gateway_name'        => $t['gateway']['name_en'] ?? null,
            'mode'                => $t['mode'] ?? null,
            'paid_at'             => $t['payment_date'] ?? null,
            'raw'                 => json_encode($t, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'synced_from'         => $source,
        ];
    }
}
