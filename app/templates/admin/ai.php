<?php
$title = 'AI i tehnike';
$cap = $settings['capabilities'];
$cur = \App\Shop::currency();
?>
<h1>AI predlozi i vaše tehnike</h1>
<p class="muted">AI kupcu predlaže samo dizajne koje vi možete da napravite. Označite šta radite.</p>

<?php if (!$settings['ai']['enabled']): ?>
    <div class="alert alert-warning">AI predlozi su trenutno isključeni za vašu poslastičarnicu.</div>
<?php endif; ?>

<form method="post" action="/admin/ai" class="form">
    <?= csrf_field() ?>
    <div class="grid-2 align-start">
        <div class="card">
            <h2>Tehnike koje radim</h2>
            <div class="tech-list">
                <?php foreach (\App\Shop::TECHNIQUES as $key => [$label]): ?>
                    <label class="check"><input type="checkbox" name="techniques[<?= e($key) ?>]" <?= in_array($key, $cap['techniques'], true) ? 'checked' : '' ?>> <?= e($label) ?></label>
                <?php endforeach; ?>
            </div>
            <h2>Figurice</h2>
            <?php foreach (\App\Shop::FIGURINES as $key => $label): ?>
                <label class="check"><input type="radio" name="figurines" value="<?= e($key) ?>" <?= $cap['figurines'] === $key ? 'checked' : '' ?>> <?= e($label) ?></label>
            <?php endforeach; ?>
            <label style="margin-top:1rem">Najviše spratova <input type="number" min="1" max="5" name="max_tiers" value="<?= (int)$cap['max_tiers'] ?>"></label>
            <label>Napomena za AI (opciono)
                <textarea name="notes" rows="3" maxlength="500" placeholder="Npr. ne radimo crnu boju, radimo samo okrugle torte"><?= e($cap['notes']) ?></textarea>
            </label>
        </div>
        <div class="card">
            <h2>Doplata za složenost dekoracije</h2>
            <p class="muted">AI svaki predlog ocenjuje od 1 do 3. Doplata se dodaje na cenu po kilogramu i kupac je vidi uz predlog.</p>
            <label>1 – jednostavno (<?= e($cur) ?>) <input type="number" min="0" step="100" name="surcharge_1" value="<?= e((float)$settings['design']['surcharge_1']) ?>"></label>
            <label>2 – srednje (<?= e($cur) ?>) <input type="number" min="0" step="100" name="surcharge_2" value="<?= e((float)$settings['design']['surcharge_2']) ?>"></label>
            <label>3 – složeno (<?= e($cur) ?>) <input type="number" min="0" step="100" name="surcharge_3" value="<?= e((float)$settings['design']['surcharge_3']) ?>"></label>
        </div>
    </div>
    <button class="btn btn-primary">Sačuvaj</button>
</form>
