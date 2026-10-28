<?php
$title = 'Kolači';
$scripts = ['products.js'];
$cur = \App\Shop::currency();
?>
<h1>Kolači</h1>
<?php if (!$products): ?>
    <p class="muted">Trenutno nema kolača u ponudi.</p>
<?php else: ?>
<form method="post" action="/korpa/kolaci" class="product-list" id="products" data-currency="<?= e($cur) ?>">
    <?= csrf_field() ?>
    <?php foreach ($products as $prod):
        $isKg = $prod['unit'] === 'kg';
        $step = (float)$prod['step'] > 0 ? (float)$prod['step'] : 1;
        $min = max((float)$prod['min_qty'], $step);
    ?>
        <div class="product" data-product data-price="<?= e((float)$prod['price']) ?>" data-step="<?= e($step) ?>" data-min="<?= e($min) ?>" data-unit="<?= e($prod['unit']) ?>">
            <div class="product-img">
                <?php if ($prod['image_path']): ?>
                    <img src="<?= e(upload_url($prod['image_path'])) ?>" alt="<?= e($prod['name']) ?>" loading="lazy">
                <?php else: ?>
                    <span aria-hidden="true">🧁</span>
                <?php endif; ?>
            </div>
            <div class="product-info">
                <strong><?= e($prod['name']) ?></strong>
                <?php if ($prod['description']): ?><p><?= e($prod['description']) ?></p><?php endif; ?>
                <span class="price"><?= e(money($prod['price'], $cur)) ?> <?= $isKg ? 'po kg' : 'po komadu' ?></span>
                <?php if ($prod['allergens']): ?><small>Alergeni: <?= e($prod['allergens']) ?></small><?php endif; ?>
                <div class="stepper">
                    <button type="button" data-minus aria-label="Manje">−</button>
                    <output data-value>0 <?= $isKg ? 'kg' : 'kom' ?></output>
                    <button type="button" data-plus aria-label="Više">+</button>
                    <input type="hidden" name="qty[<?= (int)$prod['id'] ?>]" value="0">
                </div>
                <small>Najmanje <?= e(qty($min)) ?> <?= $isKg ? 'kg' : 'kom' ?><?= $step != $min ? ', zatim po ' . e(qty($step)) . ($isKg ? ' kg' : ' kom') : '' ?></small>
            </div>
        </div>
    <?php endforeach; ?>
    <div class="sticky-actions">
        <button class="btn btn-primary btn-block" type="submit" data-submit disabled>Izaberite količinu</button>
    </div>
</form>
<?php endif; ?>
