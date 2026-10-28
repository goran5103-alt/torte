<?php
$title = 'Podešavanja';
$c = $settings['contact'];
$d = $settings['deposit'];
?>
<h1>Podešavanja</h1>
<form method="post" action="/admin/podesavanja" enctype="multipart/form-data" class="form">
    <?= csrf_field() ?>
    <div class="grid-2 align-start">
        <div class="card">
            <h2>Izgled</h2>
            <label>Kratak opis ispod naziva <input name="tagline" value="<?= e($settings['tagline']) ?>" maxlength="150"></label>
            <label>O nama (početna strana) <textarea name="about" rows="4"><?= e($settings['about']) ?></textarea></label>
            <label>Boja <input type="color" name="color" value="<?= e($settings['branding']['color']) ?>" style="height:44px"></label>
            <label>Logo <input type="file" name="logo" accept="image/jpeg,image/png,image/webp">
                <?php if ($settings['branding']['logo']): ?>
                    <img src="<?= e(upload_url($settings['branding']['logo'])) ?>" alt="" style="max-height:60px;margin-top:.5rem">
                <?php endif; ?>
            </label>
            <?php if ($settings['branding']['logo']): ?>
                <label class="check"><input type="checkbox" name="remove_logo"> Ukloni logo</label>
            <?php endif; ?>

            <h2>Kontakt</h2>
            <label>Email za obaveštenja o porudžbinama <input type="email" name="notify_email" value="<?= e($settings['notify_email']) ?>"></label>
            <label>Telefon <input name="contact_phone" value="<?= e($c['phone']) ?>"></label>
            <label>Email za kupce <input name="contact_email" value="<?= e($c['email']) ?>"></label>
            <label>Adresa <input name="contact_address" value="<?= e($c['address']) ?>"></label>
            <label>Radno vreme <input name="contact_hours" value="<?= e($c['hours']) ?>"></label>
            <label>Instagram <input name="contact_instagram" value="<?= e($c['instagram']) ?>" placeholder="@nalog"></label>
            <label>Facebook (link) <input name="contact_facebook" value="<?= e($c['facebook']) ?>"></label>
        </div>

        <div>
            <div class="card">
                <h2>Avans i uplata (IPS QR)</h2>
                <p class="muted">Kupac posle porudžbine dobija QR kod koji skenira u mobilnom bankarstvu.</p>
                <label>Avans (% od cene, 0 = bez avansa) <input type="number" min="0" max="100" name="deposit_percent" value="<?= e((float)$d['percent']) ?>"></label>
                <label>Primalac (naziv) <input name="deposit_recipient" value="<?= e($d['recipient']) ?>" maxlength="70"></label>
                <label>Broj računa <input name="deposit_account" value="<?= e($d['account']) ?>" placeholder="160-0000000000000-00"></label>
                <label>Šifra plaćanja <input name="deposit_payment_code" value="<?= e($d['payment_code']) ?>" maxlength="3"></label>
                <label>Napomena uz uplatu <input name="deposit_note" value="<?= e($d['note']) ?>" placeholder="Avans se ne vraća u slučaju otkazivanja."></label>
            </div>

            <div class="card">
                <h2>Dostava</h2>
                <label class="check"><input type="checkbox" name="delivery_enabled" <?= $settings['delivery']['enabled'] ? 'checked' : '' ?>> Nudimo dostavu</label>
                <label>Cena dostave (<?= e(\App\Shop::currency()) ?>) <input type="number" min="0" step="10" name="delivery_fee" value="<?= e((float)$settings['delivery']['fee']) ?>"></label>
                <label>Napomena <input name="delivery_note" value="<?= e($settings['delivery']['note']) ?>" placeholder="Dostava samo na teritoriji Beograda"></label>
            </div>

            <div class="card">
                <h2>Uslovi poručivanja</h2>
                <label>Tekst koji kupac vidi pre slanja <textarea name="terms" rows="5"><?= e($settings['terms']) ?></textarea></label>
            </div>
        </div>
    </div>
    <button class="btn btn-primary">Sačuvaj podešavanja</button>
</form>
