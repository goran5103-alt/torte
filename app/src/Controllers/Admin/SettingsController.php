<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Db;
use App\Services\IpsQr;
use App\Services\Uploads;
use App\Shop;

final class SettingsController extends BaseController
{
    public function calendar(array $p): void
    {
        $blocked = Db::all('SELECT * FROM blocked_dates WHERE shop_id = ? AND day >= ? ORDER BY day', [Shop::id(), date('Y-m-d')]);
        $this->render('calendar', ['blocked' => $blocked], 'kalendar');
    }

    public function saveCalendar(array $p): void
    {
        $action = input('action');
        if ($action === 'block') {
            $from = input('from');
            $to = input('to') ?: $from;
            if (!strtotime($from) || !strtotime($to) || $to < $from) {
                flash('error', 'Izaberite ispravan datum.');
                redirect('/admin/kalendar');
            }
            for ($ts = strtotime($from), $n = 0; $ts <= strtotime($to) && $n < 120; $ts += 86400, $n++) {
                Db::run('INSERT IGNORE INTO blocked_dates (shop_id, day, note) VALUES (?, ?, ?)', [Shop::id(), date('Y-m-d', $ts), mb_substr(input('note'), 0, 190)]);
            }
            flash('success', 'Dani su zatvoreni za porudžbine.');
        } elseif ($action === 'unblock') {
            Db::run('DELETE FROM blocked_dates WHERE id = ? AND shop_id = ?', [input_int('id'), Shop::id()]);
            flash('success', 'Dan je ponovo otvoren.');
        } else {
            $s = Shop::settings();
            $s['ordering']['lead_days_cake'] = max(0, input_int('lead_days_cake', 3));
            $s['ordering']['lead_days_other'] = max(0, input_int('lead_days_other', 1));
            $s['ordering']['max_cakes_per_day'] = max(0, input_int('max_cakes_per_day', 10));
            $s['ordering']['max_days_ahead'] = max(7, min(365, input_int('max_days_ahead', 90)));
            $s['ordering']['closed_weekdays'] = array_values(array_map('intval', array_intersect(array_keys($_POST['closed'] ?? []), ['0', '1', '2', '3', '4', '5', '6'])));
            $s['ordering']['pickup_note'] = input('pickup_note');
            Shop::saveSettings(Shop::id(), $s);
            flash('success', 'Sačuvano.');
        }
        redirect('/admin/kalendar');
    }

    public function general(array $p): void
    {
        $this->render('settings', [], 'podesavanja');
    }

    public function saveGeneral(array $p): void
    {
        $s = Shop::settings();
        $s['tagline'] = input('tagline');
        $s['about'] = input('about');
        $s['notify_email'] = filter_var(input('notify_email'), FILTER_VALIDATE_EMAIL) ? input('notify_email') : '';
        foreach (['phone', 'email', 'address', 'hours', 'instagram', 'facebook'] as $k) {
            $s['contact'][$k] = input('contact_' . $k);
        }
        $color = input('color');
        if (preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
            $s['branding']['color'] = $color;
        }
        if (!empty($_FILES['logo']['name'])) {
            try {
                $old = $s['branding']['logo'];
                $s['branding']['logo'] = Uploads::saveImage($_FILES['logo'], Shop::id(), 'branding', 600);
                Uploads::delete($old);
            } catch (\RuntimeException $e) {
                flash('error', 'Logo: ' . $e->getMessage());
            }
        }
        if (isset($_POST['remove_logo'])) {
            Uploads::delete($s['branding']['logo']);
            $s['branding']['logo'] = null;
        }
        $s['delivery']['enabled'] = isset($_POST['delivery_enabled']);
        $s['delivery']['fee'] = max(0, input_float('delivery_fee'));
        $s['delivery']['note'] = input('delivery_note');

        $s['deposit']['percent'] = max(0, min(100, input_float('deposit_percent')));
        $s['deposit']['recipient'] = input('deposit_recipient');
        $s['deposit']['account'] = input('deposit_account');
        $s['deposit']['payment_code'] = preg_match('/^\d{3}$/', input('deposit_payment_code')) ? input('deposit_payment_code') : '289';
        $s['deposit']['note'] = input('deposit_note');
        $s['terms'] = input('terms');

        if ($s['deposit']['percent'] > 0 && $s['deposit']['account'] !== '') {
            $acc = IpsQr::normalizeAccount($s['deposit']['account']);
            if (!$acc || !IpsQr::isValidAccount($acc)) {
                flash('error', 'Broj računa nije ispravan (format 160-0000000000000-00). QR kod neće raditi dok se ne ispravi.');
            }
        }

        Shop::saveSettings(Shop::id(), $s);
        flash('success', 'Podešavanja su sačuvana.');
        redirect('/admin/podesavanja');
    }

    public function ai(array $p): void
    {
        $this->render('ai', [], 'ai');
    }

    public function saveAi(array $p): void
    {
        $s = Shop::settings();
        $s['capabilities']['techniques'] = array_values(array_intersect(array_keys(Shop::TECHNIQUES), array_keys($_POST['techniques'] ?? [])));
        $fig = input('figurines');
        $s['capabilities']['figurines'] = isset(Shop::FIGURINES[$fig]) ? $fig : 'none';
        $s['capabilities']['max_tiers'] = max(1, min(5, input_int('max_tiers', 2)));
        $s['capabilities']['notes'] = mb_substr(input('notes'), 0, 500);
        foreach ([1, 2, 3] as $level) {
            $s['design']['surcharge_' . $level] = max(0, input_float('surcharge_' . $level));
        }
        Shop::saveSettings(Shop::id(), $s);
        flash('success', 'Sačuvano. Novi AI predlozi koriste ova podešavanja.');
        redirect('/admin/ai');
    }
}
