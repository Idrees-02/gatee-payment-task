<?php
/**
 * @var list<array<string, mixed>> $payments
 * @var array{total: int, paid: int, failed: int, open: int, collected: float} $summary
 */
use App\Service\PaymentStatus;
use App\Support\View;
?>
<main id="main" class="app">
  <div class="app-head">
    <div>
      <h1>Payments</h1>
      <p>Every checkout started on the shop, newest first.</p>
    </div>
    <a class="button button--solid" href="/#checkout">New checkout</a>
  </div>

  <dl class="summary" aria-label="Summary">
    <div><dt>Collected</dt><dd><?= e(money($summary['collected'])) ?></dd></div>
    <div><dt>Paid</dt><dd><?= $summary['paid'] ?></dd></div>
    <div><dt>Failed</dt><dd><?= $summary['failed'] ?></dd></div>
    <div><dt>Not finished</dt><dd><?= $summary['open'] ?></dd></div>
    <div><dt>All payments</dt><dd><?= $summary['total'] ?></dd></div>
  </dl>

  <section class="panel panel--flush" aria-label="Payment list">
    <?php if ($payments === []): ?>
      <div class="empty">
        <h2>No payments yet</h2>
        <p>Payments appear here as soon as someone starts a checkout.</p>
        <a class="button button--solid" href="/#checkout">Start a checkout</a>
      </div>
    <?php else: ?>
      <div class="table-scroll">
        <table class="data-table data-table--list">
          <thead>
            <tr>
              <th scope="col">Order</th>
              <th scope="col">Customer</th>
              <th scope="col">Item</th>
              <th scope="col" class="num">Amount</th>
              <th scope="col">Status</th>
              <th scope="col">Started</th>
              <th scope="col"><span class="visually-hidden">Details</span></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($payments as $p): ?>
              <tr>
                <td class="num"><?= e($p['order_code']) ?></td>
                <td>
                  <span class="cell-strong"><?= e($p['customer_name']) ?></span>
                  <span class="cell-sub"><?= e($p['customer_email']) ?></span>
                </td>
                <td><?= e($p['finish']) ?> × <?= (int) $p['quantity'] ?></td>
                <td class="num"><?= e(money($p['amount'], $p['currency'])) ?></td>
                <td><?php View::render('partials/status-badge', ['status' => PaymentStatus::from($p['status'])]); ?></td>
                <td class="nowrap"><?= e(format_date($p['created_at'])) ?></td>
                <td class="end">
                  <button type="button" class="button button--quiet button--small" data-view-payment="<?= (int) $p['id'] ?>"
                          aria-label="View payment <?= e($p['order_code']) ?>">View</button>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </section>
</main>

<div class="modal fade" id="payment-modal" tabindex="-1" aria-labelledby="payment-modal-title" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable modal-fullscreen-md-down">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title" id="payment-modal-title">Payment</h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" data-modal-body></div>
    </div>
  </div>
</div>
