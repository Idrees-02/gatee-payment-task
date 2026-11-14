<?php

declare(strict_types=1);

/*
 * POST /api/create-payment.php  (AJAX from the checkout form)
 * Validates the form, stores the customer + an "initiated" payment, creates it at Gate-E,
 * and returns the hosted payment page URL for the iframe.
 */

use App\Gateway\GateEException;
use App\Http\Csrf;
use App\Http\JsonResponse;
use App\Http\Request;
use App\Http\Session;
use App\Service\CheckoutRequest;
use App\Service\ValidationException;

$app = require __DIR__ . '/../../src/bootstrap.php';

if (!Request::isPost()) {
    JsonResponse::error('Use POST.', 405);
}
if (!Csrf::isValid(Request::input('csrf'))) {
    JsonResponse::error('Your session expired. Refresh the page and try again.', 419);
}

$product = $app->catalog()->headphones();

try {
    $request = CheckoutRequest::fromInput($_POST, $product);
    $payment = $app->checkout()->start($request, $product, Request::baseUrl());
} catch (ValidationException $e) {
    JsonResponse::error($e->getMessage(), 422, $e->errors);
} catch (GateEException $e) {
    error_log('[create-payment] ' . $e->getMessage());
    JsonResponse::error('The payment gateway did not accept this payment. Try again in a minute.', 502);
} catch (Throwable $e) {
    error_log('[create-payment] ' . $e);
    JsonResponse::error('Something went wrong on our side. Try again in a minute.', 500);
}

Session::rememberOrder($payment['order_code']);

JsonResponse::send([
    'ok'          => true,
    'orderCode'   => $payment['order_code'],
    'amount'      => money($payment['amount'], $payment['currency']),
    'paymentUrl'  => $payment['payment_link'],
]);
