<?php
declare(strict_types=1);

namespace App;

use App\Controllers\Admin;
use App\Controllers\DesignController;
use App\Controllers\InstallController;
use App\Controllers\PlatformController;
use App\Controllers\StorefrontController;

final class Kernel
{
    private const STATIC_TYPES = [
        'css' => 'text/css; charset=utf-8',
        'js' => 'application/javascript; charset=utf-8',
        'svg' => 'image/svg+xml',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'webp' => 'image/webp',
        'gif' => 'image/gif',
        'ico' => 'image/x-icon',
        'woff2' => 'font/woff2',
    ];

    public static function handle(): void
    {
        $path = request_path();

        // Na poddomenima Apache ne vidi fajlove glavnog public_html, pa ih šalje PHP.
        if (str_starts_with($path, '/assets/') || str_starts_with($path, '/uploads/')) {
            self::serveStatic($path);
            return;
        }

        self::startSession();

        try {
            if (!Config::exists()) {
                $router = new Router();
                $router->get('/install', [InstallController::class, 'form']);
                $router->post('/install', [InstallController::class, 'install']);
                if ($path !== '/install') {
                    redirect('/install');
                }
                $router->dispatch(request_method(), $path);
                return;
            }

            $host = strtolower($_SERVER['HTTP_HOST'] ?? '');
            $shop = self::resolveShop($host);

            if ($shop === null) {
                header('X-Robots-Tag: noindex, nofollow');
                self::platformRoutes()->dispatch(request_method(), $path);
                return;
            }

            Shop::setCurrent($shop);
            if ($shop['status'] === 'disabled' && !str_starts_with($path, '/admin')) {
                throw new HttpException(404, 'Ova stranica trenutno nije dostupna.');
            }
            if ((int)$shop['noindex'] === 1 || str_starts_with($path, '/admin')) {
                header('X-Robots-Tag: noindex, nofollow');
            }
            self::shopRoutes()->dispatch(request_method(), $path);
        } catch (HttpException $e) {
            self::renderError($e->status, $e->getMessage());
        } catch (\Throwable $e) {
            error_log((string)$e);
            logger('Greška: ' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);
            self::renderError(500, '');
        }
    }

    /** null = glavni domen (dashboard). Nepostojeći poddomen → 404. */
    private static function resolveShop(string $host): ?array
    {
        $main = strtolower((string)Config::get('main_domain'));

        if (Config::get('dev') && !empty($_GET['shop'])) {
            $_SESSION['_dev_shop'] = (string)$_GET['shop'];
        }
        if (Config::get('dev') && !empty($_SESSION['_dev_shop']) && ($host === $main || $host === 'www.' . $main)) {
            if ($_SESSION['_dev_shop'] === '-') {
                return null;
            }
            $shop = Db::one('SELECT * FROM shops WHERE slug = ?', [$_SESSION['_dev_shop']]);
            if ($shop) {
                return $shop;
            }
        }

        if ($host === $main || $host === 'www.' . $main) {
            return null;
        }
        if (str_ends_with($host, '.' . $main)) {
            $slug = substr($host, 0, -strlen('.' . $main));
            $shop = Db::one('SELECT * FROM shops WHERE slug = ?', [$slug]);
        } else {
            $shop = Db::one('SELECT * FROM shops WHERE custom_domain = ?', [preg_replace('/^www\./', '', $host)]);
        }
        if (!$shop) {
            throw new HttpException(404, 'Ova poslastičarnica ne postoji.');
        }
        return $shop;
    }

    private static function platformRoutes(): Router
    {
        $r = new Router();
        $r->get('/', [PlatformController::class, 'dashboard']);
        $r->get('/login', [PlatformController::class, 'loginForm']);
        $r->post('/login', [PlatformController::class, 'login']);
        $r->post('/logout', [PlatformController::class, 'logout']);
        $r->get('/poslasticarnice/nova', [PlatformController::class, 'newShop']);
        $r->post('/poslasticarnice', [PlatformController::class, 'createShop']);
        $r->get('/poslasticarnice/{id}', [PlatformController::class, 'showShop']);
        $r->post('/poslasticarnice/{id}', [PlatformController::class, 'updateShop']);
        $r->post('/poslasticarnice/{id}/lozinka', [PlatformController::class, 'resetPassword']);
        $r->post('/poslasticarnice/{id}/obrisi', [PlatformController::class, 'deleteShop']);
        $r->get('/poslasticarnice/{id}/udji', [PlatformController::class, 'enterShop']);
        $r->post('/baza/azuriraj', [PlatformController::class, 'migrate']);
        $r->post('/test-ai', [PlatformController::class, 'testAi']);
        $r->get('/robots.txt', [PlatformController::class, 'robots']);
        $r->get('/install', [PlatformController::class, 'installDone']);
        return $r;
    }

    private static function shopRoutes(): Router
    {
        $r = new Router();
        $r->get('/', [StorefrontController::class, 'home']);
        $r->get('/robots.txt', [StorefrontController::class, 'robots']);
        $r->get('/torta', [StorefrontController::class, 'cake']);
        $r->get('/kolaci', [StorefrontController::class, 'products']);
        $r->get('/galerija', [StorefrontController::class, 'gallery']);
        $r->get('/korpa', [StorefrontController::class, 'cart']);
        $r->post('/korpa/torta', [StorefrontController::class, 'addCake']);
        $r->post('/korpa/kolaci', [StorefrontController::class, 'addProducts']);
        $r->post('/korpa/ukloni', [StorefrontController::class, 'removeItem']);
        $r->get('/api/dostupnost', [StorefrontController::class, 'availability']);
        $r->post('/porudzbina', [StorefrontController::class, 'placeOrder']);
        $r->get('/porudzbina/{code}', [StorefrontController::class, 'orderStatus']);
        $r->get('/porudzbina/{code}/ips.svg', [StorefrontController::class, 'ipsQr']);

        $r->post('/api/dizajn', [DesignController::class, 'start']);
        $r->post('/api/dizajn/{token}/{idx}', [DesignController::class, 'image']);

        $r->get('/admin/login', [Admin\AuthController::class, 'form']);
        $r->post('/admin/login', [Admin\AuthController::class, 'login']);
        $r->post('/admin/logout', [Admin\AuthController::class, 'logout']);
        $r->get('/admin/sso', [Admin\AuthController::class, 'sso']);
        $r->get('/admin/nalog', [Admin\AuthController::class, 'account']);
        $r->post('/admin/nalog', [Admin\AuthController::class, 'updateAccount']);

        $r->get('/admin', [Admin\OrdersController::class, 'index']);
        $r->get('/admin/porudzbine/{id}', [Admin\OrdersController::class, 'show']);
        $r->post('/admin/porudzbine/{id}', [Admin\OrdersController::class, 'update']);
        $r->get('/admin/proizvodnja', [Admin\OrdersController::class, 'production']);

        $r->get('/admin/katalog', [Admin\CatalogController::class, 'index']);
        $r->post('/admin/katalog/{type}', [Admin\CatalogController::class, 'save']);
        $r->post('/admin/katalog/{type}/{id}/obrisi', [Admin\CatalogController::class, 'delete']);

        $r->get('/admin/galerija', [Admin\GalleryController::class, 'index']);
        $r->post('/admin/galerija', [Admin\GalleryController::class, 'upload']);
        $r->post('/admin/galerija/{id}', [Admin\GalleryController::class, 'update']);
        $r->post('/admin/galerija/{id}/obrisi', [Admin\GalleryController::class, 'delete']);

        $r->get('/admin/kalendar', [Admin\SettingsController::class, 'calendar']);
        $r->post('/admin/kalendar', [Admin\SettingsController::class, 'saveCalendar']);
        $r->get('/admin/podesavanja', [Admin\SettingsController::class, 'general']);
        $r->post('/admin/podesavanja', [Admin\SettingsController::class, 'saveGeneral']);
        $r->get('/admin/ai', [Admin\SettingsController::class, 'ai']);
        $r->post('/admin/ai', [Admin\SettingsController::class, 'saveAi']);
        return $r;
    }

    private static function startSession(): void
    {
        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
        session_name('torte_sid');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
        if (empty($_SESSION['client_key'])) {
            $_SESSION['client_key'] = random_token();
        }
    }

    private static function serveStatic(string $path): void
    {
        $base = realpath(PUBLIC_DIR);
        $file = realpath(PUBLIC_DIR . $path);
        $ext = strtolower(pathinfo((string)$file, PATHINFO_EXTENSION));
        if ($file === false || $base === false || !str_starts_with($file, $base . DIRECTORY_SEPARATOR)
            || !is_file($file) || !isset(self::STATIC_TYPES[$ext])) {
            http_response_code(404);
            echo 'Not found';
            return;
        }
        header('Content-Type: ' . self::STATIC_TYPES[$ext]);
        header('Content-Length: ' . filesize($file));
        header('Cache-Control: public, max-age=604800');
        header('X-Content-Type-Options: nosniff');
        readfile($file);
    }

    private static function renderError(int $status, string $message): void
    {
        http_response_code($status);
        $wantsJson = str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
            || str_starts_with(request_path(), '/api/');
        if ($wantsJson) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => $message ?: self::defaultMessage($status)], JSON_UNESCAPED_UNICODE);
            return;
        }
        View::render('error', [
            'status' => $status,
            'message' => $message ?: self::defaultMessage($status),
        ]);
    }

    private static function defaultMessage(int $status): string
    {
        return match ($status) {
            404 => 'Stranica nije pronađena.',
            405 => 'Nedozvoljen zahtev.',
            419 => 'Sesija je istekla. Osvežite stranicu.',
            default => 'Došlo je do greške. Pokušajte ponovo za par minuta.',
        };
    }
}
