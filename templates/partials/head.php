<?php
/**
 * @var string $title
 * @var string $page   body class: "page-shop" | "page-app"
 * @var string $active navigation item: "shop" | "payments" | ""
 */
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title) ?> · Halo Audio</title>
  <link rel="icon" href="/assets/img/favicon.svg" type="image/svg+xml">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Schibsted+Grotesk:wght@400;600;800&display=swap" rel="stylesheet">
  <?php if ($page === 'page-app'): ?>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <?php endif; ?>
  <link href="/assets/css/base.css?v=10" rel="stylesheet">
  <link href="/assets/css/<?= $page === 'page-shop' ? 'shop' : 'app' ?>.css?v=10" rel="stylesheet">
</head>
<body class="<?= e($page) ?>">
<a class="skip-link" href="#main">Skip to content</a>
<header class="site-nav">
  <a class="wordmark" href="/" aria-label="Halo Audio home">
    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9.5"/><circle cx="12" cy="12" r="3.5"/></svg>
    Halo Audio
  </a>
  <nav aria-label="Main">
    <a href="/#checkout" <?= $active === 'shop' ? 'aria-current="page"' : '' ?>>Buy</a>
    <a href="/payments.php" <?= $active === 'payments' ? 'aria-current="page"' : '' ?>>Payments</a>
  </nav>
</header>
