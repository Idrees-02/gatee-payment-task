<?php /** @var App\Service\PaymentStatus $status */ ?>
<span class="status status--<?= e($status->tone()) ?>"><?= e($status->label()) ?></span>
