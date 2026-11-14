<?php
/**
 * Product page with the checkout built in.
 * @var App\Catalog\Product $product
 * @var string $csrfToken
 * @var list<string> $countryCodes
 */
$finish = $product->defaultFinish();
$features = [
    'Sound' => [
        ['40 mm drivers', 'Large drivers keep bass full even at low volume.'],
        ['Adaptive noise cancelling', 'Six microphones measure the room and cancel it 50,000 times a second.'],
        ['Transparency mode', 'Hold the right cup to hear the street without taking them off.'],
        ['Spatial audio', 'Films and calls sound like they are around you, not inside your head.'],
        ['Hi-Res wireless', 'LDAC and AAC over Bluetooth 5.3.'],
        ['Clear calls', 'Beamforming mics pick out your voice in wind and traffic.'],
    ],
    'Comfort' => [
        ['Memory-foam cushions', 'They warm to the shape of your ear in a few minutes.'],
        ['Pressure-free band', 'The steel ring spreads its grip, so nothing presses on the crown.'],
        ['268 grams', 'Light enough for a full working day.'],
        ['Folds flat', 'The cups turn 90° to slide into the case.'],
        ['Fits with glasses', 'Soft inner walls close the seal around frames.'],
        ['Washable lining', 'The knit covers come off and go in the wash.'],
    ],
    'Power' => [
        ['40 hours with ANC', 'A week of commutes on one charge.'],
        ['4 hours in 5 minutes', 'A short charge covers a long flight.'],
        ['USB-C', 'Charge with the same cable as your phone.'],
        ['Two devices at once', 'Switches from laptop to phone when a call comes in.'],
        ['Auto-pause', 'Lift a cup and the music stops.'],
        ['2-year warranty', 'Repairs and replacements handled in Bahrain.'],
    ],
];
?>
<main id="main">

  <section class="hero" aria-labelledby="hero-title">
    <img class="hero__photo" src="/assets/img/manama-skyline.jpg" width="1920" height="1183"
         alt="The Manama skyline and the Bahrain World Trade Center across turquoise water" fetchpriority="high">
    <h1 class="hero__title" id="hero-title">
      <span class="hero__line hero__line--top"><span>Halo</span></span>
      <span class="hero__line hero__line--bottom"><span>One</span></span>
    </h1>
    <div class="hero__lede">
      <p>Over-ear headphones that turn a loud street into the sound of the sea. <?= e(money($product->unitPrice)) ?>, delivered anywhere in Bahrain.</p>
      <a class="button button--solid" href="#checkout">Buy Halo One</a>
    </div>
  </section>

  <section class="assembly" aria-labelledby="assembly-title" data-finish-target>
    <div class="assembly__stage">
      <h2 id="assembly-title" class="assembly__title">One ring, no noise</h2>
      <div class="assembly__figure">
        <svg class="noise" viewBox="0 0 400 120" aria-hidden="true" preserveAspectRatio="none">
          <path data-noise d="M0 60 L400 60"/>
        </svg>
        <?php $id = 'assembly'; require __DIR__ . '/../partials/headphones.php'; ?>
      </div>
      <ol class="assembly__steps">
        <li class="is-active">A single steel band, bent once into a ring.</li>
        <li>Two cushions close the room off, ear by ear.</li>
        <li>Then the noise drops away.</li>
      </ol>
    </div>
  </section>

  <section class="finishes" id="finishes" aria-labelledby="finishes-title">
    <div class="finishes__plate">
      <img src="/assets/img/malkiya-beach.jpg" width="1365" height="2048" loading="lazy"
           alt="A small boat resting on calm water at Malkiya Beach, Bahrain, at sunset">
      <span class="finishes__ring" aria-hidden="true"></span>
    </div>
    <div class="finishes__panel">
      <div class="finishes__tabs" role="radiogroup" aria-label="Finish">
        <?php foreach ($product->finishes as $option): ?>
          <button type="button" role="radio" class="finish-tab"
                  aria-checked="<?= $option === $finish ? 'true' : 'false' ?>"
                  data-finish="<?= e($option->id) ?>"
                  data-name="<?= e($option->name) ?>"
                  data-description="<?= e($option->description) ?>"
                  data-shell="<?= e($option->shell) ?>"
                  data-cushion="<?= e($option->cushion) ?>">
            <?= e($option->name) ?>
          </button>
        <?php endforeach; ?>
      </div>
      <div class="finishes__copy">
        <h2 id="finishes-title">Pick your finish</h2>
        <p data-finish-description><?= e($finish->description) ?>. Every finish has the same steel band and the same quiet.</p>
      </div>
      <div class="finishes__actions">
        <a class="cell cell--ink" href="#checkout"><span>Buy in <span data-finish-name><?= e($finish->name) ?></span></span></a>
        <a class="cell cell--tone" href="#specs">See what's inside</a>
      </div>
    </div>
  </section>

  <section class="specs" id="specs" aria-labelledby="specs-title">
    <h2 id="specs-title">What's inside</h2>
    <div class="specs__grid">
      <?php foreach ($features as $group => $items): ?>
        <div class="specs__group">
          <h3><?= e($group) ?></h3>
          <ul>
            <?php foreach ($items as [$name, $detail]): ?>
              <li tabindex="0" data-detail="<?= e($detail) ?>"><?= e($name) ?><span class="specs__inline"><?= e($detail) ?></span></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endforeach; ?>
      <p class="specs__detail" aria-live="polite">Hover or tap a feature to read about it.</p>
    </div>
  </section>

  <section class="places" aria-labelledby="places-title">
    <h2 id="places-title">Made for wherever it gets loud</h2>
    <p class="places__lede">Noise cancelling that follows you from the office to the airport, and switches itself off when you take them off.</p>
    <figure class="places__wide">
      <img src="/assets/img/sitra-harbour.jpg" width="1920" height="1425" loading="lazy" alt="Wooden dhows moored side by side in Sitra harbour">
      <figcaption>Dhows in Sitra harbour.</figcaption>
    </figure>
    <figure class="places__narrow">
      <img src="/assets/img/boat-dusk.jpg" width="1920" height="1280" loading="lazy" alt="A fishing boat moored on the shore at dusk against a calm sea">
      <figcaption>A fishing boat at dusk.</figcaption>
    </figure>
  </section>

  <section class="checkout" id="checkout" aria-labelledby="checkout-title">
    <div class="checkout__head">
      <h2 id="checkout-title">Checkout</h2>
      <ol class="steps-nav" aria-label="Checkout progress">
        <li aria-current="step" data-step="details">Your details</li>
        <li data-step="payment">Payment</li>
        <li data-step="done">Confirmation</li>
      </ol>
    </div>

    <div class="checkout__body">
      <aside class="order" aria-label="Your order" data-finish-target>
        <div class="order__figure"><?php $id = 'order'; require __DIR__ . '/../partials/headphones.php'; ?></div>
        <p class="order__name"><?= e($product->name) ?> · <span data-finish-name><?= e($finish->name) ?></span></p>
        <dl class="order__lines">
          <div><dt>Price</dt><dd><?= e(money($product->unitPrice)) ?></dd></div>
          <div>
            <dt><label for="quantity">Quantity</label></dt>
            <dd>
              <span class="stepper">
                <button type="button" data-step="-1" aria-label="One fewer">−</button>
                <input id="quantity" name="quantity" form="checkout-form" type="number" inputmode="numeric"
                       min="1" max="<?= $product->maxQuantity ?>" value="1">
                <button type="button" data-step="1" aria-label="One more">+</button>
              </span>
            </dd>
          </div>
          <div><dt>Delivery</dt><dd>Free</dd></div>
          <div class="order__total"><dt>Total</dt><dd data-total data-unit-price="<?= e($product->unitPrice) ?>"><?= e(money($product->unitPrice)) ?></dd></div>
        </dl>
      </aside>

      <div class="pay">
        <form id="checkout-form" class="pay__form" novalidate>
          <input type="hidden" name="csrf" value="<?= e($csrfToken) ?>">
          <input type="hidden" name="finish" value="<?= e($finish->id) ?>" data-finish-input>
          <h3>Your details</h3>
          <p class="pay__hint">We send the receipt here and call this number only about delivery.</p>

          <div class="field">
            <label for="name">Full name</label>
            <input id="name" name="name" type="text" autocomplete="name" maxlength="64" required aria-describedby="name-error">
            <p class="field__error" id="name-error"></p>
          </div>
          <div class="field">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" autocomplete="email" maxlength="64" required aria-describedby="email-error">
            <p class="field__error" id="email-error"></p>
          </div>
          <div class="field">
            <label for="mobile">Mobile number</label>
            <div class="field__phone">
              <select id="mobile_code" name="mobile_code" aria-label="Country code">
                <?php foreach ($countryCodes as $code): ?>
                  <option value="<?= e($code) ?>">+<?= e($code) ?></option>
                <?php endforeach; ?>
              </select>
              <input id="mobile" name="mobile" type="tel" autocomplete="tel-national" inputmode="numeric" maxlength="12" required aria-describedby="mobile-error">
            </div>
            <p class="field__error" id="mobile-error"></p>
          </div>

          <p class="pay__alert" role="alert" hidden></p>

          <button class="button button--solid button--wide" type="submit">
            <span data-label>Continue to payment</span>
          </button>
          <p class="pay__hint">You'll enter your card on Gate-E's secure page, right here on this page.</p>
        </form>

        <div class="pay__gateway" hidden>
          <div class="pay__gateway-head">
            <div>
              <h3>Payment</h3>
              <p class="pay__meta" data-payment-meta></p>
            </div>
            <button type="button" class="link-button" data-edit-details>Change details</button>
          </div>
          <div class="pay__frame">
            <p class="pay__loading">Loading the secure payment page…</p>
            <iframe title="Gate-E secure payment page" data-gateway-frame></iframe>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="closer" aria-labelledby="closer-title">
    <h2 id="closer-title"><span>Turn the world</span> <span>down.</span></h2>
    <div class="closer__foot">
      <p>Free delivery anywhere in Bahrain within two working days. Pay by card on Gate-E's secure page.</p>
      <a class="button button--paper" href="#checkout">Buy Halo One</a>
    </div>
  </section>

  <footer class="site-foot">
    <p class="site-foot__name" aria-hidden="true">Halo</p>
    <div class="site-foot__meta">
      <p>Halo Audio, Manama. A demo shop for the Gate-E payment integration; payments run in the Gate-E sandbox.</p>
      <p>Photographs: “Manama Skyline 15” and “Sitra Dhow Harbour 09” by Zairon, CC BY-SA 4.0, Wikimedia Commons · “Malkiya Beach, Bahrain” by Shahana, CC0, WordPress Photo Directory · “A fishing boat moored on the shore at dusk” by PattayaPatrol, CC BY-SA 4.0, Wikimedia Commons.</p>
    </div>
  </footer>
</main>
