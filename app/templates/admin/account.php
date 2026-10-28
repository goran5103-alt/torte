<?php $title = 'Nalog'; ?>
<h1>Nalog</h1>
<form method="post" action="/admin/nalog" class="form card narrow">
    <?= csrf_field() ?>
    <label>Ime <input name="name" value="<?= e($user['name']) ?>"></label>
    <label>Email za prijavu <input type="email" name="email" value="<?= e($user['email']) ?>" required></label>
    <h2>Promena lozinke</h2>
    <label>Trenutna lozinka <input type="password" name="current_password" autocomplete="current-password"></label>
    <label>Nova lozinka (min. 8 znakova) <input type="password" name="new_password" minlength="8" autocomplete="new-password"></label>
    <button class="btn btn-primary">Sačuvaj</button>
</form>
