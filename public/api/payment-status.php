<?php

declare(strict_types=1);

/*
 * GET /api/payment-status.php?order=…   (polled by the checkout page while the iframe is open)
 *
 * Why: when the shop runs on http://localhost, Chrome's Local Network Access rules stop the
 * public Gate-E page from redirecting its iframe back to localhost. The checkout page therefore
 * asks us whether Gate-E has recorded a result, and moves the visitor to the callback page itself.
 * On a public HTTPS domain the gateway's own redirect also works; both paths end in callback.php.
 */

use App\Http\JsonResponse;
use App\Http\Request;
use App\Http\Session;
use App\Service\PaymentStatus;
use App\Service\PaymentSyncService;

$app = require __DIR__ . '/../../src/bootstrap.php';

$orderCode = Request::input('order');
if (!Session::ownsOrder($orderCode)) {
    JsonResponse::error('Unknown order.', 404);
}

$payment = $app->payments()->findByOrderCode($orderCode);
if ($payment === null || $payment['gate_payment_id'] === null) {
    JsonResponse::error('Unknown order.', 404);
}

try {
    $payment = $app->paymentSync()->sync($payment, PaymentSyncService::FROM_VIEW)['payment'];
} catch (Throwable $e) {
    JsonResponse::send(['ok' => true, 'finished' => false]); // try again on the next poll
}

$status = PaymentStatus::from($payment['status']);

JsonResponse::send([
    'ok'          => true,
    'finished'    => $status->isFinal(),
    'status'      => $status->value,
    'callbackUrl' => '/callback.php?' . http_build_query([
        'payment_id'   => $payment['gate_payment_id'],
        'payment_code' => $payment['order_code'],
    ]),
]);
