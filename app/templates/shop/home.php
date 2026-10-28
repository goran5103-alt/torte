<section class="hero">
    <h1><?= e($shop['name']) ?></h1>
    <?php if ($settings['tagline']): ?><p class="lead"><?= e($settings['tagline']) ?></p><?php endif; ?>
</section>

<div class="choice-grid">
    <?php if ($hasCakes): ?>
        <a class="choice" href="/torta">
            <span class="choice-icon" aria-hidden="true">🎂</span>
            <strong>Torte</strong>
            <span>Izaberite veličinu, ukus i izgled. Uz pomoć AI-a vidite kako bi vaša torta mogla da izgleda.</span>
        </a>
    <?php endif; ?>
    <?php if ($hasProducts): ?>
        <a class="choice" href="/kolaci">
            <span class="choice-icon" aria-hidden="true">🧁</span>
            <strong>Kolači</strong>
            <span>Sitni kolači, krempite, mafini i ostalo, po kilogramu ili komadu.</span>
        </a>
    <?php endif; ?>
</div>

<?php if ($settings['about']): ?>
    <section class="about"><?= nl2br(e($settings['about'])) ?></section>
<?php endif; ?>

<?php if ($gallery): ?>
    <section>
        <div class="section-head">
            <h2>Naši radovi</h2>
            <a href="/galerija">Sve slike →</a>
        </div>
        <div class="gallery-strip">
            <?php foreach ($gallery as $g): ?>
                <a href="/torta?galerija=<?= (int)$g['id'] ?>" title="<?= e($g['title'] ?: $g['code']) ?>">
                    <img src="<?= e(upload_url($g['image_path'])) ?>" alt="<?= e($g['title'] ?: 'Torta ' . $g['code']) ?>" loading="lazy">
                    <?php if ($g['code']): ?><span><?= e($g['code']) ?></span><?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<section class="how">
    <h2>Kako poručiti</h2>
    <ol>
        <li>Izaberite tortu ili kolače.</li>
        <li>Izaberite datum preuzimanja<?= $settings['delivery']['enabled'] ? ' ili dostave' : '' ?>.</li>
        <li>Pošaljite porudžbinu, a mi vas kontaktiramo radi potvrde.</li>
    </ol>
    <?php if ((float)$settings['deposit']['percent'] > 0): ?>
        <p class="muted">Za torte po porudžbini uzimamo avans od <?= (float)$settings['deposit']['percent'] ?>%. Uplatu možete izvršiti skeniranjem QR koda u mobilnom bankarstvu.</p>
    <?php endif; ?>
</section>
