<?php

declare(strict_types=1);

/*
 * GET /callback.php?payment_id=…&payment_code=…   (settings.redirect_url)
 * Gate-E sends the customer here when the payment page finishes. We confirm the result
 * with payment/get, store it, and show the payment and its transactions.
 */

use App\Http\Request;
use App\Service\PaymentStatus;
use App\Service\PaymentSyncService;
use App\Support\View;

$app = require __DIR__ . '/../src/bootstrap.php';

// The gateway page lives in our iframe, so its redirect first lands *inside* the frame.
// Answer with a stub that reloads this same URL in the top window; that load does the work.
if (Request::isFramed()) {
    View::render('pages/frame-exit');
    exit;
}

$gatePaymentId = preg_replace('/\D/', '', Request::input('payment_id'));
$payment = $gatePaymentId !== '' ? $app->payments()->findByGatePaymentId($gatePaymentId) : null;
$error = null;

if ($payment === null) {
    http_response_code(404);
    $error = 'We could not find this payment. Open it from the payments list instead.';
} else {
    try {
        $payment = $app->paymentSync()->sync($payment, PaymentSyncService::FROM_REDIRECT)['payment'];
    } catch (Throwable $e) {
        error_log('[callback] ' . $e->getMessage());
        $error = 'Gate-E did not confirm the result yet. The status below may be out of date; refresh in a moment.';
    }
}

View::render('partials/head', ['title' => 'Payment result', 'page' => 'page-app', 'active' => '']);
View::render('pages/result', [
    'payment'      => $payment,
    'status'       => $payment ? PaymentStatus::from($payment['status']) : PaymentStatus::Error,
    'transactions' => $payment ? $app->transactions()->forPayment((int) $payment['id']) : [],
    'error'        => $error,
]);
View::render('partials/foot', ['scripts' => []]);
