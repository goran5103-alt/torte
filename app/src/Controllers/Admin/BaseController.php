<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Auth;
use App\Db;
use App\Shop;
use App\View;

abstract class BaseController
{
    protected array $user;

    public function __construct()
    {
        if (!static::isPublic()) {
            $this->user = Auth::requireShopUser();
        }
    }

    protected static function isPublic(): bool
    {
        return false;
    }

    protected function render(string $template, array $vars = [], string $nav = ''): void
    {
        View::render('admin/' . $template, $vars + [
            'shop' => Shop::current(),
            'settings' => Shop::settings(),
            'nav' => $nav,
            'newOrders' => (int)Db::value("SELECT COUNT(*) FROM orders WHERE shop_id = ? AND status = 'nova'", [Shop::id()]),
        ], 'admin/layout');
    }
}
