<?php
declare(strict_types=1);

namespace App\Services;

use App\Db;
use App\Services\Design\DesignService;
use App\Shop;

/** Korpa u sesiji. Cene se uvek ponovo računaju iz baze. */
final class Cart
{
    public static function raw(): array
    {
        return $_SESSION['cart'][Shop::id()] ?? [];
    }

    public static function add(array $item): void
    {
        $item['id'] = random_token(6);
        $_SESSION['cart'][Shop::id()][$item['id']] = $item;
    }

    public static function remove(string $id): void
    {
        unset($_SESSION['cart'][Shop::id()][$id]);
    }

    public static function clear(): void
    {
        unset($_SESSION['cart'][Shop::id()]);
    }

    public static function count(): int
    {
        return count(self::raw());
    }

    /**
     * Stavke sa nazivima i cenama iz baze. Stavke čiji proizvod više ne postoji se izbacuju.
     * @return array{items: list<array>, total: float, has_cake: bool, cake_count: int}
     */
    public static function priced(): array
    {
        $shopId = Shop::id();
        $settings = Shop::settings();
        $items = [];
        $total = 0.0;
        $cakeCount = 0;

        foreach (self::raw() as $id => $raw) {
            if ($raw['type'] === 'cake') {
                $size = Db::one('SELECT * FROM cake_sizes WHERE id = ? AND shop_id = ? AND active = 1', [$raw['size_id'], $shopId]);
                $flavor = Db::one('SELECT * FROM cake_flavors WHERE id = ? AND shop_id = ? AND active = 1', [$raw['flavor_id'], $shopId]);
                if (!$size || !$flavor) {
                    self::remove($id);
                    continue;
                }
                $design = $raw['design'] ?? [];
                $complexity = (int)($design['complexity'] ?? 0);
                $base = round((float)$size['weight_kg'] * (float)$flavor['price_per_kg']);
                $surcharge = $complexity > 0 ? DesignService::surcharge($settings, $complexity) : 0.0;
                $price = $base + $surcharge;
                $items[] = [
                    'id' => $id,
                    'type' => 'cake',
                    'name' => 'Torta: ' . $flavor['name'],
                    'size' => $size,
                    'flavor' => $flavor,
                    'design' => $design,
                    'inscription' => $raw['inscription'] ?? '',
                    'note' => $raw['note'] ?? '',
                    'occasion' => $raw['occasion'] ?? '',
                    'quantity' => 1,
                    'unit' => 'kom',
                    'base_price' => $base,
                    'surcharge' => $surcharge,
                    'unit_price' => $price,
                    'total' => $price,
                    'image_path' => $design['image_path'] ?? null,
                ];
                $total += $price;
                $cakeCount++;
            } else {
                $product = Db::one('SELECT * FROM products WHERE id = ? AND shop_id = ? AND active = 1', [$raw['product_id'], $shopId]);
                if (!$product) {
                    self::remove($id);
                    continue;
                }
                $qty = max((float)$product['min_qty'], (float)$raw['qty']);
                $line = round($qty * (float)$product['price']);
                $items[] = [
                    'id' => $id,
                    'type' => 'product',
                    'name' => $product['name'],
                    'product' => $product,
                    'quantity' => $qty,
                    'unit' => $product['unit'],
                    'unit_price' => (float)$product['price'],
                    'total' => $line,
                    'image_path' => $product['image_path'],
                ];
                $total += $line;
            }
        }
        return ['items' => $items, 'total' => $total, 'has_cake' => $cakeCount > 0, 'cake_count' => $cakeCount];
    }
}
