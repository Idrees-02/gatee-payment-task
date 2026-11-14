/**
 * Payments list: "View" fetches the payment over AJAX (our database + live Gate-E record)
 * and renders both side by side in a modal.
 */
$(function () {
  'use strict';

  var modal = new bootstrap.Modal('#payment-modal');
  var $title = $('#payment-modal-title');
  var $body = $('[data-modal-body]');

  $(document).on('click', '[data-view-payment]', function () {
    $title.text('Payment');
    $body.html('<p class="empty-note">Loading from our database and from Gate-E…</p>');
    modal.show();

    $.getJSON('/api/payment.php', { id: $(this).data('viewPayment') })
      .done(render)
      .fail(function (xhr) {
        var message = (xhr.responseJSON || {}).message || 'This payment could not be loaded. Try again.';
        $body.html('<p class="notice">' + escapeHtml(message) + '</p>');
      });
  });

  function render(response) {
    var local = response.database.payment;
    var remote = response.gateway ? response.gateway.payment : null;
    var transactions = response.gateway ? response.gateway.transaction : [];

    $title.text('Payment ' + local.order_code);
    $body.html(
      '<div class="compare">' +
        section('In our database', facts([
          ['Status', statusBadge(local.status)],
          ['Order code', text(local.order_code), true],
          ['Customer', text(local.customer_name)],
          ['Email', text(local.customer_email)],
          ['Mobile', text('+' + local.mobile_code + ' ' + local.mobile), true],
          ['Item', text(local.product_name + ', ' + local.finish + ' × ' + local.quantity)],
          ['Amount', text(money(local.amount, local.currency)), true],
          ['Paid', text(money(local.amount_paid, local.currency)), true],
          ['Started', text(local.created_at), true],
          ['Completed', text(local.completed_at), true],
        ])) +
        section('Live from Gate-E', remote ? facts([
          ['Status', statusBadge(remote.status)],
          ['Payment ID', text(remote.id), true],
          ['Code', text(remote.code), true],
          ['Client', text(remote.client && remote.client.name)],
          ['Client email', text(remote.client && remote.client.email)],
          ['Amount', text(money(remote.amount, remote.currency)), true],
          ['Paid', text(money(remote.amount_paid, remote.currency)), true],
          ['Remaining', text(money(remote.amount_remaining, remote.currency)), true],
          ['Description', text(remote.description)],
        ]) : '<p class="notice">' + escapeHtml(response.gatewayError || 'Gate-E returned nothing.') + '</p>') +
      '</div>' +
      section('Transactions at Gate-E', transactionTable(transactions || [])) +
      '<details class="raw"><summary>Raw Gate-E response</summary><pre>' +
        escapeHtml(JSON.stringify(response.gateway, null, 2)) +
      '</pre></details>'
    );
  }

  function transactionTable(transactions) {
    if (!transactions.length) {
      return '<p class="empty-note">No card was charged or declined for this payment.</p>';
    }
    var rows = transactions.map(function (t) {
      var card = t.card && t.card.first_digits ? t.card.first_digits + '******' + t.card.last_digits : '—';
      return '<tr>' +
        '<td class="num">' + text(t.id) + '</td>' +
        '<td>' + statusBadge(t.status) + '</td>' +
        '<td class="num">' + text(money(t.amount, t.currency)) + '</td>' +
        '<td class="num">' + text(card) + '</td>' +
        '<td>' + text(t.response_message) + '</td>' +
        '<td class="num">' + text((t.authorization_code || '—') + ' / ' + (t.rrn || '—')) + '</td>' +
        '<td class="nowrap">' + text(t.payment_date) + '</td>' +
      '</tr>';
    }).join('');

    return '<div class="table-scroll"><table class="data-table"><thead><tr>' +
      '<th scope="col">Transaction</th><th scope="col">Result</th><th scope="col" class="num">Amount</th>' +
      '<th scope="col">Card</th><th scope="col">Message</th><th scope="col">Auth / RRN</th><th scope="col">Date</th>' +
      '</tr></thead><tbody>' + rows + '</tbody></table></div>';
  }

  /* Small HTML builders ---------------------------------------------------- */

  function section(heading, content) {
    return '<section class="compare__side"><h3>' + escapeHtml(heading) + '</h3>' + content + '</section>';
  }

  function facts(rows) {
    return '<dl class="facts">' + rows.map(function (row) {
      return '<div><dt>' + escapeHtml(row[0]) + '</dt><dd' + (row[2] ? ' class="num"' : '') + '>' + row[1] + '</dd></div>';
    }).join('') + '</dl>';
  }

  var TONES = { paid: 'positive', failed: 'negative', error: 'negative', pending: 'waiting', unpaid: 'waiting', initiated: 'neutral' };
  var LABELS = { pending: 'Not completed' };

  function statusBadge(status) {
    var key = String(status || '').toLowerCase();
    var label = LABELS[key] || key.charAt(0).toUpperCase() + key.slice(1);
    return '<span class="status status--' + (TONES[key] || 'neutral') + '">' + escapeHtml(label || '—') + '</span>';
  }

  function money(amount, currency) {
    return amount === null || amount === undefined ? null : Number(amount).toFixed(3) + ' ' + (currency || 'BHD');
  }

  function text(value) {
    return escapeHtml(value === null || value === undefined || value === '' ? '—' : value);
  }

  function escapeHtml(value) {
    return String(value).replace(/[&<>"']/g, function (ch) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch];
    });
  }
});
