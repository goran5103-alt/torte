<?php $cur = $settings['currency'] ?? 'RSD'; ?>
<table cellpadding="6" cellspacing="0" style="border-collapse:collapse;width:100%;font-size:14px">
<?php foreach ($items as $item): $d = $item['details']; ?>
    <tr style="border-bottom:1px solid #eee">
        <td>
            <strong><?= e($item['name']) ?></strong>
            <?php if ($item['type'] === 'cake'): ?>
                <br><?= e($d['size'] ?? '') ?>
                <br>Izgled: <?= e($d['design_title'] ?? '') ?><?= !empty($d['gallery_code']) ? ' (' . e($d['gallery_code']) . ')' : '' ?>
                <?php if (!empty($d['design_request'])): ?><br>Opis kupca: <?= e($d['design_request']) ?><?php endif; ?>
                <?php if (!empty($d['design_colors'])): ?><br>Boje: <?= e(implode(', ', $d['design_colors'])) ?><?php endif; ?>
                <?php if (!empty($d['inscription'])): ?><br>Natpis: „<?= e($d['inscription']) ?>"<?php endif; ?>
                <?php if (!empty($d['note'])): ?><br>Napomena: <?= e($d['note']) ?><?php endif; ?>
            <?php else: ?>
                <br><?= e(qty($item['quantity'])) ?> <?= e($item['unit']) ?>
            <?php endif; ?>
        </td>
        <td align="right" style="white-space:nowrap"><?= e(money($item['total'], $cur)) ?></td>
    </tr>
<?php endforeach; ?>
<?php if ((float)$order['delivery_fee'] > 0): ?>
    <tr><td>Dostava</td><td align="right"><?= e(money($order['delivery_fee'], $cur)) ?></td></tr>
<?php endif; ?>
    <tr><td><strong>Okvirno ukupno</strong></td><td align="right"><strong><?= e(money($order['estimated_total'], $cur)) ?></strong></td></tr>
</table>
