<?php $title = 'Poslastičarnice'; ?>
<?php if ($pending): ?>
    <div class="alert alert-warning">
        Postoje nove izmene baze (<?= e(implode(', ', $pending)) ?>).
        <form method="post" action="/baza/azuriraj" class="inline">
            <?= csrf_field() ?>
            <button class="btn btn-small">Ažuriraj bazu</button>
        </form>
    </div>
<?php endif; ?>

<div class="page-head">
    <h1>Poslastičarnice</h1>
    <div class="actions">
        <form method="post" action="/test-ai" class="inline">
            <?= csrf_field() ?>
            <button class="btn" title="Proba Claude, servis za slike i proveru izvodljivosti (traje do 2 minuta)">Testiraj AI</button>
        </form>
        <a class="btn btn-primary" href="/poslasticarnice/nova">+ Nova poslastičarnica</a>
    </div>
</div>

<div class="stats">
    <div class="stat"><span><?= count($shops) ?></span>poslastičarnica</div>
    <div class="stat"><span>$<?= number_format($aiTotal, 2) ?></span>AI trošak ovog meseca</div>
    <div class="stat"><span><?= $aiReady ? 'Claude' : 'demo' ?></span>opisi dizajna</div>
    <div class="stat"><span><?= e($imageProvider) ?></span>slike</div>
    <div class="stat"><span><?= e($checkModel) ?></span>provera izvodljivosti</div>
</div>

<?php if (!$shops): ?>
    <div class="card empty">Još nema poslastičarnica. <a href="/poslasticarnice/nova">Napravite prvu</a>.</div>
<?php else: ?>
    <div class="table-wrap">
    <table class="table">
        <thead>
        <tr><th>Naziv</th><th>Adresa</th><th>Status</th><th>Porudžbine (mesec / ukupno)</th><th>AI dizajni (mesec)</th><th>AI trošak</th></tr>
        </thead>
        <tbody>
        <?php foreach ($shops as $s): ?>
            <tr>
                <td><a href="/poslasticarnice/<?= (int)$s['id'] ?>"><strong><?= e($s['name']) ?></strong></a></td>
                <td><a href="<?= e(\App\Shop::baseUrl($s)) ?>" target="_blank" rel="noopener"><?= e($s['custom_domain'] ?: $s['slug'] . '.' . \App\Config::get('main_domain')) ?></a></td>
                <td><span class="badge badge-<?= e($s['status']) ?>"><?= e(\App\Shop::STATUSES[$s['status']] ?? $s['status']) ?></span></td>
                <td><?= (int)$s['orders_month'] ?> / <?= (int)$s['orders_total'] ?></td>
                <td><?= (int)$s['designs_month'] ?></td>
                <td>$<?= number_format((float)$s['ai_cost_month'], 2) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
<?php endif; ?>
