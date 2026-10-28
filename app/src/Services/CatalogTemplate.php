<?php
declare(strict_types=1);

namespace App\Services;

use App\Db;
use App\Shop;

/**
 * Početni katalog za novu poslastičarnicu (tipična srpska poslastičarnica).
 * Poslastičar posle samo menja cene i nazive.
 */
final class CatalogTemplate
{
    public const TEMPLATES = [
        'standard' => 'Tipična poslastičarnica (veličine, ukusi, kolači)',
        'suzana' => 'Test: Poslastičarnica Suzana (kontakt sa pekarasuzana.rs + tipičan katalog)',
        'prazan' => 'Prazan katalog',
    ];

    /** Podešavanja za test poslastičarnicu (javni podaci sa pekarasuzana.rs; cene i ukusi su primer). */
    private const SUZANA_SETTINGS = [
        'tagline' => 'Rođendanske i svadbene torte, torte po vašoj zamisli',
        'about' => "Torte po vašoj želji: rođendanske, svadbene i torte po vašoj zamisli. Pošaljite nam motiv, a mi ćemo ga pretvoriti u tortu.\nRadimo svaki dan od 07 do 24h.",
        'contact' => [
            'phone' => '063 8069 721',
            'address' => 'Kralja Petra Prvog 7b, Kaluđerica',
            'hours' => 'Svaki dan 07–24h',
            'facebook' => 'https://www.facebook.com/Poslasti%C4%8Darnica-Suzana-1442924032647375/',
        ],
        'deposit' => [
            'percent' => 30,
            'recipient' => 'Poslastičarnica Suzana (TEST)',
            'account' => '160-0000000012345-95',
            'payment_code' => '289',
            'note' => 'Ovo je test račun, ne uplaćujte.',
        ],
        'delivery' => ['enabled' => true, 'fee' => 500, 'note' => 'Dostava na teritoriji Beograda.'],
        'capabilities' => [
            'techniques' => ['krem', 'fondan', 'drip', 'jestiva_slika', 'topper_papir', 'secerno_cvece', 'voce', 'slatkisi', 'oblik', '3d'],
            'figurines' => 'detailed',
            'max_tiers' => 3,
            'notes' => 'Radimo i torte sa logom firme, grbom kluba ili jestivom slikom po želji kupca.',
        ],
    ];

    private const SIZES = [
        ['Mala (8–10 osoba)', 10, 1.5, 1],
        ['Srednja (12–15 osoba)', 15, 2.5, 1],
        ['Velika (20–25 osoba)', 25, 3.5, 1],
        ['Dva sprata (30–35 osoba)', 35, 5.0, 2],
        ['Dva sprata, velika (40–50 osoba)', 50, 7.0, 2],
        ['Tri sprata (60–70 osoba)', 70, 10.0, 3],
    ];

    private const FLAVORS = [
        ['Čokoladna', 'Čokoladni biskvit, krem od tamne čokolade.', 'gluten, jaja, mleko', 3200],
        ['Vanila i jagoda', 'Vanila biskvit, krem od vanile i sveže jagode.', 'gluten, jaja, mleko', 3300],
        ['Plazma', 'Krem sa mlevenom plazmom i mlečnom čokoladom.', 'gluten, jaja, mleko', 3200],
        ['Šumsko voće', 'Krem od bele čokolade i šumsko voće.', 'gluten, jaja, mleko', 3500],
        ['Snikers', 'Karamel, kikiriki i mlečna čokolada.', 'gluten, jaja, mleko, kikiriki', 3500],
        ['Ferrero', 'Lešnik i nutela krem, hrskavi sloj.', 'gluten, jaja, mleko, orašasti plodovi', 3600],
        ['Moskva', 'Ananas, višnje i orasi.', 'gluten, jaja, mleko, orašasti plodovi', 3400],
        ['Crveni somot', 'Red velvet biskvit i krem od krem sira.', 'gluten, jaja, mleko', 3600],
        ['Limun', 'Limun krem i bela čokolada.', 'gluten, jaja, mleko', 3400],
        ['Oreo', 'Čokoladni biskvit i krem sa oreo keksom.', 'gluten, jaja, mleko', 3400],
    ];

    private const PRODUCTS = [
        ['Sitni slavski kolači (mešani)', 'Izbor od 10–12 vrsta sitnih kolača.', 'gluten, jaja, mleko, orašasti plodovi', 'kg', 2400, 1, 0.5],
        ['Posni sitni kolači', 'Bez mleka i jaja.', 'gluten, orašasti plodovi', 'kg', 2200, 1, 0.5],
        ['Baklava', 'Sa orasima, tradicionalna.', 'gluten, orašasti plodovi', 'kg', 2600, 0.5, 0.5],
        ['Tulumbe', '', 'gluten, jaja', 'kg', 1600, 0.5, 0.5],
        ['Krempita', 'Parče.', 'gluten, jaja, mleko', 'kom', 250, 1, 1],
        ['Princes krofne', '', 'gluten, jaja, mleko', 'kom', 150, 6, 1],
        ['Čokoladni mafini', '', 'gluten, jaja, mleko', 'kom', 180, 6, 1],
        ['Makaroni', 'Razni ukusi.', 'jaja, mleko, orašasti plodovi', 'kom', 150, 6, 6],
    ];

    public static function apply(int $shopId, string $template): void
    {
        if (!in_array($template, ['standard', 'suzana'], true)) {
            return;
        }
        foreach (self::SIZES as $i => [$name, $persons, $kg, $tiers]) {
            Db::insert('cake_sizes', [
                'shop_id' => $shopId, 'name' => $name, 'persons' => $persons,
                'weight_kg' => $kg, 'tiers' => $tiers, 'shape' => 'okrugla', 'sort' => $i,
            ]);
        }
        foreach (self::FLAVORS as $i => [$name, $desc, $allergens, $price]) {
            Db::insert('cake_flavors', [
                'shop_id' => $shopId, 'name' => $name, 'description' => $desc,
                'allergens' => $allergens, 'price_per_kg' => $price, 'sort' => $i,
            ]);
        }
        foreach (self::PRODUCTS as $i => [$name, $desc, $allergens, $unit, $price, $min, $step]) {
            Db::insert('products', [
                'shop_id' => $shopId, 'name' => $name, 'description' => $desc, 'allergens' => $allergens,
                'unit' => $unit, 'price' => $price, 'min_qty' => $min, 'step' => $step, 'sort' => $i,
            ]);
        }
    }

    /** Pravi poslastičarnicu sa vlasnikom i početnim katalogom. Vraća ID. */
    public static function createShop(array $data): int
    {
        return Db::transaction(function () use ($data) {
            $settings = Shop::DEFAULTS;
            $settings['tagline'] = $data['tagline'] ?? '';
            $settings['notify_email'] = $data['email'];
            $settings['contact']['email'] = $data['email'];
            $settings['contact']['phone'] = $data['phone'] ?? '';
            $settings['contact']['instagram'] = $data['instagram'] ?? '';
            if (($data['template'] ?? '') === 'suzana') {
                $settings = array_replace_recursive($settings, self::SUZANA_SETTINGS);
                $settings['capabilities']['techniques'] = self::SUZANA_SETTINGS['capabilities']['techniques'];
            }

            $shopId = Db::insert('shops', [
                'slug' => $data['slug'],
                'name' => $data['name'],
                'status' => $data['status'] ?? 'demo',
                'noindex' => 1,
                'settings' => json_encode($settings, JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            Db::insert('users', [
                'shop_id' => $shopId,
                'role' => 'owner',
                'name' => $data['owner_name'] ?? '',
                'email' => $data['email'],
                'password_hash' => \App\Auth::hash($data['password']),
                'created_at' => now(),
            ]);
            self::apply($shopId, $data['template'] ?? 'standard');
            return $shopId;
        });
    }
}
