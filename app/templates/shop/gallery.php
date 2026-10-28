<?php $title = 'Galerija'; ?>
<h1>Naši radovi</h1>
<p class="muted">Kliknite na tortu koja vam se dopada i poručite sličnu.</p>
<?php if (!$images): ?>
    <p class="muted">Galerija je trenutno prazna.</p>
<?php else: ?>
    <div class="gallery-grid">
        <?php foreach ($images as $g): ?>
            <a href="/torta?galerija=<?= (int)$g['id'] ?>">
                <img src="<?= e(upload_url($g['image_path'])) ?>" alt="<?= e($g['title'] ?: 'Torta ' . $g['code']) ?>" loading="lazy">
                <span><?= e($g['code']) ?><?= $g['title'] ? ' · ' . e($g['title']) : '' ?></span>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
