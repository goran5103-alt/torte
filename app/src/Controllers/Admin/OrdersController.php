<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Db;
use App\Services\OrderService;
use App\Shop;

final class OrdersController extends BaseController
{
    private const FILTERS = [
        'aktivne' => 'Aktivne',
        'nova' => 'Nove',
        'danas' => 'Danas',
        'sutra' => 'Sutra',
        'sve' => 'Sve',
    ];

    public function index(array $p): void
    {
        $filter = input('f', 'aktivne');
        if (!isset(self::FILTERS[$filter])) {
            $filter = 'aktivne';
        }
        $where = 'o.shop_id = ?';
        $params = [Shop::id()];
        $order = 'o.due_date ASC, o.id ASC';
        switch ($filter) {
            case 'aktivne':
                $where .= " AND o.status NOT IN ('preuzeta', 'otkazana')";
                break;
            case 'nova':
                $where .= " AND o.status = 'nova'";
                $order = 'o.created_at DESC';
                break;
            case 'danas':
                $where .= " AND o.due_date = ? AND o.status <> 'otkazana'";
                $params[] = date('Y-m-d');
                break;
            case 'sutra':
                $where .= " AND o.due_date = ? AND o.status <> 'otkazana'";
                $params[] = date('Y-m-d', strtotime('+1 day'));
                break;
            default:
                $order = 'o.created_at DESC';
        }
        $orders = Db::all(
            "SELECT o.*,
                (SELECT image_path FROM order_items i WHERE i.order_id = o.id AND i.image_path IS NOT NULL ORDER BY i.id LIMIT 1) AS image_path,
                (SELECT GROUP_CONCAT(i.name SEPARATOR ', ') FROM order_items i WHERE i.order_id = o.id) AS item_names
             FROM orders o WHERE $where ORDER BY $order LIMIT 200",
            $params
        );
        $this->render('orders', ['orders' => $orders, 'filter' => $filter, 'filters' => self::FILTERS], 'porudzbine');
    }

    public function show(array $p): void
    {
        $order = $this->find($p['id']);
        $this->render('order', [
            'order' => $order,
            'items' => OrderService::items((int)$order['id']),
            'statuses' => OrderService::STATUSES,
            'orderUrl' => Shop::baseUrl(Shop::current()) . '/porudzbina/' . $order['code'],
        ], 'porudzbine');
    }

    public function update(array $p): void
    {
        $order = $this->find($p['id']);
        $status = input('status');
        if (!isset(OrderService::STATUSES[$status])) {
            $status = $order['status'];
        }
        $final = input('final_total');
        $deposit = input('deposit_amount');
        Db::update('orders', [
            'status' => $status,
            'final_total' => $final === '' ? null : (float)str_replace(',', '.', $final),
            'deposit_amount' => $deposit === '' ? 0 : (float)str_replace(',', '.', $deposit),
            'deposit_paid' => isset($_POST['deposit_paid']) ? 1 : 0,
            'internal_note' => input('internal_note'),
            'updated_at' => now(),
        ], ['id' => $order['id']]);

        if ($status !== $order['status'] && isset($_POST['notify_customer'])) {
            OrderService::notifyStatus(Shop::current(), $this->find($p['id']));
        }
        flash('success', 'Porudžbina je sačuvana.');
        redirect('/admin/porudzbine/' . $order['id']);
    }

    /** Spisak za proizvodnju: šta se pravi narednih dana. */
    public function production(array $p): void
    {
        $from = date('Y-m-d');
        $to = date('Y-m-d', strtotime('+' . max(1, min(14, input_int('dana', 3))) . ' days'));
        $rows = Db::all(
            "SELECT o.id AS order_id, o.number, o.due_date, o.due_time, o.status, o.customer_name, o.fulfillment, i.*
             FROM orders o JOIN order_items i ON i.order_id = o.id
             WHERE o.shop_id = ? AND o.due_date BETWEEN ? AND ? AND o.status NOT IN ('otkazana', 'preuzeta')
             ORDER BY o.due_date, i.type, o.number",
            [Shop::id(), $from, $to]
        );
        $days = [];
        foreach ($rows as $row) {
            $row['details'] = json_decode((string)$row['details'], true) ?: [];
            $days[$row['due_date']][] = $row;
        }
        $this->render('production', ['days' => $days, 'dana' => input_int('dana', 3)], 'proizvodnja');
    }

    private function find(string $id): array
    {
        $order = Db::one('SELECT * FROM orders WHERE id = ? AND shop_id = ?', [(int)$id, Shop::id()]);
        if (!$order) {
            abort(404);
        }
        return $order;
    }
}
