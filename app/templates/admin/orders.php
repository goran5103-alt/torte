<?php
$title = 'Porudžbine';
$statuses = \App\Services\OrderService::STATUSES;
$cur = \App\Shop::currency();
?>
<div class="page-head">
    <h1>Porudžbine</h1>
</div>
<div class="filters">
    <?php foreach ($filters as $k => $label): ?>
        <a href="/admin?f=<?= e($k) ?>" class="<?= $filter === $k ? 'active' : '' ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
</div>

<?php if (!$orders): ?>
    <div class="card empty">Nema porudžbina za ovaj prikaz.</div>
<?php else: ?>
    <div class="order-cards">
        <?php foreach ($orders as $o): ?>
            <a class="order-card" href="/admin/porudzbine/<?= (int)$o['id'] ?>">
                <?php if ($o['image_path']): ?>
                    <img src="<?= e(upload_url($o['image_path'])) ?>" alt="">
                <?php else: ?>
                    <span class="noimg"></span>
                <?php endif; ?>
                <div>
                    <div class="meta">
                        <strong>#<?= (int)$o['number'] ?></strong>
                        <span class="badge badge-<?= e($o['status']) ?>"><?= e($statuses[$o['status']] ?? $o['status']) ?></span>
                        <span><?= e(day_name_sr($o['due_date'])) ?> <?= e(date_sr($o['due_date'])) ?><?= $o['due_time'] ? ', ' . e($o['due_time']) : '' ?></span>
                        <?php if ($o['fulfillment'] === 'dostava'): ?><span>🚗 dostava</span><?php endif; ?>
                    </div>
                    <div class="title"><?= e($o['customer_name']) ?> · <?= e($o['customer_phone']) ?></div>
                    <div class="muted"><?= e(mb_strimwidth((string)$o['item_names'], 0, 110, '…')) ?></div>
                    <div class="meta">
                        <span><?= e(money($o['final_total'] ?? $o['estimated_total'], $cur)) ?></span>
                        <?php if ((float)$o['deposit_amount'] > 0): ?>
                            <span><?= $o['deposit_paid'] ? '✓ avans plaćen' : 'avans ' . e(money($o['deposit_amount'], $cur)) . ' nije plaćen' ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
