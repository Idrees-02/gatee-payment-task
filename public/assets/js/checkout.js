/**
 * Product page behaviour: finish picker, quantity, feature details,
 * and the checkout (AJAX create-payment, then Gate-E inside an iframe).
 */
$(function () {
  'use strict';

  startAtTheTop();

  var $root = $(document.documentElement);
  var $form = $('#checkout-form');
  var $submit = $form.find('[type="submit"]');
  var $alert = $form.find('.pay__alert');
  var $gateway = $('.pay__gateway');
  var $frame = $('[data-gateway-frame]');
  var $quantity = $('#quantity');
  var $total = $('[data-total]');
  var unitPrice = parseFloat($total.data('unitPrice'));
  var maxQuantity = parseInt($quantity.attr('max'), 10);

  /* Finish picker ------------------------------------------------------- */

  var $tabs = $('.finish-tab');

  $tabs.on('click', function () { selectFinish($(this)); });

  $tabs.on('keydown', function (event) {
    var offset = { ArrowRight: 1, ArrowDown: 1, ArrowLeft: -1, ArrowUp: -1 }[event.key];
    if (!offset) return;
    event.preventDefault();
    var next = $tabs.eq(($tabs.index(this) + offset + $tabs.length) % $tabs.length);
    selectFinish(next);
    next.trigger('focus');
  });

  function selectFinish($tab) {
    $tabs.attr({ 'aria-checked': 'false', tabindex: '-1' });
    $tab.attr({ 'aria-checked': 'true', tabindex: '0' });

    $root.css({ '--shell': $tab.data('shell'), '--cushion': $tab.data('cushion') });
    $('[data-finish-name]').text($tab.data('name'));
    $('[data-finish-description]').text($tab.data('description') + '. Every finish has the same steel band and the same quiet.');
    $('[data-finish-input]').val($tab.data('finish'));
  }

  $tabs.filter('[aria-checked="false"]').attr('tabindex', '-1');

  /* Quantity --------------------------------------------------------------- */

  $('.stepper [data-step]').on('click', function () {
    setQuantity(currentQuantity() + parseInt($(this).data('step'), 10));
  });
  $quantity.on('change', function () { setQuantity(currentQuantity()); });

  function currentQuantity() {
    return parseInt($quantity.val(), 10) || 1;
  }

  function setQuantity(value) {
    var quantity = Math.min(maxQuantity, Math.max(1, value));
    $quantity.val(quantity);
    $('.stepper [data-step="-1"]').prop('disabled', quantity <= 1);
    $('.stepper [data-step="1"]').prop('disabled', quantity >= maxQuantity);
    $total.text(formatMoney(unitPrice * quantity));
  }

  function formatMoney(amount) {
    return amount.toFixed(3) + ' BHD';
  }

  setQuantity(1);

  /* Feature details -------------------------------------------------------- */

  var $detail = $('.specs__detail');
  $('.specs__group li').on('mouseenter focus', function () {
    $('.specs__group li').removeClass('is-active');
    $(this).addClass('is-active');
    $detail.text($(this).data('detail'));
  });

  /* Checkout --------------------------------------------------------------- */

  $form.on('submit', function (event) {
    event.preventDefault();
    hideAlert();

    var errors = validate();
    showFieldErrors(errors);
    if (!$.isEmptyObject(errors)) return;

    setBusy(true);
    $.ajax({ url: '/api/create-payment.php', method: 'POST', data: $form.serialize(), dataType: 'json' })
      .done(openGateway)
      .fail(function (xhr) {
        var response = xhr.responseJSON || {};
        showFieldErrors(response.errors || {});
        showAlert(response.message || 'We could not reach the server. Check your connection and try again.');
      })
      .always(function () { setBusy(false); });
  });

  $('[data-edit-details]').on('click', closeGateway);

  /*
   * While Gate-E is open in the iframe, ask our server every few seconds whether a result
   * has been recorded, and move to the confirmation page when it has. This is how the page
   * finishes on http://localhost, where the browser blocks the gateway's own redirect.
   */
  var POLL_EVERY_MS = 3000;
  var pollTimer = null;

  function watchPayment(orderCode) {
    stopWatching();
    pollTimer = setInterval(function () {
      $.getJSON('/api/payment-status.php', { order: orderCode }).done(function (response) {
        if (response.finished) {
          stopWatching();
          setProgress('done');
          window.location.assign(response.callbackUrl);
        }
      });
    }, POLL_EVERY_MS);
  }

  function stopWatching() {
    clearInterval(pollTimer);
    pollTimer = null;
  }

  $frame.on('load', function () { $('.pay__loading').prop('hidden', true); });

  function validate() {
    var errors = {};
    var name = $.trim($('#name').val());
    var email = $.trim($('#email').val());
    var mobile = $('#mobile').val().replace(/\D/g, '');

    if (name.length < 2) errors.name = 'Enter your full name.';
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(email)) errors.email = 'Enter an email address like name@example.com.';
    if (mobile.length < 7 || mobile.length > 12) errors.mobile = 'Enter a mobile number of 7 to 12 digits.';
    return errors;
  }

  function showFieldErrors(errors) {
    $form.find('.field__error').text('');
    $form.find('[aria-invalid]').removeAttr('aria-invalid');
    $.each(errors, function (field, message) {
      $('#' + field + '-error').text(message);
      $('#' + field).attr('aria-invalid', 'true');
    });
    var firstInvalid = Object.keys(errors)[0];
    if (firstInvalid) $('#' + firstInvalid).trigger('focus');
  }

  function openGateway(response) {
    $('[data-payment-meta]').text('Order ' + response.orderCode + ' · ' + response.amount);
    $('.pay__loading').prop('hidden', false);
    $frame.attr('src', response.paymentUrl);
    watchPayment(response.orderCode);

    $form.prop('hidden', true);
    $gateway.prop('hidden', false);
    lockOrder(true);
    setProgress('payment');
    scrollToElement($gateway[0]);
  }

  function closeGateway() {
    stopWatching();
    $frame.attr('src', 'about:blank');
    $gateway.prop('hidden', true);
    $form.prop('hidden', false);
    lockOrder(false);
    setProgress('details');
  }

  function lockOrder(locked) {
    $('.stepper button, #quantity, .finish-tab').prop('disabled', locked);
    if (!locked) setQuantity(currentQuantity());
  }

  function setProgress(step) {
    var order = ['details', 'payment', 'done'];
    var reached = order.indexOf(step);
    $('.steps-nav li').each(function () {
      var index = order.indexOf($(this).data('step'));
      $(this).toggleClass('is-done', index < reached);
      if (index === reached) $(this).attr('aria-current', 'step'); else $(this).removeAttr('aria-current');
    });
  }

  function setBusy(busy) {
    $submit.prop('disabled', busy).toggleClass('is-busy', busy);
    $submit.find('[data-label]').text(busy ? 'Creating your payment' : 'Continue to payment');
  }

  /**
   * A reload always starts at the top of the page. A link from another page that points at a
   * section (for example "Try again" → /#checkout) still lands there once, and the #hash is then
   * removed from the address so the next reload starts at the top again.
   */
  function startAtTheTop() {
    if ('scrollRestoration' in history) history.scrollRestoration = 'manual';
    var target = location.hash ? document.querySelector(location.hash) : null;
    if (location.hash) history.replaceState(null, '', location.pathname + location.search);
    if (target) {
      scrollToElement(target);
    } else {
      window.scrollTo(0, 0);
    }
  }

  /** Uses the page's smooth scroller when it is running, the browser's otherwise. */
  function scrollToElement(element) {
    if (window.__lenis) {
      window.__lenis.scrollTo(element, { offset: -24, duration: 1.2 });
    } else {
      element.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  }

  function showAlert(message) { $alert.text(message).prop('hidden', false); }
  function hideAlert() { $alert.prop('hidden', true); }
});
