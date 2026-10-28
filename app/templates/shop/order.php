<?php
$title = 'Porudžbina #' . $order['number'];
$cur = \App\Shop::currency();
$statuses = \App\Services\OrderService::STATUSES;
$total = $order['final_total'] !== null ? (float)$order['final_total'] : (float)$order['estimated_total'];
?>
<?php if ($justPlaced): ?>
    <div class="success-box">
        <div class="success-icon" aria-hidden="true">✓</div>
        <h1>Hvala, porudžbina je primljena!</h1>
        <p>Broj porudžbine: <strong>#<?= (int)$order['number'] ?></strong>. Uskoro ćemo vas kontaktirati radi potvrde.</p>
        <p class="hint">Sačuvajte ovu stranicu. Na njoj uvek vidite status porudžbine.</p>
    </div>
<?php else: ?>
    <h1>Porudžbina #<?= (int)$order['number'] ?></h1>
<?php endif; ?>

<div class="status-line">
    Status: <span class="status status-<?= e($order['status']) ?>"><?= e($statuses[$order['status']] ?? $order['status']) ?></span>
</div>

<dl class="order-meta">
    <dt><?= $order['fulfillment'] === 'dostava' ? 'Dostava' : 'Preuzimanje' ?></dt>
    <dd><?= e(day_name_sr($order['due_date'])) ?>, <?= e(date_sr($order['due_date'])) ?><?= $order['due_time'] ? ', ' . e($order['due_time']) : '' ?>
        <?php if ($order['fulfillment'] === 'dostava'): ?><br><?= e($order['delivery_address']) ?><?php endif; ?></dd>
    <dt>Ime</dt><dd><?= e($order['customer_name']) ?>, <?= e($order['customer_phone']) ?></dd>
</dl>

<div class="cart-items">
    <?php foreach ($items as $item): $d = $item['details']; ?>
        <div class="cart-item">
            <?php if ($item['image_path']): ?><img src="<?= e(upload_url($item['image_path'])) ?>" alt=""><?php endif; ?>
            <div class="cart-info">
                <strong><?= e($item['name']) ?></strong>
                <?php if ($item['type'] === 'cake'): ?>
                    <span><?= e($d['size'] ?? '') ?></span>
                    <span><?= e($d['design_title'] ?? '') ?></span>
                    <?php if (!empty($d['inscription'])): ?><span>Natpis: „<?= e($d['inscription']) ?>"</span><?php endif; ?>
                <?php else: ?>
                    <span><?= e(qty($item['quantity'])) ?> <?= e($item['unit']) ?></span>
                <?php endif; ?>
            </div>
            <div class="cart-price"><?= e(money($item['total'], $cur)) ?></div>
        </div>
    <?php endforeach; ?>
    <?php if ((float)$order['delivery_fee'] > 0): ?>
        <div class="cart-total small"><span>Dostava</span><span><?= e(money($order['delivery_fee'], $cur)) ?></span></div>
    <?php endif; ?>
    <div class="cart-total">
        <span><?= $order['final_total'] !== null ? 'Ukupno' : 'Okvirno ukupno' ?></span>
        <strong><?= e(money($total, $cur)) ?></strong>
    </div>
</div>

<?php if ((float)$order['deposit_amount'] > 0): ?>
    <section class="deposit">
        <h2>Avans: <?= e(money($order['deposit_amount'], $cur)) ?></h2>
        <?php if ((int)$order['deposit_paid'] === 1): ?>
            <p class="ok">✓ Avans je evidentiran. Hvala!</p>
        <?php elseif ($ipsPayload): ?>
            <p>Skenirajte QR kod u aplikaciji vaše banke (opcija „IPS skeniraj") ili uplatite na račun:</p>
            <img class="ips-qr" src="/porudzbina/<?= e($order['code']) ?>/ips.svg" alt="IPS QR kod za uplatu" width="220" height="220">
            <dl class="order-meta">
                <dt>Primalac</dt><dd><?= e($settings['deposit']['recipient']) ?></dd>
                <dt>Račun</dt><dd><?= e($settings['deposit']['account']) ?></dd>
                <dt>Iznos</dt><dd><?= e(money($order['deposit_amount'], $cur)) ?></dd>
                <dt>Svrha</dt><dd>Avans porudžbina <?= (int)$order['number'] ?></dd>
            </dl>
            <?php if ($settings['deposit']['note']): ?><p class="hint"><?= e($settings['deposit']['note']) ?></p><?php endif; ?>
        <?php else: ?>
            <p class="hint">Poslastičar će vam javiti kako da uplatite avans.</p>
        <?php endif; ?>
    </section>
<?php endif; ?>

<p class="center"><a href="/">← Nazad na početnu</a></p>
