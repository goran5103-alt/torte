<!doctype html>
<html lang="sr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Instalacija</title>
    <link rel="stylesheet" href="<?= e(asset('admin.css')) ?>">
</head>
<body class="center-page">
<div class="card narrow wide-form">
    <h1>Instalacija</h1>
    <p class="muted">Jednokratno podešavanje. Posle ovoga se ova stranica više ne otvara.</p>

    <h2>Provera servera</h2>
    <ul class="checks">
        <?php foreach ($checks as $label => $ok): ?>
            <li class="<?= $ok ? 'ok' : 'fail' ?>"><?= $ok ? '✓' : '✗' ?> <?= e($label) ?></li>
        <?php endforeach; ?>
    </ul>

    <?php foreach ($errors as $error): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
    <?php endforeach; ?>

    <form method="post" action="/install" class="form">
        <?= csrf_field() ?>
        <h2>Baza (iz DirectAdmin → MySQL Management)</h2>
        <label>Host <input name="db_host" value="<?= e($old['db_host']) ?>" required></label>
        <label>Ime baze <input name="db_name" value="<?= e($old['db_name']) ?>" required></label>
        <label>Korisnik <input name="db_user" value="<?= e($old['db_user']) ?>" required></label>
        <label>Lozinka baze <input name="db_pass" type="password" autocomplete="off"></label>

        <h2>Platforma</h2>
        <label>Glavni domen <input name="main_domain" value="<?= e($old['main_domain']) ?>" required>
            <small>Poslastičarnice će biti na poddomenima, npr. test.<?= e($old['main_domain']) ?></small></label>

        <h2>Vaš admin nalog</h2>
        <label>Ime <input name="name" value="<?= e($old['name']) ?>"></label>
        <label>Email <input name="email" type="email" value="<?= e($old['email']) ?>" required></label>
        <label>Lozinka (min. 10 znakova) <input name="password" type="password" minlength="10" required autocomplete="new-password"></label>

        <button class="btn btn-primary" type="submit">Instaliraj</button>
    </form>
</div>
</body>
</html>
