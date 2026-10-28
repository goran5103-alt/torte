<!doctype html>
<html lang="sr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Prijava</title>
    <link rel="stylesheet" href="<?= e(asset('admin.css')) ?>">
</head>
<body class="center-page">
<div class="card narrow">
    <h1>Prijava</h1>
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
    <form method="post" action="/login" class="form">
        <?= csrf_field() ?>
        <label>Email <input type="email" name="email" value="<?= e($email) ?>" required autofocus></label>
        <label>Lozinka <input type="password" name="password" required></label>
        <button class="btn btn-primary" type="submit">Prijavi se</button>
    </form>
</div>
</body>
</html>
