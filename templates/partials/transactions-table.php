<?php /** @var list<array<string, mixed>> $transactions */ ?>
<div class="table-scroll">
  <table class="data-table">
    <thead>
      <tr>
        <th scope="col">Transaction</th>
        <th scope="col">Result</th>
        <th scope="col" class="num">Amount</th>
        <th scope="col">Card</th>
        <th scope="col">Message</th>
        <th scope="col">Auth / RRN</th>
        <th scope="col">Date</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($transactions as $t): ?>
        <tr>
          <td class="num"><?= e($t['gate_transaction_id']) ?></td>
          <td><span class="status status--<?= $t['status'] === 'paid' ? 'positive' : 'negative' ?>"><?= e(ucfirst($t['status'])) ?></span></td>
          <td class="num"><?= e(money($t['amount'], $t['currency'])) ?></td>
          <td class="num"><?= e($t['card_masked'] ?? '—') ?></td>
          <td><?= e($t['response_message'] ?? '—') ?></td>
          <td class="num"><?= e(($t['authorization_code'] ?? '—') . ' / ' . ($t['rrn'] ?? '—')) ?></td>
          <td><?= e(format_date($t['paid_at'])) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
