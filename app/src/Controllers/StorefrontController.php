<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Db;
use App\Services\Availability;
use App\Services\Cart;
use App\Services\Design\DesignService;
use App\Services\Design\Palette;
use App\Services\IpsQr;
use App\Services\OrderService;
use App\Shop;
use App\View;

/** Stranice za kupca na poddomenu poslastičarnice. */
final class StorefrontController
{
    private function render(string $template, array $vars = []): void
    {
        View::render('shop/' . $template, $vars + [
            'shop' => Shop::current(),
            'settings' => Shop::settings(),
            'cartCount' => Cart::count(),
        ], 'shop/layout');
    }

    public function home(array $p): void
    {
        $shopId = Shop::id();
        $this->render('home', [
            'hasCakes' => (bool)Db::value('SELECT COUNT(*) FROM cake_sizes WHERE shop_id = ? AND active = 1', [$shopId]),
            'hasProducts' => (bool)Db::value('SELECT COUNT(*) FROM products WHERE shop_id = ? AND active = 1', [$shopId]),
            'gallery' => Db::all('SELECT * FROM gallery_images WHERE shop_id = ? AND active = 1 ORDER BY sort, id DESC LIMIT 8', [$shopId]),
        ]);
    }

    public function robots(array $p): void
    {
        header('Content-Type: text/plain; charset=utf-8');
        $shop = Shop::current();
        echo "User-agent: *\n" . ((int)$shop['noindex'] === 1 ? "Disallow: /\n" : "Disallow: /admin\nDisallow: /porudzbina\nDisallow: /api\n");
    }

    public function cake(array $p): void
    {
        $shopId = Shop::id();
        $settings = Shop::settings();
        $sizes = Db::all('SELECT * FROM cake_sizes WHERE shop_id = ? AND active = 1 ORDER BY sort, persons', [$shopId]);
        $flavors = Db::all('SELECT * FROM cake_flavors WHERE shop_id = ? AND active = 1 ORDER BY sort, name', [$shopId]);
        $gallery = Db::all('SELECT id, code, title, image_path FROM gallery_images WHERE shop_id = ? AND active = 1 ORDER BY sort, id DESC', [$shopId]);

        $config = [
            'sizes' => array_map(fn($s) => [
                'id' => (int)$s['id'], 'name' => $s['name'], 'persons' => (int)$s['persons'],
                'kg' => (float)$s['weight_kg'], 'tiers' => (int)$s['tiers'], 'shape' => $s['shape'],
            ], $sizes),
            'flavors' => array_map(fn($f) => [
                'id' => (int)$f['id'], 'name' => $f['name'], 'description' => (string)$f['description'],
                'allergens' => $f['allergens'], 'pricePerKg' => (float)$f['price_per_kg'],
            ], $flavors),
            'gallery' => array_map(fn($g) => [
                'id' => (int)$g['id'], 'code' => $g['code'], 'title' => $g['title'], 'url' => upload_url($g['image_path']),
            ], $gallery),
            'colors' => array_map(fn($name, $c) => ['name' => $name, 'hex' => $c[0]], array_keys(Palette::COLORS), Palette::COLORS),
            'occasions' => Shop::OCCASIONS,
            'aiEnabled' => (bool)$settings['ai']['enabled'],
            'aiRemaining' => DesignService::remaining($shopId, (string)$_SESSION['client_key']),
            'currency' => Shop::currency(),
            'preselectGallery' => input_int('galerija') ?: null,
        ];
        $this->render('cake', ['config' => $config, 'empty' => !$sizes || !$flavors]);
    }

    public function products(array $p): void
    {
        $products = Db::all('SELECT * FROM products WHERE shop_id = ? AND active = 1 ORDER BY sort, name', [Shop::id()]);
        $this->render('products', ['products' => $products]);
    }

    public function gallery(array $p): void
    {
        $images = Db::all('SELECT * FROM gallery_images WHERE shop_id = ? AND active = 1 ORDER BY sort, id DESC', [Shop::id()]);
        $this->render('gallery', ['images' => $images]);
    }

    /** Torta iz čarobnjaka (JSON). */
    public function addCake(array $p): void
    {
        $in = json_input();
        $shopId = Shop::id();
        $clientKey = (string)$_SESSION['client_key'];
        $size = Db::one('SELECT id FROM cake_sizes WHERE id = ? AND shop_id = ? AND active = 1', [(int)($in['size_id'] ?? 0), $shopId]);
        $flavor = Db::one('SELECT id FROM cake_flavors WHERE id = ? AND shop_id = ? AND active = 1', [(int)($in['flavor_id'] ?? 0), $shopId]);
        if (!$size || !$flavor) {
            json_response(['error' => 'Izaberite veličinu i ukus.'], 422);
        }

        $mode = (string)($in['design_mode'] ?? 'opis');
        $design = ['mode' => $mode];
        if ($mode === 'ai') {
            $selected = DesignService::selected($shopId, (string)($in['design_token'] ?? ''), (int)($in['design_idx'] ?? -1), $clientKey);
            if (!$selected) {
                json_response(['error' => 'Izaberite jedan od AI dizajna.'], 422);
            }
            $design += $selected;
        } elseif ($mode === 'galerija') {
            $g = Db::one('SELECT * FROM gallery_images WHERE id = ? AND shop_id = ? AND active = 1', [(int)($in['gallery_id'] ?? 0), $shopId]);
            if (!$g) {
                json_response(['error' => 'Izaberite tortu iz galerije.'], 422);
            }
            $design += [
                'title' => 'Po uzoru na ' . ($g['code'] ?: 'sliku iz galerije'),
                'gallery_code' => $g['code'],
                'image_path' => $g['image_path'],
            ];
        } else {
            $design['mode'] = 'opis';
            $text = trim((string)($in['design_text'] ?? ''));
            if (mb_strlen($text) < 3) {
                json_response(['error' => 'Opišite kako torta treba da izgleda.'], 422);
            }
            $design += [
                'title' => 'Po opisu kupca',
                'text' => mb_substr($text, 0, 1000),
                'colors' => Palette::clean((array)($in['colors'] ?? [])),
            ];
        }

        $occasion = (string)($in['occasion'] ?? '');
        Cart::add([
            'type' => 'cake',
            'size_id' => (int)$size['id'],
            'flavor_id' => (int)$flavor['id'],
            'design' => $design,
            'occasion' => isset(Shop::OCCASIONS[$occasion]) ? $occasion : '',
            'inscription' => mb_substr(trim((string)($in['inscription'] ?? '')), 0, 40),
            'note' => mb_substr(trim((string)($in['note'] ?? '')), 0, 1000),
        ]);
        json_response(['ok' => true, 'redirect' => '/korpa']);
    }

    /** Kolači iz forme sa količinama. */
    public function addProducts(array $p): void
    {
        $added = 0;
        foreach ((array)($_POST['qty'] ?? []) as $productId => $qty) {
            $qty = (float)str_replace(',', '.', (string)$qty);
            if ($qty <= 0) {
                continue;
            }
            $product = Db::one('SELECT * FROM products WHERE id = ? AND shop_id = ? AND active = 1', [(int)$productId, Shop::id()]);
            if (!$product) {
                continue;
            }
            Cart::add(['type' => 'product', 'product_id' => (int)$product['id'], 'qty' => max((float)$product['min_qty'], $qty)]);
            $added++;
        }
        if ($added === 0) {
            flash('error', 'Unesite količinu za bar jedan proizvod.');
            redirect('/kolaci');
        }
        redirect('/korpa');
    }

    public function removeItem(array $p): void
    {
        Cart::remove(input('id'));
        redirect('/korpa');
    }

    public function cart(array $p, array $errors = [], array $old = []): void
    {
        $cart = Cart::priced();
        $settings = Shop::settings();
        $this->render('cart', [
            'cart' => $cart,
            'errors' => $errors,
            'old' => $old + ['name' => '', 'phone' => '', 'email' => '', 'note' => '', 'fulfillment' => 'preuzimanje', 'address' => '', 'date' => '', 'time' => ''],
            'calendar' => $cart['items'] ? Availability::calendar(Shop::current(), $cart['has_cake'], $cart['cake_count']) : null,
            'deliveryFee' => (float)$settings['delivery']['fee'],
        ]);
    }

    public function availability(array $p): void
    {
        $cart = Cart::priced();
        json_response(Availability::calendar(Shop::current(), $cart['has_cake'], max(1, $cart['cake_count'])));
    }

    public function placeOrder(array $p): void
    {
        $shop = Shop::current();
        $settings = Shop::settings();
        $cart = Cart::priced();
        if (!$cart['items']) {
            redirect('/korpa');
        }

        $old = [
            'name' => input('name'),
            'phone' => input('phone'),
            'email' => strtolower(input('email')),
            'note' => mb_substr(input('note'), 0, 2000),
            'fulfillment' => input('fulfillment') === 'dostava' && $settings['delivery']['enabled'] ? 'dostava' : 'preuzimanje',
            'address' => input('address'),
            'date' => input('date'),
            'time' => mb_substr(input('time'), 0, 20),
        ];
        $errors = [];
        if (mb_strlen($old['name']) < 2) {
            $errors[] = 'Unesite ime i prezime.';
        }
        if (!preg_match('/^[0-9 +\/()-]{6,}$/', $old['phone'])) {
            $errors[] = 'Unesite ispravan broj telefona.';
        }
        if ($old['email'] !== '' && !filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email nije ispravan.';
        }
        if ($old['fulfillment'] === 'dostava' && mb_strlen($old['address']) < 5) {
            $errors[] = 'Unesite adresu za dostavu.';
        }
        if (!Availability::isAvailable($shop, $old['date'], $cart['has_cake'], max(1, $cart['cake_count']))) {
            $errors[] = 'Izabrani datum nije slobodan. Izaberite drugi dan.';
        }
        if ($cart['has_cake'] && !isset($_POST['accept_illustration'])) {
            $errors[] = 'Potvrdite da razumete da je slika ilustracija.';
        }
        if ($errors) {
            $this->cart([], $errors, $old);
            return;
        }

        $order = OrderService::create($shop, $cart, $old);
        Cart::clear();
        $_SESSION['my_orders'][] = $order['code'];
        redirect('/porudzbina/' . $order['code']);
    }

    public function orderStatus(array $p): void
    {
        $order = $this->findOrder($p['code']);
        $this->render('order', [
            'order' => $order,
            'items' => OrderService::items((int)$order['id']),
            'ipsPayload' => OrderService::ipsPayload(Shop::current(), $order),
            'justPlaced' => in_array($order['code'], $_SESSION['my_orders'] ?? [], true) && $order['status'] === 'nova',
        ]);
    }

    public function ipsQr(array $p): void
    {
        $order = $this->findOrder($p['code']);
        $payload = OrderService::ipsPayload(Shop::current(), $order);
        if (!$payload) {
            abort(404);
        }
        header('Content-Type: image/svg+xml');
        header('Cache-Control: private, max-age=300');
        echo IpsQr::svg($payload);
    }

    private function findOrder(string $code): array
    {
        if (!preg_match('/^[A-F0-9]{16}$/', $code)) {
            abort(404);
        }
        $order = Db::one('SELECT * FROM orders WHERE code = ? AND shop_id = ?', [$code, Shop::id()]);
        if (!$order) {
            abort(404, 'Porudžbina nije pronađena.');
        }
        return $order;
    }
}
