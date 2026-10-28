<?php
declare(strict_types=1);

namespace App\Services;

use App\Db;
use App\Shop;

/** Koji dani su slobodni za preuzimanje (rok poručivanja, kapacitet, neradni dani). */
final class Availability
{
    /**
     * @return array{min: string, max: string, unavailable: array<string, string>}
     *   unavailable: datum => razlog
     */
    public static function calendar(array $shop, bool $hasCake, int $cakesInOrder = 1): array
    {
        $settings = Shop::settings($shop);
        $o = $settings['ordering'];
        $lead = $hasCake ? (int)$o['lead_days_cake'] : (int)$o['lead_days_other'];
        $min = date('Y-m-d', strtotime("+$lead days"));
        $max = date('Y-m-d', strtotime('+' . max($lead + 1, (int)$o['max_days_ahead']) . ' days'));

        $unavailable = [];
        $closed = array_map('intval', (array)$o['closed_weekdays']);
        for ($ts = strtotime($min); $ts <= strtotime($max); $ts += 86400) {
            if (in_array((int)date('w', $ts), $closed, true)) {
                $unavailable[date('Y-m-d', $ts)] = 'Ne radimo';
            }
        }
        foreach (Db::all('SELECT day, note FROM blocked_dates WHERE shop_id = ? AND day BETWEEN ? AND ?', [$shop['id'], $min, $max]) as $b) {
            $unavailable[$b['day']] = $b['note'] !== '' ? $b['note'] : 'Popunjeno';
        }
        if ($hasCake && (int)$o['max_cakes_per_day'] > 0) {
            $rows = Db::all(
                "SELECT o.due_date, COUNT(*) AS cakes FROM orders o
                 JOIN order_items i ON i.order_id = o.id AND i.type = 'cake'
                 WHERE o.shop_id = ? AND o.status <> 'otkazana' AND o.due_date BETWEEN ? AND ?
                 GROUP BY o.due_date",
                [$shop['id'], $min, $max]
            );
            foreach ($rows as $r) {
                if ((int)$r['cakes'] + $cakesInOrder > (int)$o['max_cakes_per_day']) {
                    $unavailable[$r['due_date']] ??= 'Popunjeno';
                }
            }
        }
        ksort($unavailable);
        return ['min' => $min, 'max' => $max, 'unavailable' => $unavailable];
    }

    public static function isAvailable(array $shop, string $date, bool $hasCake, int $cakes): bool
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !strtotime($date)) {
            return false;
        }
        $cal = self::calendar($shop, $hasCake, $cakes);
        return $date >= $cal['min'] && $date <= $cal['max'] && !isset($cal['unavailable'][$date]);
    }
}
