<?php

declare(strict_types=1);

/*
 * GET /api/payment.php?id=…   (AJAX from the "View" button)
 * Returns the payment as stored in our database next to the live record from Gate-E.
 */

use App\Http\JsonResponse;
use App\Service\PaymentSyncService;

$app = require __DIR__ . '/../../src/bootstrap.php';

$payment = $app->payments()->find((int) ($_GET['id'] ?? 0));
if ($payment === null) {
    JsonResponse::error('Payment not found.', 404);
}

$gateway = null;
$gatewayError = null;

if ($payment['gate_payment_id'] === null) {
    $gatewayError = 'This payment was never created at Gate-E.';
} else {
    try {
        ['payment' => $payment, 'gateway' => $gateway] = $app->paymentSync()->sync($payment, PaymentSyncService::FROM_VIEW);
    } catch (Throwable $e) {
        $gatewayError = 'Gate-E could not be reached: ' . $e->getMessage();
    }
}

unset($payment['create_response'], $payment['last_response']);

JsonResponse::send([
    'ok'           => true,
    'database'     => ['payment' => $payment, 'transactions' => $app->transactions()->forPayment((int) $payment['id'])],
    'gateway'      => $gateway,
    'gatewayError' => $gatewayError,
]);
