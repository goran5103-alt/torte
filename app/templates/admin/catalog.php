<?php
$title = 'Katalog';
$cur = \App\Shop::currency();
$tabs = ['velicine' => 'Veličine torti', 'ukusi' => 'Ukusi', 'kolaci' => 'Kolači'];
$tab = isset($tabs[$tab]) ? $tab : 'velicine';
// Dugme za brisanje je u istoj formi (forme ne smeju da se ugnežđuju), šalje na drugu adresu.
$deleteForm = fn(string $type, int $id) => '<button class="btn btn-small" formaction="/admin/katalog/' . $type . '/' . $id . '/obrisi" formnovalidate data-confirm-click="Obrisati?">Obriši</button>';
?>
<h1>Katalog</h1>
<div class="filters">
    <?php foreach ($tabs as $k => $label): ?>
        <a href="/admin/katalog?t=<?= $k ?>" class="<?= $tab === $k ? 'active' : '' ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
</div>

<?php if ($tab === 'velicine'): ?>
    <div class="card">
        <p class="muted">Cena torte = težina × cena ukusa po kg (+ doplata za složenu dekoraciju).</p>
        <?php foreach (array_merge($sizes, [null]) as $s): ?>
            <form method="post" action="/admin/katalog/velicine" class="form catalog-row sizes">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int)($s['id'] ?? 0) ?>">
                <label>Naziv <input name="name" value="<?= e($s['name'] ?? '') ?>" placeholder="<?= $s ? '' : 'Nova veličina, npr. Srednja (12–15 osoba)' ?>" required></label>
                <label>Osoba <input type="number" name="persons" min="0" value="<?= e($s['persons'] ?? '') ?>"></label>
                <label>Težina kg <input type="number" step="0.1" min="0.1" name="weight_kg" value="<?= e($s ? (float)$s['weight_kg'] : '') ?>" required></label>
                <label>Spratova <input type="number" min="1" max="5" name="tiers" value="<?= e($s['tiers'] ?? 1) ?>"></label>
                <label>Redosled <input type="number" name="sort" value="<?= e($s['sort'] ?? 0) ?>"></label>
                <div class="row-actions">
                    <label class="check"><input type="checkbox" name="active" <?= !$s || $s['active'] ? 'checked' : '' ?>> aktivno</label>
                    <button class="btn btn-small btn-primary"><?= $s ? 'Sačuvaj' : 'Dodaj' ?></button>
                    <?= $s ? $deleteForm('velicine', (int)$s['id']) : '' ?>
                </div>
            </form>
        <?php endforeach; ?>
    </div>
<?php elseif ($tab === 'ukusi'): ?>
    <div class="card">
        <?php foreach (array_merge($flavors, [null]) as $f): ?>
            <form method="post" action="/admin/katalog/ukusi" class="form catalog-row flavors">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int)($f['id'] ?? 0) ?>">
                <label>Naziv <input name="name" value="<?= e($f['name'] ?? '') ?>" placeholder="<?= $f ? '' : 'Novi ukus' ?>" required></label>
                <label>Opis <input name="description" value="<?= e($f['description'] ?? '') ?>"></label>
                <label>Alergeni <input name="allergens" value="<?= e($f['allergens'] ?? '') ?>"></label>
                <label>Cena/kg (<?= e($cur) ?>) <input type="number" step="10" min="0" name="price_per_kg" value="<?= e($f ? (float)$f['price_per_kg'] : '') ?>" required></label>
                <label>Redosled <input type="number" name="sort" value="<?= e($f['sort'] ?? 0) ?>"></label>
                <div class="row-actions">
                    <label class="check"><input type="checkbox" name="active" <?= !$f || $f['active'] ? 'checked' : '' ?>> aktivno</label>
                    <button class="btn btn-small btn-primary"><?= $f ? 'Sačuvaj' : 'Dodaj' ?></button>
                    <?= $f ? $deleteForm('ukusi', (int)$f['id']) : '' ?>
                </div>
            </form>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="card">
        <?php foreach (array_merge($products, [null]) as $pr): ?>
            <form method="post" action="/admin/katalog/kolaci" class="form catalog-row products" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int)($pr['id'] ?? 0) ?>">
                <label>Naziv <input name="name" value="<?= e($pr['name'] ?? '') ?>" placeholder="<?= $pr ? '' : 'Novi proizvod' ?>" required></label>
                <label>Opis <input name="description" value="<?= e($pr['description'] ?? '') ?>"></label>
                <label>Jedinica
                    <select name="unit">
                        <option value="kg" <?= ($pr['unit'] ?? 'kg') === 'kg' ? 'selected' : '' ?>>kg</option>
                        <option value="kom" <?= ($pr['unit'] ?? '') === 'kom' ? 'selected' : '' ?>>kom</option>
                    </select>
                </label>
                <label>Cena (<?= e($cur) ?>) <input type="number" step="10" min="0" name="price" value="<?= e($pr ? (float)$pr['price'] : '') ?>" required></label>
                <label>Min. <input type="number" step="0.5" min="0.1" name="min_qty" value="<?= e($pr ? (float)$pr['min_qty'] : 1) ?>"></label>
                <label>Korak <input type="number" step="0.5" min="0.1" name="step" value="<?= e($pr ? (float)$pr['step'] : 1) ?>"></label>
                <label>Red. <input type="number" name="sort" value="<?= e($pr['sort'] ?? 0) ?>"></label>
                <div class="row-actions">
                    <label class="check"><input type="checkbox" name="active" <?= !$pr || $pr['active'] ? 'checked' : '' ?>> aktivno</label>
                    <button class="btn btn-small btn-primary"><?= $pr ? 'Sačuvaj' : 'Dodaj' ?></button>
                    <?= $pr ? $deleteForm('kolaci', (int)$pr['id']) : '' ?>
                </div>
                <label style="grid-column:1/-1">Alergeni <input name="allergens" value="<?= e($pr['allergens'] ?? '') ?>"></label>
                <label style="grid-column:1/-1">Slika (opciono) <input type="file" name="image" accept="image/jpeg,image/png,image/webp">
                    <?php if (!empty($pr['image_path'])): ?><small>Trenutna: <a href="<?= e(upload_url($pr['image_path'])) ?>" target="_blank">pogledaj</a></small><?php endif; ?>
                </label>
            </form>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
