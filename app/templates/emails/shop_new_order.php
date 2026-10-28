<div style="font-family:Arial,sans-serif;color:#222;max-width:600px">
    <h2 style="margin:0 0 8px">Nova porudžbina #<?= (int)$order['number'] ?></h2>
    <p style="margin:0 0 16px">
        <strong><?= $order['fulfillment'] === 'dostava' ? 'Dostava' : 'Preuzimanje' ?>:</strong>
        <?= e(day_name_sr($order['due_date'])) ?>, <?= e(date_sr($order['due_date'])) ?><?= $order['due_time'] ? ', ' . e($order['due_time']) : '' ?>
        <?php if ($order['fulfillment'] === 'dostava'): ?><br>Adresa: <?= e($order['delivery_address']) ?><?php endif; ?>
    </p>
    <p style="margin:0 0 16px">
        <strong><?= e($order['customer_name']) ?></strong><br>
        Telefon: <?= e($order['customer_phone']) ?><br>
        <?php if ($order['customer_email']): ?>Email: <?= e($order['customer_email']) ?><br><?php endif; ?>
        <?php if ($order['customer_note']): ?>Napomena: <?= e($order['customer_note']) ?><?php endif; ?>
    </p>
    <?php include __DIR__ . '/_items.php'; ?>
    <?php if ((float)$order['deposit_amount'] > 0): ?>
        <p>Avans: <?= e(money($order['deposit_amount'], $settings['currency'])) ?></p>
    <?php endif; ?>
    <p style="margin-top:20px"><a href="<?= e($adminUrl) ?>" style="background:#b23a6f;color:#fff;padding:10px 16px;border-radius:6px;text-decoration:none">Otvori porudžbinu</a></p>
</div>
