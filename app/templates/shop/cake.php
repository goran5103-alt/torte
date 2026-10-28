<?php
$title = 'Torta';
$scripts = ['cake.js'];
$cur = \App\Shop::currency();
?>
<?php if ($empty): ?>
    <h1>Torte</h1>
    <p class="muted">Ponuda torti se upravo priprema. Pozovite nas za porudžbinu.</p>
<?php else: ?>
<div id="cake-wizard" data-csrf="<?= e(\App\Csrf::token()) ?>">
    <script type="application/json" id="cake-config"><?= json_encode($config, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>

    <ol class="progress" aria-label="Koraci">
        <li data-step-dot="1" class="active">Veličina</li>
        <li data-step-dot="2">Ukus</li>
        <li data-step-dot="3">Izgled</li>
        <li data-step-dot="4">Pregled</li>
    </ol>

    <!-- 1. Veličina -->
    <section class="step" data-step="1">
        <h1>Koliko osoba?</h1>
        <div class="options">
            <?php foreach ($config['sizes'] as $s): ?>
                <label class="option">
                    <input type="radio" name="size" value="<?= (int)$s['id'] ?>">
                    <span class="option-body">
                        <strong><?= e($s['name']) ?></strong>
                        <span>oko <?= e(qty($s['kg'])) ?> kg<?= $s['tiers'] > 1 ? ' · ' . (int)$s['tiers'] . ' sprata' : '' ?></span>
                    </span>
                </label>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- 2. Ukus -->
    <section class="step" data-step="2" hidden>
        <h1>Izaberite ukus</h1>
        <div class="options">
            <?php foreach ($config['flavors'] as $f): ?>
                <label class="option">
                    <input type="radio" name="flavor" value="<?= (int)$f['id'] ?>">
                    <span class="option-body">
                        <strong><?= e($f['name']) ?></strong>
                        <?php if ($f['description']): ?><span><?= e($f['description']) ?></span><?php endif; ?>
                        <?php if ($f['allergens']): ?><small>Alergeni: <?= e($f['allergens']) ?></small><?php endif; ?>
                    </span>
                    <span class="option-price"><?= e(money($f['pricePerKg'], $cur)) ?>/kg</span>
                </label>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- 3. Izgled -->
    <section class="step" data-step="3" hidden>
        <h1>Kako da izgleda?</h1>
        <div class="tabs" role="tablist">
            <?php if ($config['aiEnabled']): ?>
                <button type="button" role="tab" data-mode="ai" class="active">✨ AI predlozi</button>
            <?php endif; ?>
            <?php if ($config['gallery']): ?>
                <button type="button" role="tab" data-mode="galerija" class="<?= !$config['aiEnabled'] ? 'active' : '' ?>">Iz galerije</button>
            <?php endif; ?>
            <button type="button" role="tab" data-mode="opis" class="<?= !$config['aiEnabled'] && !$config['gallery'] ? 'active' : '' ?>">Opisaću sam</button>
        </div>

        <div class="field-group" data-for="ai opis">
            <label>Povod
                <select name="occasion">
                    <option value="">— izaberite —</option>
                    <?php foreach ($config['occasions'] as $k => $label): ?>
                        <option value="<?= e($k) ?>"><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label><span data-label-ai>Motiv ili tema</span>
                <textarea name="description" rows="3" maxlength="500"
                          placeholder="Npr. rođendan za devojčicu od 5 godina, jednorozi i duga, nežne boje"></textarea>
            </label>
            <div class="label">Boje (do 4)</div>
            <div class="colors" data-colors>
                <?php foreach ($config['colors'] as $c): ?>
                    <button type="button" class="color" data-color="<?= e($c['name']) ?>" style="--c: <?= e($c['hex']) ?>"><?= e($c['name']) ?></button>
                <?php endforeach; ?>
            </div>
            <label>Natpis na torti (opciono)
                <input name="inscription" maxlength="40" placeholder="Srećan 5. rođendan, Mila!">
            </label>
        </div>

        <?php if ($config['aiEnabled']): ?>
            <div class="mode-panel" data-panel="ai">
                <button type="button" class="btn btn-primary btn-block" data-generate>✨ Napravi 6 predloga</button>
                <p class="hint" data-remaining></p>
                <div class="ai-status" data-ai-status hidden></div>
                <div class="designs" data-designs></div>
                <p class="hint disclaimer" data-disclaimer hidden>Slike su ilustracija. Torta se izrađuje ručno i po uzoru na izabrani predlog, ali neće biti identična slici.</p>
            </div>
        <?php endif; ?>

        <?php if ($config['gallery']): ?>
            <div class="mode-panel" data-panel="galerija" hidden>
                <p class="hint">Izaberite tortu iz naše galerije. Napravićemo sličnu u izabranoj veličini i ukusu.</p>
                <div class="gallery-pick">
                    <?php foreach ($config['gallery'] as $g): ?>
                        <button type="button" class="pick" data-gallery="<?= (int)$g['id'] ?>">
                            <img src="<?= e($g['url']) ?>" alt="<?= e($g['title'] ?: $g['code']) ?>" loading="lazy">
                            <span><?= e($g['code']) ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <label class="note-field">Napomena za poslastičara (opciono)
            <textarea name="note" rows="2" maxlength="1000" placeholder="Npr. bez orašastih plodova, figurica psa umesto mačke…"></textarea>
        </label>
    </section>

    <!-- 4. Pregled -->
    <section class="step" data-step="4" hidden>
        <h1>Vaša torta</h1>
        <div class="summary" data-summary></div>
        <p class="hint">Cena je okvirna. Poslastičar je potvrđuje nakon pregleda porudžbine.</p>
    </section>

    <div class="wizard-bar">
        <button type="button" class="btn" data-back hidden>Nazad</button>
        <div class="estimate" data-estimate></div>
        <button type="button" class="btn btn-primary" data-next>Dalje</button>
    </div>
    <div class="notice notice-error" data-error hidden></div>
</div>
<?php endif; ?>
