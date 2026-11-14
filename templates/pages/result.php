<?php
/**
 * Shown after Gate-E redirects back.
 * @var array<string, mixed>|null $payment
 * @var App\Service\PaymentStatus $status
 * @var list<array<string, mixed>> $transactions
 * @var string|null $error
 */
use App\Service\PaymentStatus;
use App\Support\View;

$firstName = $payment ? explode(' ', $payment['customer_name'])[0] : '';
[$heading, $message] = match ($status) {
    PaymentStatus::Paid   => ["Thank you, {$firstName}", "Your payment went through and your Halo One is on its way. We'll email the receipt to {$payment['customer_email']}."],
    PaymentStatus::Failed => ['Your card was declined', 'Nothing was charged. You can try again with the same or a different card.'],
    PaymentStatus::Error  => ['We could not load this payment', (string) $error],
    default               => ['The payment was not finished', 'The payment page closed before your card was charged. You can start again whenever you like.'],
};
?>
<main id="main" class="app">
  <ol class="steps-nav" aria-label="Checkout progress">
    <li class="is-done">Your details</li>
    <li class="is-done">Payment</li>
    <li aria-current="step">Confirmation</li>
  </ol>

  <section class="result result--<?= e($status->tone()) ?>" aria-labelledby="result-title">
    <div class="result__mark" aria-hidden="true">
      <svg viewBox="0 0 64 64">
        <circle cx="32" cy="32" r="29"/>
        <?php if ($status === PaymentStatus::Paid): ?>
          <path d="M19 33l9 9 18-20"/>
        <?php elseif ($status === PaymentStatus::Failed || $status === PaymentStatus::Error): ?>
          <path d="M22 22l20 20M42 22L22 42"/>
        <?php else: ?>
          <path d="M32 18v15l9 6"/>
        <?php endif; ?>
      </svg>
    </div>
    <div class="result__copy">
      <h1 id="result-title"><?= e($heading) ?></h1>
      <p><?= e($message) ?></p>
      <?php if ($error && $payment): ?><p class="notice"><?= e($error) ?></p><?php endif; ?>
      <div class="result__actions">
        <a class="button button--solid" href="/#checkout"><?= $status === PaymentStatus::Paid ? 'Back to the shop' : 'Try again' ?></a>
        <a class="button button--quiet" href="/payments.php">See all payments</a>
      </div>
    </div>
  </section>

  <?php if ($payment): ?>
  <div class="detail-grid">
    <section class="panel" aria-labelledby="payment-heading">
      <h2 id="payment-heading">Payment</h2>
      <dl class="facts">
        <div><dt>Status</dt><dd><?php View::render('partials/status-badge', ['status' => $status]); ?></dd></div>
        <div><dt>Order code</dt><dd class="num"><?= e($payment['order_code']) ?></dd></div>
        <div><dt>Gate-E payment ID</dt><dd class="num"><?= e($payment['gate_payment_id']) ?></dd></div>
        <div><dt>Item</dt><dd><?= e($payment['product_name']) ?>, <?= e($payment['finish']) ?> × <?= (int) $payment['quantity'] ?></dd></div>
        <div><dt>Amount</dt><dd class="num"><?= e(money($payment['amount'], $payment['currency'])) ?></dd></div>
        <div><dt>Paid</dt><dd class="num"><?= e(money($payment['amount_paid'], $payment['currency'])) ?></dd></div>
        <div><dt>Gate-E status</dt><dd><?= e($payment['gateway_status'] ?? '—') ?></dd></div>
        <div><dt>Started</dt><dd><?= e(format_date($payment['created_at'])) ?></dd></div>
        <div><dt>Completed</dt><dd><?= e(format_date($payment['completed_at'])) ?></dd></div>
      </dl>
    </section>
    <section class="panel" aria-labelledby="customer-heading">
      <h2 id="customer-heading">Customer</h2>
      <dl class="facts">
        <div><dt>Name</dt><dd><?= e($payment['customer_name']) ?></dd></div>
        <div><dt>Email</dt><dd><?= e($payment['customer_email']) ?></dd></div>
        <div><dt>Mobile</dt><dd class="num">+<?= e($payment['mobile_code']) ?> <?= e($payment['mobile']) ?></dd></div>
      </dl>
    </section>
  </div>

  <section class="panel" aria-labelledby="tx-heading">
    <h2 id="tx-heading">Transactions</h2>
    <?php if ($transactions === []): ?>
      <p class="empty-note">No card was charged or declined for this payment.</p>
    <?php else: ?>
      <?php View::render('partials/transactions-table', ['transactions' => $transactions]); ?>
    <?php endif; ?>
  </section>
  <?php endif; ?>
</main>
