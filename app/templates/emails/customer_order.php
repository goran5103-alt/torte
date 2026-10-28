<div style="font-family:Arial,sans-serif;color:#222;max-width:600px">
    <h2 style="margin:0 0 8px">Hvala na porudžbini!</h2>
    <p>Primili smo vašu porudžbinu <strong>#<?= (int)$order['number'] ?></strong> za
        <?= e(day_name_sr($order['due_date'])) ?>, <?= e(date_sr($order['due_date'])) ?>.
        Uskoro ćemo vas kontaktirati radi potvrde i konačne cene.</p>
    <?php include __DIR__ . '/_items.php'; ?>
    <?php if ((float)$order['deposit_amount'] > 0): ?>
        <p>Avans: <strong><?= e(money($order['deposit_amount'], $settings['currency'])) ?></strong>. QR kod za uplatu je na stranici porudžbine.</p>
    <?php endif; ?>
    <p><a href="<?= e($orderUrl) ?>">Pogledajte porudžbinu i status</a></p>
    <p style="color:#777;font-size:13px"><?= e($shop['name']) ?><?= $settings['contact']['phone'] ? ' · ' . e($settings['contact']['phone']) : '' ?></p>
</div>
