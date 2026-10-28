<?php
declare(strict_types=1);

namespace App\Services;

use App\Db;
use App\Shop;
use App\View;

final class OrderService
{
    public const STATUSES = [
        'nova' => 'Nova',
        'potvrdjena' => 'Potvrđena',
        'u_izradi' => 'U izradi',
        'gotova' => 'Gotova',
        'preuzeta' => 'Preuzeta',
        'otkazana' => 'Otkazana',
    ];

    /**
     * Pravi porudžbinu iz korpe. Pretpostavlja da su podaci već provereni.
     * @return array porudžbina
     */
    public static function create(array $shop, array $cart, array $customer): array
    {
        $settings = Shop::settings($shop);
        $deliveryFee = $customer['fulfillment'] === 'dostava' ? (float)$settings['delivery']['fee'] : 0.0;
        $total = $cart['total'] + $deliveryFee;
        $percent = (float)$settings['deposit']['percent'];
        $deposit = $percent > 0 ? round($total * $percent / 100 / 10) * 10 : 0.0;

        $orderId = Db::transaction(function () use ($shop, $cart, $customer, $total, $deposit, $deliveryFee) {
            $number = (int)Db::value('SELECT COALESCE(MAX(number), 0) + 1 FROM orders WHERE shop_id = ? FOR UPDATE', [$shop['id']]);
            $id = Db::insert('orders', [
                'shop_id' => $shop['id'],
                'number' => $number,
                'code' => strtoupper(random_token(8)),
                'status' => 'nova',
                'customer_name' => $customer['name'],
                'customer_phone' => $customer['phone'],
                'customer_email' => $customer['email'],
                'customer_note' => $customer['note'],
                'fulfillment' => $customer['fulfillment'],
                'delivery_address' => $customer['address'],
                'due_date' => $customer['date'],
                'due_time' => $customer['time'],
                'estimated_total' => $total,
                'delivery_fee' => $deliveryFee,
                'deposit_amount' => $deposit,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            foreach ($cart['items'] as $item) {
                Db::insert('order_items', [
                    'order_id' => $id,
                    'type' => $item['type'],
                    'name' => $item['name'],
                    'details' => json_encode(self::itemDetails($item), JSON_UNESCAPED_UNICODE),
                    'quantity' => $item['quantity'],
                    'unit' => $item['unit'],
                    'unit_price' => $item['unit_price'],
                    'total' => $item['total'],
                    'image_path' => $item['image_path'],
                ]);
            }
            return $id;
        });

        $order = Db::one('SELECT * FROM orders WHERE id = ?', [$orderId]);
        self::notify($shop, $order);
        return $order;
    }

    /** Šta se čuva uz stavku (da porudžbina ostane ista i ako se katalog promeni). */
    private static function itemDetails(array $item): array
    {
        if ($item['type'] !== 'cake') {
            return ['product_id' => (int)$item['product']['id']];
        }
        $design = $item['design'] ?? [];
        return [
            'size' => $item['size']['name'],
            'weight_kg' => (float)$item['size']['weight_kg'],
            'tiers' => (int)$item['size']['tiers'],
            'flavor' => $item['flavor']['name'],
            'allergens' => $item['flavor']['allergens'],
            'occasion' => Shop::OCCASIONS[$item['occasion']] ?? '',
            'inscription' => $item['inscription'],
            'note' => $item['note'],
            'base_price' => $item['base_price'],
            'surcharge' => $item['surcharge'],
            'design_mode' => $design['mode'] ?? 'opis',
            'design_title' => $design['title'] ?? '',
            'design_description' => $design['description'] ?? '',
            'design_complexity' => $design['complexity'] ?? null,
            'design_techniques' => $design['techniques'] ?? [],
            'design_request' => $design['request']['description'] ?? ($design['text'] ?? ''),
            'design_colors' => $design['request']['colors'] ?? ($design['colors'] ?? []),
            'gallery_code' => $design['gallery_code'] ?? '',
            'design_check' => $design['check'] ?? null,
        ];
    }

    public static function items(int $orderId): array
    {
        $items = Db::all('SELECT * FROM order_items WHERE order_id = ? ORDER BY id', [$orderId]);
        foreach ($items as &$item) {
            $item['details'] = json_decode((string)$item['details'], true) ?: [];
        }
        return $items;
    }

    public static function notify(array $shop, array $order): void
    {
        $settings = Shop::settings($shop);
        $items = self::items((int)$order['id']);
        $orderUrl = Shop::baseUrl($shop) . '/porudzbina/' . $order['code'];
        $vars = ['shop' => $shop, 'settings' => $settings, 'order' => $order, 'items' => $items, 'orderUrl' => $orderUrl];

        $notify = $settings['notify_email'] ?: $settings['contact']['email'];
        if ($notify) {
            Mailer::send(
                $notify,
                sprintf('Nova porudžbina #%d – %s, %s', $order['number'], $order['customer_name'], date_sr($order['due_date'])),
                View::capture('emails/shop_new_order', $vars + ['adminUrl' => Shop::baseUrl($shop) . '/admin/porudzbine/' . $order['id']]),
                $order['customer_email'] ?: null
            );
        }
        if ($order['customer_email']) {
            Mailer::send(
                $order['customer_email'],
                sprintf('Primili smo vašu porudžbinu #%d – %s', $order['number'], $shop['name']),
                View::capture('emails/customer_order', $vars),
                $settings['contact']['email'] ?: null,
                $shop['name']
            );
        }
    }

    /** Email kupcu kad poslastičar promeni status. */
    public static function notifyStatus(array $shop, array $order): void
    {
        if (!$order['customer_email']) {
            return;
        }
        $settings = Shop::settings($shop);
        Mailer::send(
            $order['customer_email'],
            sprintf('Porudžbina #%d: %s', $order['number'], self::STATUSES[$order['status']] ?? $order['status']),
            View::capture('emails/customer_status', [
                'shop' => $shop, 'settings' => $settings, 'order' => $order,
                'orderUrl' => Shop::baseUrl($shop) . '/porudzbina/' . $order['code'],
            ]),
            $settings['contact']['email'] ?: null,
            $shop['name']
        );
    }

    public static function ipsPayload(array $shop, array $order): ?string
    {
        $settings = Shop::settings($shop);
        $d = $settings['deposit'];
        $amount = (float)$order['deposit_amount'];
        if ($amount <= 0 || (int)$order['deposit_paid'] === 1 || $order['status'] === 'otkazana') {
            return null;
        }
        return IpsQr::payload($d['account'], $d['recipient'], $amount, 'Avans porudzbina ' . $order['number'], $d['payment_code']);
    }
}
