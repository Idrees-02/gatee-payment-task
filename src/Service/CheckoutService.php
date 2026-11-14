<?php

declare(strict_types=1);

namespace App\Service;

use App\Catalog\Product;
use App\Gateway\GateEClient;
use App\Gateway\GateEException;
use App\Repository\CustomerRepository;
use App\Repository\PaymentRepository;
use PDO;

/**
 * Starts a purchase: stores the customer and an "initiated" payment, then creates
 * the matching payment at Gate-E and returns the hosted payment page link.
 */
final class CheckoutService
{
    public function __construct(
        private readonly PDO $db,
        private readonly CustomerRepository $customers,
        private readonly PaymentRepository $payments,
        private readonly GateEClient $gateway,
        private readonly string $currency,
        private readonly ?string $gatewayId,
    ) {
    }

    /**
     * @param string $baseUrl public origin Gate-E should send the customer and the callback back to
     * @return array<string, mixed> the stored payment row
     * @throws GateEException when the gateway refuses the payment (the row is kept with status "error")
     */
    public function start(CheckoutRequest $request, Product $product, string $baseUrl): array
    {
        $paymentId = $this->storeInitiatedPayment($request, $product);
        $payment = $this->payments->find($paymentId);

        try {
            $created = $this->gateway->createPayment(
                $this->paymentPayload($payment, $request, $baseUrl),
                [$this->lineItem($payment)],
            );
        } catch (GateEException $e) {
            $this->payments->markError($paymentId, $e->response);
            throw $e;
        }

        $this->payments->attachGatewayPayment($paymentId, (string) $created['id'], (string) $created['link'], $created);

        return $this->payments->find($paymentId);
    }

    /** Customer and payment are written together, or not at all. */
    private function storeInitiatedPayment(CheckoutRequest $request, Product $product): int
    {
        $this->db->beginTransaction();
        try {
            $customerId = $this->customers->create($request->customer);
            $paymentId = $this->payments->create([
                'customer_id'  => $customerId,
                'order_code'   => self::newOrderCode(),
                'product_sku'  => $product->sku,
                'product_name' => $product->name,
                'finish'       => $request->finish->name,
                'quantity'     => $request->quantity,
                'unit_price'   => $product->unitPrice,
                'amount'       => $product->priceFor($request->quantity),
                'currency'     => $this->currency,
                'status'       => PaymentStatus::Initiated->value,
            ]);
            $this->db->commit();

            return $paymentId;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Field names follow the Gate-E API guide (including its spelling of "referance").
     *
     * @param array<string, mixed> $payment
     * @return array<string, mixed>
     */
    private function paymentPayload(array $payment, CheckoutRequest $request, string $baseUrl): array
    {
        $customer = $request->customer;

        $payload = [
            'type'        => 'pay',
            'code'        => $payment['order_code'],
            'amount'      => (float) $payment['amount'],
            'currency'    => $payment['currency'],
            'description' => "{$payment['product_name']} ({$payment['finish']})",
            'note'        => "Order {$payment['order_code']}",
            'referance'   => $payment['order_code'],
            'language'    => 'en',
            'source'      => 'Halo Audio web shop',
            'field1'      => (string) $payment['id'],
            'client' => [
                'name'        => $customer->name,
                'email'       => $customer->email,
                'mobile_code' => $customer->mobileCode,
                'mobile'      => $customer->mobile,
                'country'     => 'BH',
            ],
            'notification' => ['email_send' => 0, 'sms_send' => 0, 'whatsapp_send' => 0],
            'settings' => [
                'redirect_url'      => $baseUrl . '/callback.php',
                'callback_url'      => $baseUrl . '/webhook.php',
                'create_code'       => 0,
                'calculate_payment' => 0,
            ],
        ];

        if ($this->gatewayId !== null) {
            $payload['gateway'] = ['id' => $this->gatewayId];
        }

        return $payload;
    }

    /**
     * @param array<string, mixed> $payment
     * @return array<string, mixed>
     */
    private function lineItem(array $payment): array
    {
        return [
            'name'            => "{$payment['product_name']} ({$payment['finish']})",
            'amount'          => (float) $payment['amount'],
            'amount_unit'     => (float) $payment['unit_price'],
            'amount_subtotal' => (float) $payment['amount'],
            'quantity'        => (int) $payment['quantity'],
        ];
    }

    private static function newOrderCode(): string
    {
        return 'HALO-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
    }
}
