<?php

declare(strict_types=1);

/*
 * POST /webhook.php?payment_id=…&payment_code=…   (settings.callback_url)
 * Server-to-server notice from Gate-E after every transaction. The body is only a trigger:
 * we re-read the payment from Gate-E, then answer HTTP 200 echoing the code_id we received.
 */

use App\Http\JsonResponse;
use App\Http\Request;
use App\Service\PaymentSyncService;

$app = require __DIR__ . '/../src/bootstrap.php';

$body = json_decode(file_get_contents('php://input') ?: '', true) ?: $_POST;
error_log('[webhook] ' . json_encode(['query' => $_GET, 'body' => $body]));

$gatePaymentId = preg_replace('/\D/', '', Request::input('payment_id') ?: (string) ($body['payment_id'] ?? ''));

if ($gatePaymentId !== '' && ($payment = $app->payments()->findByGatePaymentId($gatePaymentId))) {
    try {
        $app->paymentSync()->sync($payment, PaymentSyncService::FROM_WEBHOOK);
    } catch (Throwable $e) {
        error_log('[webhook] sync failed: ' . $e->getMessage());
    }
}

JsonResponse::send(['status' => 1, 'code_id' => $body['code_id'] ?? null]);
