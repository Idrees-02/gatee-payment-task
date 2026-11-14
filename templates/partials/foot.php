<?php /** @var list<string> $scripts */ ?>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<?php foreach ($scripts ?? [] as $src): ?>
<script src="<?= e($src) ?>"></script>
<?php endforeach; ?>
</body>
</html>
