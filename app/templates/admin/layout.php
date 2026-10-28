<?php
$links = [
    'porudzbine' => ['/admin', 'Porudžbine'],
    'proizvodnja' => ['/admin/proizvodnja', 'Proizvodnja'],
    'katalog' => ['/admin/katalog', 'Katalog'],
    'galerija' => ['/admin/galerija', 'Galerija'],
    'kalendar' => ['/admin/kalendar', 'Kalendar'],
    'ai' => ['/admin/ai', 'AI i tehnike'],
    'podesavanja' => ['/admin/podesavanja', 'Podešavanja'],
    'nalog' => ['/admin/nalog', 'Nalog'],
];
$brand = preg_match('/^#[0-9a-fA-F]{6}$/', $settings['branding']['color']) ? $settings['branding']['color'] : '#b23a6f';
?><!doctype html>
<html lang="sr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($title ?? 'Admin') ?> · <?= e($shop['name']) ?></title>
    <link rel="stylesheet" href="<?= e(asset('admin.css')) ?>">
    <style>:root { --brand: <?= e($brand) ?>; }</style>
</head>
<body>
<header class="topbar">
    <a class="brand" href="/admin"><?= e($shop['name']) ?></a>
    <nav>
        <?php foreach ($links as $key => [$href, $label]): ?>
            <a href="<?= e($href) ?>" class="<?= $nav === $key ? 'active' : '' ?>"><?= e($label) ?><?php if ($key === 'porudzbine' && $newOrders): ?><span class="badge-count"><?= (int)$newOrders ?></span><?php endif; ?></a>
        <?php endforeach; ?>
        <a href="/" target="_blank" rel="noopener">Sajt ↗</a>
    </nav>
    <form method="post" action="/admin/logout" class="inline">
        <?= csrf_field() ?>
        <button class="link">Odjava</button>
    </form>
</header>
<main class="container">
    <?php foreach (take_flashes() as $f): ?>
        <div class="alert alert-<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
    <?php endforeach; ?>
    <?= $content ?>
</main>
<script src="<?= e(asset('admin.js')) ?>"></script>
</body>
</html>
