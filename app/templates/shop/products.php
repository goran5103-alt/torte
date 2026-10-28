<?php $title = 'Kolači'; ?>
<h1>Kolači</h1>
<?php if (!$products): ?>
    <p class="muted">Trenutno nema kolača u ponudi.</p>
<?php else: ?>
<form method="post" action="/korpa/kolaci" class="product-list">
    <?= csrf_field() ?>
    <?php foreach ($products as $prod): ?>
        <div class="product">
            <?php if ($prod['image_path']): ?>
                <img src="<?= e(upload_url($prod['image_path'])) ?>" alt="" loading="lazy">
            <?php endif; ?>
            <div class="product-info">
                <strong><?= e($prod['name']) ?></strong>
                <?php if ($prod['description']): ?><p><?= e($prod['description']) ?></p><?php endif; ?>
                <span class="price"><?= e(money($prod['price'], \App\Shop::currency())) ?> / <?= e($prod['unit']) ?></span>
                <?php if ($prod['allergens']): ?><small>Alergeni: <?= e($prod['allergens']) ?></small><?php endif; ?>
            </div>
            <label class="qty">
                <span class="sr-only">Količina</span>
                <input type="number" inputmode="decimal" name="qty[<?= (int)$prod['id'] ?>]" min="0"
                       step="<?= e((float)$prod['step']) ?>" placeholder="0">
                <span><?= e($prod['unit']) ?></span>
            </label>
            <?php if ((float)$prod['min_qty'] > 1 || $prod['unit'] === 'kg'): ?>
                <small class="min">Najmanje <?= e(qty($prod['min_qty'])) ?> <?= e($prod['unit']) ?></small>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
    <div class="sticky-actions">
        <button class="btn btn-primary btn-block" type="submit">Dodaj u porudžbinu</button>
    </div>
</form>
<?php endif; ?>
