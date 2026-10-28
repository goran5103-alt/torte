<?php
$title = 'Kalendar';
$o = $settings['ordering'];
$weekdays = [1 => 'ponedeljak', 2 => 'utorak', 3 => 'sreda', 4 => 'četvrtak', 5 => 'petak', 6 => 'subota', 0 => 'nedelja'];
?>
<h1>Kalendar i kapacitet</h1>
<div class="grid-2 align-start">
    <form method="post" action="/admin/kalendar" class="form card">
        <?= csrf_field() ?>
        <h2>Pravila</h2>
        <label>Torta se poručuje najmanje (dana unapred)
            <input type="number" min="0" name="lead_days_cake" value="<?= (int)$o['lead_days_cake'] ?>">
        </label>
        <label>Kolači se poručuju najmanje (dana unapred)
            <input type="number" min="0" name="lead_days_other" value="<?= (int)$o['lead_days_other'] ?>">
        </label>
        <label>Najviše torti po danu (0 = bez ograničenja)
            <input type="number" min="0" name="max_cakes_per_day" value="<?= (int)$o['max_cakes_per_day'] ?>">
        </label>
        <label>Koliko daleko unapred može da se poruči (dana)
            <input type="number" min="7" max="365" name="max_days_ahead" value="<?= (int)$o['max_days_ahead'] ?>">
        </label>
        <div class="label"><strong>Neradni dani u nedelji</strong></div>
        <?php foreach ($weekdays as $n => $name): ?>
            <label class="check"><input type="checkbox" name="closed[<?= $n ?>]" <?= in_array($n, $o['closed_weekdays'], true) ? 'checked' : '' ?>> <?= e($name) ?></label>
        <?php endforeach; ?>
        <label>Napomena uz izbor datuma (opciono)
            <input name="pickup_note" value="<?= e($o['pickup_note']) ?>" placeholder="Preuzimanje od 9 do 20h">
        </label>
        <button class="btn btn-primary">Sačuvaj</button>
    </form>

    <div>
        <form method="post" action="/admin/kalendar" class="form card">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="block">
            <h2>Zatvori dane</h2>
            <p class="muted">Godišnji odmor, praznik ili popunjen kapacitet.</p>
            <div class="grid-2">
                <label>Od <input type="date" name="from" min="<?= date('Y-m-d') ?>" required></label>
                <label>Do (opciono) <input type="date" name="to" min="<?= date('Y-m-d') ?>"></label>
            </div>
            <label>Razlog (kupac ga vidi) <input name="note" placeholder="Godišnji odmor"></label>
            <button class="btn btn-primary">Zatvori</button>
        </form>

        <div class="card">
            <h2>Zatvoreni dani</h2>
            <?php if (!$blocked): ?>
                <p class="muted">Nema.</p>
            <?php else: ?>
                <table class="table compact">
                    <?php foreach ($blocked as $b): ?>
                        <tr>
                            <td><?= e(day_name_sr($b['day'])) ?>, <?= e(date_sr($b['day'])) ?></td>
                            <td><?= e($b['note']) ?></td>
                            <td>
                                <form method="post" action="/admin/kalendar">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="unblock">
                                    <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                                    <button class="btn btn-small">Otvori</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>
