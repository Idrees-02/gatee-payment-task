<?php

declare(strict_types=1);

use App\Support\View;

$app = require __DIR__ . '/../src/bootstrap.php';

View::render('partials/head', ['title' => 'Payments', 'page' => 'page-app', 'active' => 'payments']);
View::render('pages/payments', [
    'payments' => $app->payments()->latest(),
    'summary'  => $app->payments()->summary(),
]);
View::render('partials/foot', ['scripts' => [
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js',
    '/assets/js/payments.js?v=8',
]]);
