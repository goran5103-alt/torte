<?php $title = $shop['name']; ?>
<div class="page-head">
    <h1><?= e($shop['name']) ?></h1>
    <div class="actions">
        <a class="btn" href="<?= e($url) ?>" target="_blank" rel="noopener">Otvori stranicu</a>
        <a class="btn btn-primary" href="/poslasticarnice/<?= (int)$shop['id'] ?>/udji" target="_blank" rel="noopener">Uđi u admin</a>
    </div>
</div>

<?php if ($newPassword): ?>
    <div class="alert alert-success">
        <strong>Pristup za poslastičara</strong> (lozinka se prikazuje samo sada):<br>
        Adresa: <code><?= e($url) ?>/admin</code><br>
        Email: <code><?= e($owner['email'] ?? '') ?></code><br>
        Lozinka: <code><?= e($newPassword) ?></code>
    </div>
<?php endif; ?>

<div class="grid-2 align-start">
    <form method="post" action="/poslasticarnice/<?= (int)$shop['id'] ?>" class="form card">
        <?= csrf_field() ?>
        <h2>Osnovno</h2>
        <label>Naziv <input name="name" value="<?= e($shop['name']) ?>" required></label>
        <label>Status
            <select name="status">
                <?php foreach (\App\Shop::STATUSES as $k => $label): ?>
                    <option value="<?= e($k) ?>" <?= $shop['status'] === $k ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Sopstveni domen (opciono)
            <input name="custom_domain" value="<?= e($shop['custom_domain'] ?? '') ?>" placeholder="porudzbine.primer.rs">
        </label>
        <label class="check"><input type="checkbox" name="noindex" <?= $shop['noindex'] ? 'checked' : '' ?>> Sakrij od Google-a (noindex)</label>
        <h2>AI dizajn</h2>
        <label class="check"><input type="checkbox" name="ai_enabled" <?= $settings['ai']['enabled'] ? 'checked' : '' ?>> AI dizajn uključen</label>
        <label>Mesečni limit generisanja (po 6 slika)
            <input type="number" min="0" name="ai_monthly_limit" value="<?= (int)$settings['ai']['monthly_limit'] ?>">
        </label>
        <button class="btn btn-primary" type="submit">Sačuvaj</button>
    </form>

    <div>
        <div class="card">
            <h2>Poddomen na hostingu</h2>
            <p>Dok wildcard poddomen nije podešen, u DirectAdmin-u:</p>
            <ol class="steps">
                <li>Subdomain Management → napravite <code><?= e($shop['slug']) ?></code> (za <?= e($mainDomain) ?>).</li>
                <li>U folderu <code>public_html/<?= e($shop['slug']) ?></code> obrišite <code>index.html</code> i uploadujte <code>index.php</code> i <code>.htaccess</code> iz foldera <code>subdomain-stub</code>.</li>
                <li>SSL: uključite sertifikat za <code><?= e($shop['slug'] . '.' . $mainDomain) ?></code>.</li>
            </ol>
        </div>

        <div class="card">
            <h2>Nalog poslastičara</h2>
            <p>Email: <strong><?= e($owner['email'] ?? '—') ?></strong><br>
                Poslednja prijava: <?= e(datetime_sr($owner['last_login_at'] ?? null) ?: 'nikad') ?></p>
            <form method="post" action="/poslasticarnice/<?= (int)$shop['id'] ?>/lozinka" class="inline"
                  data-confirm="Napraviti novu lozinku? Stara prestaje da važi.">
                <?= csrf_field() ?>
                <button class="btn btn-small">Nova lozinka</button>
            </form>
        </div>

        <div class="card">
            <h2>AI potrošnja</h2>
            <?php if (!$usage): ?>
                <p class="muted">Još nema.</p>
            <?php else: ?>
                <table class="table compact">
                    <tr><th>Mesec</th><th>Vrsta</th><th>Broj</th><th>Trošak</th></tr>
                    <?php foreach ($usage as $u): ?>
                        <tr><td><?= e($u['month']) ?></td><td><?= ['briefs' => 'opisi', 'image' => 'slike', 'check' => 'provere'][$u['kind']] ?? e($u['kind']) ?></td>
                            <td><?= (int)$u['cnt'] ?></td><td>$<?= number_format((float)$u['cost'], 3) ?></td></tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>

        <form method="post" action="/poslasticarnice/<?= (int)$shop['id'] ?>/obrisi" class="card danger form">
            <?= csrf_field() ?>
            <h2>Brisanje</h2>
            <p>Briše poslastičarnicu, porudžbine i slike. Ne može se vratiti.</p>
            <label>Upišite <code><?= e($shop['slug']) ?></code> za potvrdu <input name="confirm" autocomplete="off"></label>
            <button class="btn btn-danger">Obriši</button>
        </form>
    </div>
</div>
