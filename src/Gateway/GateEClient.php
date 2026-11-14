<?php

declare(strict_types=1);

namespace App\Gateway;

/**
 * HTTP client for the Gate-E API.
 *
 * Every operation is a JSON POST to the same endpoint; the `api.resource` and `api.action`
 * fields select what it does. Credentials travel in the body, never to the browser.
 */
final class GateEClient
{
    private const API_VERSION = '01';

    public function __construct(
        private readonly string $endpoint,
        private readonly string $apiUser,
        private readonly string $apiKey,
        private readonly int $timeoutSeconds = 30,
    ) {
    }

    /**
     * payment / create
     *
     * @param array<string, mixed> $payment
     * @param list<array<string, mixed>> $items
     * @return array{id: string, link: string, url: string}
     */
    public function createPayment(array $payment, array $items = []): array
    {
        $body = ['payment' => $payment];
        if ($items !== []) {
            $body['payment_details'] = $items;
        }

        $response = $this->call('payment', 'create', $body);

        return $response['data']['payment'] ?? throw new GateEException('Gate-E did not return a payment.', $response);
    }

    /**
     * payment / get — the payment, its line items and every transaction attempted against it.
     *
     * @return array{payment: array<string, mixed>, payment_details: list<array<string, mixed>>, transaction: list<array<string, mixed>>}
     */
    public function getPayment(string $gatePaymentId): array
    {
        $response = $this->call('payment', 'get', ['payment' => ['id' => $gatePaymentId]]);

        return $response['data'] ?? throw new GateEException('Gate-E did not return payment data.', $response);
    }

    /** @return list<array<string, mixed>> */
    public function listGateways(): array
    {
        return $this->call('gateway', 'list')['data']['gateway'] ?? [];
    }

    /**
     * @param array<string, mixed> $body
     * @return array<string, mixed>
     */
    private function call(string $resource, string $action, array $body = []): array
    {
        $payload = ['api' => [
            'version'  => self::API_VERSION,
            'user'     => $this->apiUser,
            'key'      => $this->apiKey,
            'resource' => $resource,
            'action'   => $action,
            'response' => ['type' => 'json'],
        ]] + $body;

        $response = $this->post(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

        if ((int) ($response['status'] ?? 0) !== 1) {
            $code = $response['status_code'] ?? 'unknown';
            throw new GateEException("Gate-E {$resource}/{$action} failed [{$code}]: " . self::messageOf($response), $response);
        }

        return $response;
    }

    /** @return array<string, mixed> */
    private function post(string $json): array
    {
        $curl = curl_init($this->endpoint);
        curl_setopt_array($curl, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $json,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Accept: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => $this->timeoutSeconds,
        ]);

        $raw = curl_exec($curl);
        $error = curl_error($curl);
        $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($raw === false) {
            throw new GateEException("Gate-E is unreachable: {$error}");
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            throw new GateEException("Gate-E returned an unreadable response (HTTP {$httpCode}).");
        }

        return $decoded;
    }

    /** @param array<string, mixed> $response */
    private static function messageOf(array $response): string
    {
        $message = $response['message'] ?? 'no message';
        if (is_array($message)) {
            array_walk_recursive($message, static function (&$v): void { $v = (string) $v; });
            $message = implode('; ', array_merge(...array_map(
                static fn ($m) => is_array($m) ? array_values($m) : [$m],
                array_values($message),
            )));
        }

        return (string) $message;
    }
}
