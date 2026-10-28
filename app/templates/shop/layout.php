<?php
/** @var array $shop @var array $settings */
$brand = preg_match('/^#[0-9a-fA-F]{6}$/', $settings['branding']['color']) ? $settings['branding']['color'] : '#b23a6f';
$logo = $settings['branding']['logo'] ?? null;
$contact = $settings['contact'];
$instagram = $contact['instagram'];
if ($instagram !== '' && !str_starts_with($instagram, 'http')) {
    $instagram = 'https://instagram.com/' . ltrim($instagram, '@');
}
?><!doctype html>
<html lang="sr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <?php if ((int)$shop['noindex'] === 1): ?><meta name="robots" content="noindex, nofollow"><?php endif; ?>
    <title><?= e(isset($title) ? $title . ' · ' . $shop['name'] : $shop['name']) ?></title>
    <meta name="description" content="<?= e($settings['tagline'] ?: 'Poručite tortu online') ?>">
    <meta name="theme-color" content="<?= e($brand) ?>">
    <link rel="stylesheet" href="<?= e(asset('app.css')) ?>">
    <style>:root { --brand: <?= e($brand) ?>; }</style>
</head>
<body>
<header class="site-header">
    <a class="logo" href="/">
        <?php if ($logo): ?><img src="<?= e(upload_url($logo)) ?>" alt=""><?php endif; ?>
        <span><?= e($shop['name']) ?></span>
    </a>
    <a class="cart-link" href="/korpa" aria-label="Korpa">
        <svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true"><path fill="currentColor" d="M7 18a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm10 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4ZM5.2 4l.4 2H20a1 1 0 0 1 1 1.2l-1.4 7A2 2 0 0 1 17.6 16H8.1a2 2 0 0 1-2-1.6L4.1 3.8A1 1 0 0 0 3.1 3H2V1h1.1a3 3 0 0 1 3 2.4Z"/></svg>
        <?php if ($cartCount): ?><span class="count"><?= (int)$cartCount ?></span><?php endif; ?>
    </a>
</header>

<?php if ($shop['status'] === 'demo'): ?>
    <div class="demo-ribbon">Demo verzija · porudžbine se ne izrađuju</div>
<?php endif; ?>

<main class="page">
    <?php foreach (take_flashes() as $f): ?>
        <div class="notice notice-<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
    <?php endforeach; ?>
    <?= $content ?>
</main>

<footer class="site-footer">
    <strong><?= e($shop['name']) ?></strong>
    <?php if ($contact['address']): ?><div><?= e($contact['address']) ?></div><?php endif; ?>
    <?php if ($contact['hours']): ?><div><?= e($contact['hours']) ?></div><?php endif; ?>
    <div class="footer-links">
        <?php if ($contact['phone']): ?><a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $contact['phone'])) ?>"><?= e($contact['phone']) ?></a><?php endif; ?>
        <?php if ($instagram): ?><a href="<?= e($instagram) ?>" target="_blank" rel="noopener">Instagram</a><?php endif; ?>
        <?php if ($contact['facebook']): ?><a href="<?= e($contact['facebook']) ?>" target="_blank" rel="noopener">Facebook</a><?php endif; ?>
    </div>
</footer>
<?php if (!empty($scripts)): foreach ($scripts as $s): ?>
    <script src="<?= e(asset($s)) ?>"></script>
<?php endforeach; endif; ?>
</body>
</html>
