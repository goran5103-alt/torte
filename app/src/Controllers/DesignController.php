<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\Design\DesignService;
use App\Shop;

/** JSON API za AI dizajn (poziva ga cake.js). */
final class DesignController
{
    public function start(array $p): void
    {
        @set_time_limit(150);
        // Sesija se zatvara da paralelni zahtevi za slike ne bi čekali jedan drugog.
        $clientKey = (string)$_SESSION['client_key'];
        session_write_close();
        try {
            $result = DesignService::start(Shop::current(), json_input(), $clientKey, client_ip());
        } catch (\DomainException $e) {
            json_response(['error' => $e->getMessage()], 422);
        }
        unset($result['session_id']);
        json_response($result);
    }

    public function image(array $p): void
    {
        @set_time_limit(150);
        $clientKey = (string)$_SESSION['client_key'];
        session_write_close();
        $idx = (int)$p['idx'];
        if ($idx < 0 || $idx >= DesignService::COUNT || !preg_match('/^[a-f0-9]{32}$/', $p['token'])) {
            json_response(['error' => 'Neispravan zahtev.'], 400);
        }
        try {
            $result = DesignService::image(Shop::current(), $p['token'], $idx, $clientKey);
        } catch (\DomainException $e) {
            json_response(['error' => $e->getMessage()], 422);
        }
        json_response($result);
    }
}
