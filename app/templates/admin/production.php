<?php $title = 'Proizvodnja'; ?>
<div class="page-head">
    <h1>Spisak za proizvodnju</h1>
    <div class="actions no-print">
        <?php foreach ([1 => 'Danas i sutra', 3 => '3 dana', 7 => 'Nedelja'] as $n => $label): ?>
            <a class="btn btn-small <?= $dana === $n ? 'btn-primary' : '' ?>" href="/admin/proizvodnja?dana=<?= $n ?>"><?= e($label) ?></a>
        <?php endforeach; ?>
        <button class="btn btn-small" onclick="window.print()">Štampaj</button>
    </div>
</div>

<?php if (!$days): ?>
    <div class="card empty">Nema porudžbina u ovom periodu.</div>
<?php endif; ?>

<?php foreach ($days as $day => $rows): ?>
    <section class="production-day card">
        <h2><?= e(ucfirst(day_name_sr($day))) ?>, <?= e(date_sr($day)) ?></h2>
        <table class="table compact">
            <tr><th>#</th><th>Šta</th><th>Detalji</th><th>Kupac</th></tr>
            <?php foreach ($rows as $r): $d = $r['details']; ?>
                <tr data-href="/admin/porudzbine/<?= (int)$r['order_id'] ?>">
                    <td><?= (int)$r['number'] ?></td>
                    <td><strong><?= e($r['name']) ?></strong><?= $r['type'] !== 'cake' ? ' · ' . e(qty($r['quantity'])) . ' ' . e($r['unit']) : '' ?></td>
                    <td>
                        <?php if ($r['type'] === 'cake'): ?>
                            <?= e($d['size'] ?? '') ?> · <?= e($d['design_title'] ?? '') ?>
                            <?php if (!empty($d['inscription'])): ?><br>Natpis: „<?= e($d['inscription']) ?>"<?php endif; ?>
                            <?php if (!empty($d['note'])): ?><br><em><?= e($d['note']) ?></em><?php endif; ?>
                        <?php endif; ?>
                    </td>
                    <td><?= e($r['customer_name']) ?><?= $r['due_time'] ? '<br>' . e($r['due_time']) : '' ?><?= $r['fulfillment'] === 'dostava' ? '<br>dostava' : '' ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </section>
<?php endforeach; ?>
