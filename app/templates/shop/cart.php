<?php
$title = 'Porudžbina';
$scripts = ['checkout.js'];
$cur = \App\Shop::currency();
$depositPercent = (float)$settings['deposit']['percent'];
?>
<h1>Vaša porudžbina</h1>

<?php if (!$cart['items']): ?>
    <p class="muted">Korpa je prazna.</p>
    <div class="choice-row">
        <a class="btn btn-primary" href="/torta">Poruči tortu</a>
        <a class="btn" href="/kolaci">Kolači</a>
    </div>
<?php else: ?>

<div class="cart-items">
    <?php foreach ($cart['items'] as $item): ?>
        <div class="cart-item">
            <?php if ($item['image_path']): ?>
                <img src="<?= e(upload_url($item['image_path'])) ?>" alt="">
            <?php endif; ?>
            <div class="cart-info">
                <strong><?= e($item['name']) ?></strong>
                <?php if ($item['type'] === 'cake'): ?>
                    <span><?= e($item['size']['name']) ?> · oko <?= e(qty($item['size']['weight_kg'])) ?> kg</span>
                    <span><?= e($item['design']['title'] ?? '') ?></span>
                    <?php if ($item['inscription']): ?><span>Natpis: „<?= e($item['inscription']) ?>"</span><?php endif; ?>
                <?php else: ?>
                    <span><?= e(qty($item['quantity'])) ?> <?= e($item['unit']) ?> × <?= e(money($item['unit_price'], $cur)) ?></span>
                <?php endif; ?>
            </div>
            <div class="cart-price">
                <?= e(money($item['total'], $cur)) ?>
                <form method="post" action="/korpa/ukloni">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= e($item['id']) ?>">
                    <button class="link-btn" type="submit">Ukloni</button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
    <div class="cart-total">
        <span>Okvirno ukupno</span>
        <strong data-subtotal="<?= e($cart['total']) ?>"><?= e(money($cart['total'], $cur)) ?></strong>
    </div>
    <div class="choice-row">
        <a class="btn btn-small" href="/torta">+ Još jedna torta</a>
        <a class="btn btn-small" href="/kolaci">+ Kolači</a>
    </div>
</div>

<?php foreach ($errors as $error): ?>
    <div class="notice notice-error"><?= e($error) ?></div>
<?php endforeach; ?>

<form method="post" action="/porudzbina" class="checkout" id="checkout">
    <?= csrf_field() ?>
    <script type="application/json" id="calendar-data"><?= json_encode($calendar, JSON_HEX_TAG) ?></script>

    <h2>Kada vam treba?</h2>
    <input type="hidden" name="date" value="<?= e($old['date']) ?>" required>
    <div class="calendar" data-calendar></div>
    <p class="hint" data-date-label><?= $old['date'] ? '' : 'Izaberite dan na kalendaru.' ?></p>
    <?php if ($settings['ordering']['pickup_note']): ?>
        <p class="hint"><?= e($settings['ordering']['pickup_note']) ?></p>
    <?php endif; ?>

    <label>Okvirno vreme (opciono)
        <input name="time" value="<?= e($old['time']) ?>" placeholder="npr. oko 17h" maxlength="20">
    </label>

    <?php if ($settings['delivery']['enabled']): ?>
        <div class="segmented">
            <label><input type="radio" name="fulfillment" value="preuzimanje" <?= $old['fulfillment'] !== 'dostava' ? 'checked' : '' ?>> Preuzimam lično</label>
            <label><input type="radio" name="fulfillment" value="dostava" <?= $old['fulfillment'] === 'dostava' ? 'checked' : '' ?>> Dostava<?= $deliveryFee > 0 ? ' (+' . e(money($deliveryFee, $cur)) . ')' : '' ?></label>
        </div>
        <label data-address <?= $old['fulfillment'] !== 'dostava' ? 'hidden' : '' ?>>Adresa za dostavu
            <input name="address" value="<?= e($old['address']) ?>" maxlength="255">
            <?php if ($settings['delivery']['note']): ?><small><?= e($settings['delivery']['note']) ?></small><?php endif; ?>
        </label>
    <?php else: ?>
        <p class="hint">Preuzimanje u poslastičarnici<?= $settings['contact']['address'] ? ': ' . e($settings['contact']['address']) : '' ?>.</p>
    <?php endif; ?>

    <h2>Vaši podaci</h2>
    <label>Ime i prezime <input name="name" value="<?= e($old['name']) ?>" required autocomplete="name"></label>
    <label>Telefon <input name="phone" type="tel" value="<?= e($old['phone']) ?>" required autocomplete="tel" inputmode="tel"></label>
    <label>Email (za potvrdu, opciono) <input name="email" type="email" value="<?= e($old['email']) ?>" autocomplete="email"></label>
    <label>Napomena <textarea name="note" rows="2" maxlength="2000"><?= e($old['note']) ?></textarea></label>

    <?php if ($cart['has_cake']): ?>
        <label class="check">
            <input type="checkbox" name="accept_illustration" required>
            <span>Razumem da su slike ilustracija i da će torta biti izrađena ručno po uzoru na izabrani izgled.</span>
        </label>
    <?php endif; ?>

    <?php if ($depositPercent > 0): ?>
        <p class="hint">Avans: <?= (float)$depositPercent ?>% (<?= e(money(round($cart['total'] * $depositPercent / 100 / 10) * 10, $cur)) ?>). Podaci za uplatu i QR kod biće prikazani posle slanja porudžbine.</p>
    <?php endif; ?>
    <?php if ($settings['terms']): ?>
        <details class="terms"><summary>Uslovi poručivanja</summary><?= nl2br(e($settings['terms'])) ?></details>
    <?php endif; ?>

    <button class="btn btn-primary btn-block" type="submit">Pošalji porudžbinu</button>
    <p class="hint center">Poslastičar će vas kontaktirati radi potvrde porudžbine i konačne cene.</p>
</form>
<?php endif; ?>
