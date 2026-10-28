<?php
declare(strict_types=1);

namespace App;

/** Trenutna poslastičarnica (iz poddomena) i njena podešavanja. */
final class Shop
{
    private static ?array $current = null;

    public const STATUSES = [
        'demo' => 'Demo',
        'trial' => 'Probni period',
        'active' => 'Aktivna',
        'disabled' => 'Isključena',
    ];

    /** Tehnike koje poslastičar može da označi. AI predlaže samo označene. */
    public const TECHNIQUES = [
        'krem' => ['Krem ili šlag obloga', 'smooth or textured buttercream / whipped cream coating'],
        'ganas' => ['Ganaš obloga (glatka, oštre ivice)', 'smooth chocolate ganache coating with sharp edges'],
        'fondan' => ['Fondan obloga', 'smooth fondant covering'],
        'naked' => ['Naked / semi-naked', 'naked or semi-naked cake with visible sponge layers'],
        'drip' => ['Drip (preliv koji se sliva)', 'chocolate or colored drip running down the sides'],
        'jestiva_slika' => ['Jestiva slika (štampa)', 'flat printed edible image (wafer or sugar sheet) on top or sides'],
        'topper_papir' => ['Topper od papira ili kartona', 'printed paper/cardstock cake topper on sticks'],
        'topper_akril' => ['Akrilni topper (natpis)', 'acrylic lettering topper'],
        'secerno_cvece' => ['Cveće od šećerne paste', 'sugar paste flowers'],
        'sveze_cvece' => ['Sveže ili suvo cveće', 'fresh or dried flowers'],
        'voce' => ['Sveže voće', 'fresh fruit'],
        'slatkisi' => ['Makaroni, bombone, čokoladice', 'macarons, candies, chocolate bars, sprinkles as decoration'],
        'metalik' => ['Zlatni listići, metalik boja', 'edible gold leaf and metallic paint accents'],
        'kuglice' => ['Čokoladne kuglice i ukrasi', 'chocolate spheres, shards and molded chocolate decorations'],
        'oblik' => ['Oblikovane torte (broj, slovo, srce)', 'cakes cut into simple shapes such as numbers, letters or hearts'],
        '3d' => ['3D oblikovane torte (auto, lopta, meda…)', 'fully sculpted 3D cakes shaped like objects or characters'],
    ];

    public const FIGURINES = [
        'none' => 'Ne radim figurice',
        'simple' => 'Jednostavne figurice (zvezdice, srca, jednostavni oblici)',
        'detailed' => 'Detaljne modelovane figurice (životinje, likovi)',
    ];

    public const OCCASIONS = [
        'decji_rodjendan' => 'Dečiji rođendan',
        'rodjendan' => 'Rođendan (odrasli)',
        'krstenje' => 'Krštenje',
        'vencanje' => 'Venčanje',
        'godisnjica' => 'Godišnjica',
        'slava' => 'Slava',
        'firma' => 'Proslava u firmi',
        'ostalo' => 'Nešto drugo',
    ];

    public const DEFAULTS = [
        'tagline' => '',
        'about' => '',
        'contact' => [
            'phone' => '',
            'email' => '',
            'address' => '',
            'hours' => '',
            'instagram' => '',
            'facebook' => '',
        ],
        'branding' => [
            'color' => '#b23a6f',
            'logo' => null,
        ],
        'notify_email' => '',
        'currency' => 'RSD',
        'ordering' => [
            'lead_days_cake' => 3,
            'lead_days_other' => 1,
            'max_cakes_per_day' => 10,
            'max_days_ahead' => 90,
            'closed_weekdays' => [],
            'pickup_note' => '',
        ],
        'delivery' => [
            'enabled' => false,
            'fee' => 0,
            'note' => '',
        ],
        'deposit' => [
            'percent' => 0,
            'recipient' => '',
            'account' => '',
            'payment_code' => '289',
            'note' => '',
        ],
        'ai' => [
            'enabled' => true,
            'monthly_limit' => 100,
        ],
        'capabilities' => [
            'techniques' => ['krem', 'fondan', 'drip', 'jestiva_slika', 'topper_papir', 'slatkisi'],
            'figurines' => 'simple',
            'max_tiers' => 2,
            // Likovi iz crtaća i filmova (Betmen, Elsa...) na torti, kao jestiva slika, topper ili figurica
            'characters' => true,
            'notes' => '',
        ],
        'design' => [
            'surcharge_1' => 0,
            'surcharge_2' => 1000,
            'surcharge_3' => 2500,
        ],
        'terms' => '',
    ];

    public static function setCurrent(?array $shop): void
    {
        self::$current = $shop;
    }

    public static function current(): ?array
    {
        return self::$current;
    }

    public static function id(): int
    {
        return (int)(self::$current['id'] ?? 0);
    }

    public static function find(int $id): ?array
    {
        return Db::one('SELECT * FROM shops WHERE id = ?', [$id]);
    }

    public static function settings(?array $shop = null): array
    {
        $shop ??= self::$current;
        $stored = json_decode((string)($shop['settings'] ?? ''), true);
        return self::merge(self::DEFAULTS, is_array($stored) ? $stored : []);
    }

    /** Čita podešavanje preko tačke, npr. Shop::setting('deposit.percent'). */
    public static function setting(string $key, mixed $default = null): mixed
    {
        $value = self::settings();
        foreach (explode('.', $key) as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) {
                return $default;
            }
            $value = $value[$part];
        }
        return $value;
    }

    public static function saveSettings(int $shopId, array $settings): void
    {
        Db::update('shops', [
            'settings' => json_encode($settings, JSON_UNESCAPED_UNICODE),
            'updated_at' => now(),
        ], ['id' => $shopId]);
        if (self::id() === $shopId) {
            self::$current = self::find($shopId);
        }
    }

    /** Spaja sačuvana podešavanja preko podrazumevanih; liste se zamenjuju cele. */
    private static function merge(array $defaults, array $stored): array
    {
        foreach ($stored as $key => $value) {
            if (is_array($value) && isset($defaults[$key]) && is_array($defaults[$key])
                && !array_is_list($defaults[$key]) && !array_is_list($value)) {
                $defaults[$key] = self::merge($defaults[$key], $value);
            } else {
                $defaults[$key] = $value;
            }
        }
        return $defaults;
    }

    public static function baseUrl(array $shop): string
    {
        $scheme = Config::get('scheme', 'https');
        if (!empty($shop['custom_domain'])) {
            return $scheme . '://' . $shop['custom_domain'];
        }
        return $scheme . '://' . $shop['slug'] . '.' . Config::get('main_domain');
    }

    public static function platformUrl(): string
    {
        return Config::get('scheme', 'https') . '://' . Config::get('main_domain');
    }

    public static function currency(): string
    {
        return (string)self::setting('currency', 'RSD');
    }
}
