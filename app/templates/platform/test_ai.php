<?php $title = 'Test AI'; ?>
<h1>Test AI servisa</h1>
<div class="card">
    <table class="table">
        <tr><th>Korak</th><th>Rezultat</th><th>Trajanje</th></tr>
        <?php foreach ($results as [$label, $ok, $msg, $time]): ?>
            <tr>
                <td><strong><?= e($label) ?></strong></td>
                <td><?= $ok === null ? '–' : ($ok ? '<span class="badge badge-active">radi</span>' : '<span class="badge badge-nova">greška</span>') ?><br><?= e($msg) ?></td>
                <td><?= number_format($time, 1) ?> s</td>
            </tr>
        <?php endforeach; ?>
    </table>
    <p class="muted">PHP max_execution_time na serveru: <?= $maxTime ?: 'bez ograničenja' ?><?= $maxTime ? ' s' : '' ?>. Za sliku sa ponovnim crtanjem treba oko 120 s.</p>
</div>
<?php if ($imageData): ?>
    <div class="card"><img src="<?= e($imageData) ?>" alt="" style="max-width:420px;width:100%;border-radius:10px"></div>
<?php endif; ?>
<p><a href="/">← Nazad</a></p>
