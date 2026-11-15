<?php

declare(strict_types=1);

use App\Http\Csrf;
use App\Service\CheckoutRequest;
use App\Support\View;

$app = require __DIR__ . '/../src/bootstrap.php';
$csrfToken = Csrf::token(); // starts the session, so it must run before any output

View::render('partials/head', ['title' => 'Halo One headphones', 'page' => 'page-shop', 'active' => 'shop']);
View::render('pages/shop', [
    'product'      => $app->catalog()->headphones(),
    'csrfToken'    => $csrfToken,
    'countryCodes' => CheckoutRequest::COUNTRY_CODES,
]);
View::render('partials/foot', ['scripts' => [
    'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js',
    'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js',
    'https://cdn.jsdelivr.net/npm/lenis@1.1.13/dist/lenis.min.js',
    '/assets/js/motion.js?v=10',
    '/assets/js/checkout.js?v=10',
]]);
