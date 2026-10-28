<!doctype html>
<html lang="sr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($title ?? 'Dashboard') ?> · Platforma</title>
    <link rel="stylesheet" href="<?= e(asset('admin.css')) ?>">
</head>
<body>
<header class="topbar">
    <a class="brand" href="/">Platforma</a>
    <nav>
        <a href="/">Poslastičarnice</a>
        <a href="/poslasticarnice/nova">+ Nova</a>
    </nav>
    <form method="post" action="/logout" class="inline">
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
