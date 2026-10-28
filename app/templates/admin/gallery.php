<?php $title = 'Galerija'; ?>
<h1>Galerija radova</h1>
<p class="muted">Kupci biraju tortu „po uzoru na" sliku iz galerije. Svaka slika dobija šifru (npr. T-001), kao na vašem sajtu.</p>

<form method="post" action="/admin/galerija" enctype="multipart/form-data" class="form card">
    <?= csrf_field() ?>
    <div class="grid-2">
        <label>Slike (može više odjednom)
            <input type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple required>
        </label>
        <label>Prefiks šifre
            <input name="prefix" value="T" maxlength="5">
        </label>
    </div>
    <button class="btn btn-primary">Dodaj slike</button>
</form>

<?php if (!$images): ?>
    <div class="card empty">Galerija je prazna.</div>
<?php else: ?>
    <div class="gallery-grid">
        <?php foreach ($images as $g): ?>
            <div class="gallery-item">
                <img src="<?= e(upload_url($g['image_path'])) ?>" alt="" loading="lazy">
                <form method="post" action="/admin/galerija/<?= (int)$g['id'] ?>">
                    <?= csrf_field() ?>
                    <input name="code" value="<?= e($g['code']) ?>" placeholder="Šifra">
                    <input name="title" value="<?= e($g['title']) ?>" placeholder="Opis (opciono)">
                    <input type="number" name="sort" value="<?= (int)$g['sort'] ?>" title="Redosled">
                    <label class="check"><input type="checkbox" name="active" <?= $g['active'] ? 'checked' : '' ?> style="width:auto"> prikaži</label>
                    <button class="btn btn-small btn-primary">Sačuvaj</button>
                </form>
                <form method="post" action="/admin/galerija/<?= (int)$g['id'] ?>/obrisi" data-confirm="Obrisati sliku?">
                    <?= csrf_field() ?>
                    <button class="btn btn-small">Obriši</button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
