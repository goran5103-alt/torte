<!doctype html>
<html lang="sr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Greška <?= (int)$status ?></title>
    <link rel="stylesheet" href="<?= e(asset('admin.css')) ?>">
</head>
<body class="center-page">
    <div class="card narrow">
        <h1><?= $status === 404 ? 'Nije pronađeno' : 'Ups' ?></h1>
        <p><?= e($message) ?></p>
        <p><a class="btn" href="/">Na početnu</a></p>
    </div>
</body>
</html>
