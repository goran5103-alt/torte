<?php $statuses = \App\Services\OrderService::STATUSES; ?>
<div style="font-family:Arial,sans-serif;color:#222;max-width:600px">
    <h2 style="margin:0 0 8px">Porudžbina #<?= (int)$order['number'] ?>: <?= e($statuses[$order['status']] ?? $order['status']) ?></h2>
    <?php if ($order['status'] === 'potvrdjena'): ?>
        <p>Vaša porudžbina je potvrđena za <?= e(day_name_sr($order['due_date'])) ?>, <?= e(date_sr($order['due_date'])) ?>.</p>
        <?php if ($order['final_total'] !== null): ?><p>Konačna cena: <strong><?= e(money($order['final_total'], $settings['currency'])) ?></strong></p><?php endif; ?>
    <?php elseif ($order['status'] === 'gotova'): ?>
        <p>Vaša porudžbina je gotova i čeka vas.</p>
    <?php elseif ($order['status'] === 'otkazana'): ?>
        <p>Porudžbina je otkazana. Za pitanja nas pozovite.</p>
    <?php endif; ?>
    <p><a href="<?= e($orderUrl) ?>">Pogledajte porudžbinu</a></p>
    <p style="color:#777;font-size:13px"><?= e($shop['name']) ?><?= $settings['contact']['phone'] ? ' · ' . e($settings['contact']['phone']) : '' ?></p>
</div>
