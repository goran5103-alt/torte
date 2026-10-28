<?php $title = 'Nova poslastičarnica'; ?>
<h1>Nova poslastičarnica</h1>

<?php foreach ($errors as $error): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
<?php endforeach; ?>

<form method="post" action="/poslasticarnice" class="form card">
    <?= csrf_field() ?>
    <label>Naziv poslastičarnice
        <input name="name" value="<?= e($old['name']) ?>" required data-slug-source>
    </label>
    <label>Poddomen
        <span class="input-suffix">
            <input name="slug" value="<?= e($old['slug']) ?>" required pattern="[a-z0-9-]+" data-slug-target>
            <span>.<?= e($mainDomain) ?></span>
        </span>
        <small>Mala slova, brojevi i crtica. Npr. „suzana" → suzana.<?= e($mainDomain) ?></small>
    </label>
    <label>Email poslastičara (prijava i porudžbine)
        <input type="email" name="email" value="<?= e($old['email']) ?>" required>
    </label>
    <div class="grid-2">
        <label>Telefon <input name="phone" value="<?= e($old['phone']) ?>"></label>
        <label>Instagram (link ili @nalog) <input name="instagram" value="<?= e($old['instagram']) ?>"></label>
    </div>
    <label>Kratak opis (ispod naziva)
        <input name="tagline" value="<?= e($old['tagline']) ?>" placeholder="Torte po vašoj želji">
    </label>
    <div class="grid-2">
        <label>Početni katalog
            <select name="template">
                <?php foreach (\App\Services\CatalogTemplate::TEMPLATES as $k => $label): ?>
                    <option value="<?= e($k) ?>" <?= $old['template'] === $k ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Status
            <select name="status">
                <?php foreach (\App\Shop::STATUSES as $k => $label): ?>
                    <option value="<?= e($k) ?>" <?= $old['status'] === $k ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
    </div>
    <p class="muted">Lozinka za poslastičara se pravi automatski i prikazuje se na sledećoj stranici.</p>
    <button class="btn btn-primary" type="submit">Napravi</button>
</form>
