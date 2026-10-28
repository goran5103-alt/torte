<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Db;
use App\Services\Uploads;
use App\Shop;

/** Veličine torti, ukusi i kolači. */
final class CatalogController extends BaseController
{
    private const TABLES = [
        'velicine' => 'cake_sizes',
        'ukusi' => 'cake_flavors',
        'kolaci' => 'products',
    ];

    public function index(array $p): void
    {
        $shopId = Shop::id();
        $this->render('catalog', [
            'sizes' => Db::all('SELECT * FROM cake_sizes WHERE shop_id = ? ORDER BY sort, id', [$shopId]),
            'flavors' => Db::all('SELECT * FROM cake_flavors WHERE shop_id = ? ORDER BY sort, id', [$shopId]),
            'products' => Db::all('SELECT * FROM products WHERE shop_id = ? ORDER BY sort, id', [$shopId]),
            'tab' => input('t', 'velicine'),
        ], 'katalog');
    }

    /** Novi ili izmenjen red (id=0 znači novi). */
    public function save(array $p): void
    {
        $type = $p['type'];
        if (!isset(self::TABLES[$type])) {
            abort(404);
        }
        $table = self::TABLES[$type];
        $id = input_int('id');
        $data = match ($type) {
            'velicine' => [
                'name' => input('name'),
                'persons' => max(0, input_int('persons')),
                'weight_kg' => max(0.1, input_float('weight_kg', 1)),
                'tiers' => max(1, min(5, input_int('tiers', 1))),
                'shape' => input('shape') === 'cetvrtasta' ? 'cetvrtasta' : 'okrugla',
            ],
            'ukusi' => [
                'name' => input('name'),
                'description' => input('description'),
                'allergens' => input('allergens'),
                'price_per_kg' => max(0, input_float('price_per_kg')),
            ],
            'kolaci' => [
                'name' => input('name'),
                'description' => input('description'),
                'allergens' => input('allergens'),
                'unit' => input('unit') === 'kom' ? 'kom' : 'kg',
                'price' => max(0, input_float('price')),
                'min_qty' => max(0.1, input_float('min_qty', 1)),
                'step' => max(0.1, input_float('step', 1)),
            ],
        };
        $data['sort'] = input_int('sort');
        $data['active'] = isset($_POST['active']) ? 1 : 0;

        if ($data['name'] === '') {
            flash('error', 'Naziv je obavezan.');
            redirect('/admin/katalog?t=' . $type);
        }

        if ($type === 'kolaci' && !empty($_FILES['image']['name'])) {
            try {
                $data['image_path'] = Uploads::saveImage($_FILES['image'], Shop::id(), 'products', 900);
            } catch (\RuntimeException $e) {
                flash('error', $e->getMessage());
                redirect('/admin/katalog?t=' . $type);
            }
        }

        if ($id > 0) {
            $old = Db::one("SELECT * FROM $table WHERE id = ? AND shop_id = ?", [$id, Shop::id()]);
            if (!$old) {
                abort(404);
            }
            if (isset($data['image_path'])) {
                Uploads::delete($old['image_path'] ?? null);
            }
            Db::update($table, $data, ['id' => $id, 'shop_id' => Shop::id()]);
        } else {
            Db::insert($table, $data + ['shop_id' => Shop::id()]);
        }
        flash('success', 'Sačuvano.');
        redirect('/admin/katalog?t=' . $type);
    }

    public function delete(array $p): void
    {
        $type = $p['type'];
        if (!isset(self::TABLES[$type])) {
            abort(404);
        }
        $table = self::TABLES[$type];
        $row = Db::one("SELECT * FROM $table WHERE id = ? AND shop_id = ?", [(int)$p['id'], Shop::id()]);
        if ($row) {
            Uploads::delete($row['image_path'] ?? null);
            Db::run("DELETE FROM $table WHERE id = ? AND shop_id = ?", [$row['id'], Shop::id()]);
            flash('success', 'Obrisano.');
        }
        redirect('/admin/katalog?t=' . $type);
    }
}
