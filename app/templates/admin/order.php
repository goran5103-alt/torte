<?php
$title = 'Porudžbina #' . $order['number'];
$cur = \App\Shop::currency();
$phone = preg_replace('/[^0-9+]/', '', $order['customer_phone']);
$intl = str_starts_with($phone, '+') ? $phone : '+381' . ltrim($phone, '0');
?>
<div class="page-head">
    <h1>Porudžbina #<?= (int)$order['number'] ?></h1>
    <div class="actions no-print">
        <a class="btn" href="tel:<?= e($phone) ?>">📞 Pozovi</a>
        <a class="btn" href="viber://chat?number=<?= e(urlencode($intl)) ?>">Viber</a>
        <button class="btn" onclick="window.print()">Štampaj</button>
    </div>
</div>

<div class="grid-2 align-start">
    <div>
        <div class="card">
            <dl class="kv">
                <dt><?= $order['fulfillment'] === 'dostava' ? 'Dostava' : 'Preuzimanje' ?></dt>
                <dd><strong><?= e(day_name_sr($order['due_date'])) ?>, <?= e(date_sr($order['due_date'])) ?></strong><?= $order['due_time'] ? ', ' . e($order['due_time']) : '' ?></dd>
                <?php if ($order['fulfillment'] === 'dostava'): ?>
                    <dt>Adresa</dt><dd><?= e($order['delivery_address']) ?></dd>
                <?php endif; ?>
                <dt>Kupac</dt><dd><?= e($order['customer_name']) ?></dd>
                <dt>Telefon</dt><dd><a href="tel:<?= e($phone) ?>"><?= e($order['customer_phone']) ?></a></dd>
                <?php if ($order['customer_email']): ?><dt>Email</dt><dd><a href="mailto:<?= e($order['customer_email']) ?>"><?= e($order['customer_email']) ?></a></dd><?php endif; ?>
                <?php if ($order['customer_note']): ?><dt>Napomena</dt><dd><?= nl2br(e($order['customer_note'])) ?></dd><?php endif; ?>
                <dt>Primljeno</dt><dd><?= e(datetime_sr($order['created_at'])) ?></dd>
            </dl>
        </div>

        <?php foreach ($items as $item): $d = $item['details']; ?>
            <div class="card">
                <h2><?= e($item['name']) ?> <span class="muted">· <?= e(money($item['total'], $cur)) ?></span></h2>
                <?php if ($item['type'] === 'cake'): ?>
                    <?php if ($item['image_path']): ?>
                        <a href="<?= e(upload_url($item['image_path'])) ?>" target="_blank"><img class="design-preview" src="<?= e(upload_url($item['image_path'])) ?>" alt=""></a>
                    <?php endif; ?>
                    <dl class="kv" style="margin-top:1rem">
                        <dt>Veličina</dt><dd><?= e($d['size'] ?? '') ?> (<?= e(qty($d['weight_kg'] ?? 0)) ?> kg<?= ($d['tiers'] ?? 1) > 1 ? ', ' . (int)$d['tiers'] . ' sprata' : '' ?>)</dd>
                        <dt>Ukus</dt><dd><?= e($d['flavor'] ?? '') ?></dd>
                        <?php if (!empty($d['occasion'])): ?><dt>Povod</dt><dd><?= e($d['occasion']) ?></dd><?php endif; ?>
                        <dt>Izgled</dt><dd>
                            <strong><?= e($d['design_title'] ?? '') ?></strong>
                            <?php if (($d['design_mode'] ?? '') === 'ai'): ?><span class="badge">AI predlog</span><?php endif; ?>
                            <?php if (!empty($d['design_description'])): ?><br><?= e($d['design_description']) ?><?php endif; ?>
                        </dd>
                        <?php if (!empty($d['design_request'])): ?><dt>Želja kupca</dt><dd><?= nl2br(e($d['design_request'])) ?></dd><?php endif; ?>
                        <?php if (!empty($d['design_colors'])): ?><dt>Boje</dt><dd><?= e(implode(', ', $d['design_colors'])) ?></dd><?php endif; ?>
                        <?php if (!empty($d['design_techniques'])): ?><dt>Tehnike</dt><dd><?= e(implode(', ', array_map(fn($t) => \App\Shop::TECHNIQUES[$t][0] ?? $t, $d['design_techniques']))) ?></dd><?php endif; ?>
                        <?php if (!empty($d['design_check'])): $c = $d['design_check']; ?>
                            <dt>AI provera</dt><dd>
                                <span class="badge <?= $c['score'] >= 7 ? 'badge-active' : 'badge-u_izradi' ?>">izvodljivost <?= (int)$c['score'] ?>/10</span>
                                <?php if (!empty($c['note'])): ?><br><?= e($c['note']) ?><?php endif; ?>
                                <?php if (!empty($c['problems'])): ?><br><small>Na šta paziti: <?= e(implode('; ', $c['problems'])) ?></small><?php endif; ?>
                            </dd>
                        <?php endif; ?>
                        <?php if (!empty($d['gallery_code'])): ?><dt>Iz galerije</dt><dd><?= e($d['gallery_code']) ?></dd><?php endif; ?>
                        <?php if (!empty($d['inscription'])): ?><dt>Natpis</dt><dd>„<?= e($d['inscription']) ?>"</dd><?php endif; ?>
                        <?php if (!empty($d['note'])): ?><dt>Napomena</dt><dd><?= nl2br(e($d['note'])) ?></dd><?php endif; ?>
                        <?php if (!empty($d['allergens'])): ?><dt>Alergeni</dt><dd><?= e($d['allergens']) ?></dd><?php endif; ?>
                        <dt>Cena</dt><dd><?= e(money($d['base_price'] ?? 0, $cur)) ?><?= !empty($d['surcharge']) ? ' + ' . e(money($d['surcharge'], $cur)) . ' dekoracija' : '' ?></dd>
                    </dl>
                <?php else: ?>
                    <p><?= e(qty($item['quantity'])) ?> <?= e($item['unit']) ?> × <?= e(money($item['unit_price'], $cur)) ?></p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <form method="post" action="/admin/porudzbine/<?= (int)$order['id'] ?>" class="form card no-print">
        <?= csrf_field() ?>
        <h2>Status i cena</h2>
        <label>Status
            <select name="status">
                <?php foreach ($statuses as $k => $label): ?>
                    <option value="<?= e($k) ?>" <?= $order['status'] === $k ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="check"><input type="checkbox" name="notify_customer" <?= $order['customer_email'] ? 'checked' : 'disabled' ?>>
            Javi kupcu emailom kad se status promeni<?= $order['customer_email'] ? '' : ' (kupac nije ostavio email)' ?></label>
        <p class="muted">Okvirna cena: <?= e(money($order['estimated_total'], $cur)) ?><?= (float)$order['delivery_fee'] > 0 ? ' (sa dostavom ' . e(money($order['delivery_fee'], $cur)) . ')' : '' ?></p>
        <label>Konačna cena (<?= e($cur) ?>)
            <input type="number" step="10" min="0" name="final_total" value="<?= e($order['final_total'] ?? '') ?>" placeholder="<?= e((float)$order['estimated_total']) ?>">
        </label>
        <label>Avans (<?= e($cur) ?>)
            <input type="number" step="10" min="0" name="deposit_amount" value="<?= e((float)$order['deposit_amount']) ?>">
        </label>
        <label class="check"><input type="checkbox" name="deposit_paid" <?= $order['deposit_paid'] ? 'checked' : '' ?>> Avans je plaćen</label>
        <label>Interna beleška (kupac je ne vidi)
            <textarea name="internal_note"><?= e($order['internal_note'] ?? '') ?></textarea>
        </label>
        <button class="btn btn-primary" type="submit">Sačuvaj</button>
        <p class="muted" style="margin-top:1rem">Stranica za kupca: <a href="<?= e($orderUrl) ?>" target="_blank">otvori</a></p>
    </form>
</div>
