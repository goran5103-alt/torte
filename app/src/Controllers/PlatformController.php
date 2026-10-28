<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Config;
use App\Db;
use App\Migrator;
use App\Services\CatalogTemplate;
use App\Services\Uploads;
use App\Shop;
use App\View;

/** Dashboard vlasnika platforme na glavnom domenu. */
final class PlatformController
{
    private const RESERVED_SLUGS = ['www', 'mail', 'ftp', 'admin', 'api', 'webmail', 'cpanel', 'smtp', 'pop', 'imap', 'ns1', 'ns2', 'static', 'cdn'];

    private function render(string $template, array $vars = []): void
    {
        View::render('platform/' . $template, $vars + ['user' => Auth::user()], 'platform/layout');
    }

    public function loginForm(array $p): void
    {
        if ((Auth::user()['role'] ?? null) === 'superadmin') {
            redirect('/');
        }
        View::render('platform/login', ['error' => null, 'email' => '']);
    }

    public function login(array $p): void
    {
        $email = strtolower(input('email'));
        if (Auth::tooManyAttempts()) {
            View::render('platform/login', ['error' => 'Previše pokušaja. Sačekajte 15 minuta.', 'email' => $email]);
            return;
        }
        if (!Auth::attempt($email, (string)($_POST['password'] ?? ''), null)) {
            View::render('platform/login', ['error' => 'Pogrešan email ili lozinka.', 'email' => $email]);
            return;
        }
        redirect('/');
    }

    public function logout(array $p): void
    {
        Auth::logout();
        redirect('/login');
    }

    public function dashboard(array $p): void
    {
        Auth::requireSuperadmin();
        $monthStart = date('Y-m-01 00:00:00');
        $shops = Db::all(
            'SELECT s.*,
                (SELECT COUNT(*) FROM orders o WHERE o.shop_id = s.id) AS orders_total,
                (SELECT COUNT(*) FROM orders o WHERE o.shop_id = s.id AND o.created_at >= ?) AS orders_month,
                (SELECT COALESCE(SUM(cost_usd), 0) FROM ai_usage u WHERE u.shop_id = s.id AND u.created_at >= ?) AS ai_cost_month,
                (SELECT COUNT(*) FROM design_sessions d WHERE d.shop_id = s.id AND d.created_at >= ?) AS designs_month
             FROM shops s ORDER BY s.created_at DESC',
            [$monthStart, $monthStart, $monthStart]
        );
        $pending = array_keys(Migrator::pending(Db::pdo()));
        $this->render('dashboard', [
            'shops' => $shops,
            'pending' => $pending,
            'aiTotal' => array_sum(array_map(fn($s) => (float)$s['ai_cost_month'], $shops)),
            'aiReady' => (bool)Config::get('anthropic.api_key'),
            'imageProvider' => Config::get('images.provider', 'demo'),
            'checkModel' => Config::get('anthropic.api_key') && Config::get('check.enabled', true) ? Config::get('check.model', 'claude-haiku-5-5') : 'isključena',
        ]);
    }

    public function newShop(array $p, array $errors = [], array $old = []): void
    {
        Auth::requireSuperadmin();
        $this->render('shop_new', [
            'errors' => $errors,
            'old' => $old + ['name' => '', 'slug' => '', 'email' => '', 'phone' => '', 'instagram' => '', 'tagline' => '', 'template' => 'standard', 'status' => 'demo'],
            'mainDomain' => Config::get('main_domain'),
        ]);
    }

    public function createShop(array $p): void
    {
        Auth::requireSuperadmin();
        $old = [
            'name' => input('name'),
            'slug' => strtolower(input('slug')),
            'email' => strtolower(input('email')),
            'phone' => input('phone'),
            'instagram' => input('instagram'),
            'tagline' => input('tagline'),
            'template' => input('template', 'standard'),
            'status' => input('status', 'demo'),
        ];
        $errors = [];
        if ($old['name'] === '') {
            $errors[] = 'Naziv je obavezan.';
        }
        $slugError = $this->validateSlug($old['slug']);
        if ($slugError) {
            $errors[] = $slugError;
        }
        if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Unesite ispravan email poslastičara (na njega stižu porudžbine).';
        }
        if (!isset(CatalogTemplate::TEMPLATES[$old['template']])) {
            $old['template'] = 'standard';
        }
        if (!isset(Shop::STATUSES[$old['status']])) {
            $old['status'] = 'demo';
        }
        if ($errors) {
            $this->newShop([], $errors, $old);
            return;
        }
        $password = self::generatePassword();
        $id = CatalogTemplate::createShop($old + ['password' => $password]);
        $_SESSION['_new_password'] = ['shop' => $id, 'password' => $password];
        flash('success', 'Poslastičarnica je napravljena.');
        redirect('/poslasticarnice/' . $id);
    }

    public function showShop(array $p): void
    {
        Auth::requireSuperadmin();
        $shop = $this->findShop($p['id']);
        $newPassword = null;
        if (($_SESSION['_new_password']['shop'] ?? null) === (int)$shop['id']) {
            $newPassword = $_SESSION['_new_password']['password'];
            unset($_SESSION['_new_password']);
        }
        $owner = Db::one('SELECT * FROM users WHERE shop_id = ? ORDER BY id LIMIT 1', [$shop['id']]);
        $usage = Db::all(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') AS month, kind, COUNT(*) AS cnt, SUM(cost_usd) AS cost
             FROM ai_usage WHERE shop_id = ? GROUP BY month, kind ORDER BY month DESC LIMIT 24",
            [$shop['id']]
        );
        $this->render('shop_show', [
            'shop' => $shop,
            'settings' => Shop::settings($shop),
            'owner' => $owner,
            'newPassword' => $newPassword,
            'usage' => $usage,
            'url' => Shop::baseUrl($shop),
            'mainDomain' => Config::get('main_domain'),
        ]);
    }

    public function updateShop(array $p): void
    {
        Auth::requireSuperadmin();
        $shop = $this->findShop($p['id']);
        $name = input('name');
        $status = input('status');
        $domain = strtolower(input('custom_domain'));
        if ($name === '' || !isset(Shop::STATUSES[$status])) {
            flash('error', 'Proverite naziv i status.');
            redirect('/poslasticarnice/' . $shop['id']);
        }
        if ($domain !== '' && !preg_match('/^[a-z0-9.-]+\.[a-z]{2,}$/', $domain)) {
            flash('error', 'Domen nije ispravan.');
            redirect('/poslasticarnice/' . $shop['id']);
        }
        Db::update('shops', [
            'name' => $name,
            'status' => $status,
            'noindex' => isset($_POST['noindex']) ? 1 : 0,
            'custom_domain' => $domain === '' ? null : $domain,
            'updated_at' => now(),
        ], ['id' => $shop['id']]);

        $settings = Shop::settings($shop);
        $settings['ai']['monthly_limit'] = max(0, input_int('ai_monthly_limit', 100));
        $settings['ai']['enabled'] = isset($_POST['ai_enabled']);
        Shop::saveSettings((int)$shop['id'], $settings);

        flash('success', 'Sačuvano.');
        redirect('/poslasticarnice/' . $shop['id']);
    }

    public function resetPassword(array $p): void
    {
        Auth::requireSuperadmin();
        $shop = $this->findShop($p['id']);
        $owner = Db::one('SELECT * FROM users WHERE shop_id = ? ORDER BY id LIMIT 1', [$shop['id']]);
        if (!$owner) {
            abort(404);
        }
        $password = self::generatePassword();
        Db::update('users', ['password_hash' => Auth::hash($password)], ['id' => $owner['id']]);
        $_SESSION['_new_password'] = ['shop' => (int)$shop['id'], 'password' => $password];
        redirect('/poslasticarnice/' . $shop['id']);
    }

    public function deleteShop(array $p): void
    {
        Auth::requireSuperadmin();
        $shop = $this->findShop($p['id']);
        if (input('confirm') !== $shop['slug']) {
            flash('error', 'Za brisanje upišite tačan naziv poddomena.');
            redirect('/poslasticarnice/' . $shop['id']);
        }
        Db::run('DELETE FROM shops WHERE id = ?', [$shop['id']]);
        Uploads::deleteShopFiles((int)$shop['id']);
        flash('success', 'Poslastičarnica ' . $shop['name'] . ' je obrisana.');
        redirect('/');
    }

    /** Ulazak u admin poslastičarnice bez lozinke (potpisan link, važi 2 minuta). */
    public function enterShop(array $p): void
    {
        $user = Auth::requireSuperadmin();
        $shop = $this->findShop($p['id']);
        $token = Auth::ssoToken((int)$user['id'], (int)$shop['id']);
        redirect(Shop::baseUrl($shop) . '/admin/sso?t=' . urlencode($token));
    }

    public function migrate(array $p): void
    {
        Auth::requireSuperadmin();
        $applied = Migrator::migrate(Db::pdo());
        flash('success', $applied ? 'Baza ažurirana: ' . implode(', ', $applied) : 'Baza je već ažurna.');
        redirect('/');
    }

    public function robots(array $p): void
    {
        header('Content-Type: text/plain; charset=utf-8');
        echo "User-agent: *\nDisallow: /\n";
    }

    public function installDone(array $p): void
    {
        redirect('/login');
    }

    private function findShop(string $id): array
    {
        $shop = Shop::find((int)$id);
        if (!$shop) {
            abort(404);
        }
        return $shop;
    }

    private function validateSlug(string $slug): ?string
    {
        if (!preg_match('/^[a-z0-9](?:[a-z0-9-]{0,38}[a-z0-9])?$/', $slug)) {
            return 'Poddomen sme da sadrži samo mala slova, brojeve i crticu (bez č, ć, š...).';
        }
        if (in_array($slug, self::RESERVED_SLUGS, true)) {
            return 'Ovaj poddomen je rezervisan.';
        }
        if (Db::value('SELECT id FROM shops WHERE slug = ?', [$slug])) {
            return 'Poddomen je već zauzet.';
        }
        return null;
    }

    public static function generatePassword(): string
    {
        $chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789';
        $out = '';
        for ($i = 0; $i < 12; $i++) {
            $out .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $out;
    }
}
