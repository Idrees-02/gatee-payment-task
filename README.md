# Halo Audio · Gate-E payment integration

A small shop that sells one product and takes payment through the **Gate-E** gateway.
Built with **PHP 8.3, MySQL 8, HTML, CSS, JavaScript, jQuery AJAX and Bootstrap** (modal only), running in Docker.

| Requirement from the brief | Where it lives |
| --- | --- |
| 1. Gate-E integration in PHP + MySQL | `src/Gateway/GateEClient.php` (create / get / list gateways) |
| 2. Purchase form, details saved in the database | `templates/pages/shop.php` → `customers` table |
| 3. AJAX creates the payment and stores an *initiated* record | `assets/js/checkout.js` → `public/api/create-payment.php` → `CheckoutService` |
| 4. Payment page inside the checkout, in an iframe | `.pay__gateway` in `shop.php`, opened by `checkout.js` |
| 5. Gateway redirects to a callback page that updates payment + transactions | `public/callback.php` → `PaymentSyncService` |
| 6. Callback page shows payment and transaction details | `templates/pages/result.php` |
| 7. Payment list; "View" opens a modal with database **and** live Gate-E data over AJAX | `public/payments.php` → `public/api/payment.php` → `assets/js/payments.js` |

Screenshots of every screen and of the database tables are in [`screenshots/`](screenshots).

---

## Run it

Requirements: Docker Desktop.

```bash
cp .env.example .env          # then put the Gate-E API user and key in .env
docker compose up -d --build
```

| URL | What |
| --- | --- |
| http://localhost:8090 | the shop and checkout |
| http://localhost:8090/payments.php | payments list |
| http://localhost:8091 | phpMyAdmin (database `gatee_shop`) |

The schema in `database/schema.sql` is loaded automatically the first time MySQL starts.
To start over with an empty database: `docker compose down -v && docker compose up -d`.

### Test cards (Gate-E sandbox)

Card `1111 2222 3333 4444`, expiry `12/30`, and the CVV picks the result:

| CVV | Result |
| --- | --- |
| `123` | successful payment |
| `999` | failed payment (recorded as a failed transaction) |
| anything else | the gateway lets the customer re-enter the card; nothing is recorded |

---

## How a payment flows

```
Browser (checkout.js)            PHP                                   Gate-E
─────────────────────            ───                                   ──────
submit form ── AJAX POST ──▶ api/create-payment.php
                               validate (CheckoutRequest)
                               INSERT customer + payment (status=initiated)  ── one DB transaction
                               payment/create ──────────────────────────────▶  returns id + link
                               UPDATE payment (gate id, link)
            ◀── JSON { paymentUrl } ──
open paymentUrl in <iframe>                                           customer pays in the iframe
                                                                      │
             ┌────────────────────────────────────────────────────────┘
             ▼
callback.php?payment_id=…&payment_code=…  (redirect_url)
             payment/get ─────────────────────────────────────────────▶  payment + transactions
             UPSERT transactions, UPDATE payment status (paid / failed / pending)
             render result page

webhook.php  (callback_url, server-to-server) ── same sync, answers { code_id }
```

**The redirect is never trusted on its own.** `payment_id` in the query string only says *which*
payment to look at; the status always comes from `payment/get`. The sync is idempotent
(transactions are keyed on Gate-E's transaction id, a paid payment is never downgraded), so the
redirect, the webhook and the "View" modal can all run it in any order.

### A finding about the iframe on localhost

After a payment, the Gate-E page runs `location.href = redirect_url` inside the iframe.
When the shop runs on `http://localhost`, Chrome's **Local Network Access** protection blocks a
public HTTPS page from navigating a frame to `localhost`, so the iframe goes blank and the
redirect never arrives. (At the top level the same redirect works.)

The checkout handles both situations:

* **On a real HTTPS domain**, the gateway redirect lands on `callback.php` inside the frame.
  `callback.php` sees `Sec-Fetch-Dest: iframe`, answers with a tiny page that reloads itself
  in the top window, and the full result page is shown.
* **On localhost**, while the iframe is open, `checkout.js` asks `api/payment-status.php` every
  3 seconds whether Gate-E has recorded a result. When it has, the browser goes to the same
  `callback.php` URL. The status endpoint only answers for orders started in the same browser
  session.

The server-to-server `callback_url` (`webhook.php`) is implemented and tested with a simulated
request, but Gate-E cannot reach `localhost`. It works as soon as the app is deployed on a public URL.

---

## Code structure

```
public/                 web root: thin entry points only
  index.php             product page + checkout
  callback.php          redirect_url target: sync + result page
  webhook.php           callback_url target: sync + { code_id }
  payments.php          payments list
  api/create-payment.php
  api/payment.php       database + live Gate-E data for the modal
  api/payment-status.php
  assets/               css, js, images
src/                    application code (namespace App\, autoloaded)
  Container.php         creates and wires the services
  Gateway/              GateEClient, GateEException: the only code that talks to Gate-E
  Repository/           CustomerRepository, PaymentRepository, TransactionRepository: the only SQL
  Service/              CheckoutService, PaymentSyncService, PaymentStatus (enum),
                        CheckoutRequest (validated input), ValidationException
  Catalog/              Product, Finish, Catalog: prices live on the server
  Http/                 Request, JsonResponse, Csrf, Session
  Support/              Env, View, template helpers
templates/              PHP templates (pages + partials), no logic beyond display
database/schema.sql     customers 1─* payments 1─* transactions
tools/screenshots.mjs   regenerates screenshots/ with headless Chrome
```

Each layer has one job: entry points read the request and pick a response; services hold the
business rules; repositories hold the SQL; the gateway client holds the HTTP calls.

## Security notes

* The API key stays on the server (`.env`, never sent to the browser, not in Git).
* Prices come from `Catalog`, never from the form; the amount is computed on the server.
* All SQL uses prepared statements (PDO, emulation off).
* All output is escaped (`e()` in templates, `escapeHtml()` in JavaScript).
* The checkout form carries a CSRF token; the session cookie is `HttpOnly` and `SameSite=Lax`.
* Only the first 6 and last 4 card digits are stored, exactly as Gate-E returns them.
* The customer row and the initiated payment are written in one database transaction.

## Design

One product page in the spirit of a product launch: the name set as a monument across a photo
of Manama, a scroll scene where the headband draws itself into a ring and a line of street noise
flattens to silence (GSAP ScrollTrigger), a finish picker that recolours the drawn product
everywhere on the page, and the checkout at the end. One typeface (Schibsted Grotesk), teal on
paper, square corners. Every animation starts from the finished layout, so the page works without
JavaScript and respects `prefers-reduced-motion`.

The product, its specifications and the brand name are invented for the demo.

Photographs: "Manama Skyline 15" and "Sitra Dhow Harbour 09" by Zairon (CC BY-SA 4.0, Wikimedia
Commons); "Malkiya Beach, Bahrain" by Shahana (CC0, WordPress Photo Directory); "A fishing boat
moored on the shore at dusk" by PattayaPatrol (CC BY-SA 4.0, Wikimedia Commons).
