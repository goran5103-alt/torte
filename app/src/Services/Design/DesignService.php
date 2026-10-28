<?php
declare(strict_types=1);

namespace App\Services\Design;

use App\Config;
use App\Db;
use App\Services\Uploads;
use App\Shop;

/** Generisanje 6 AI dizajna: opisi odjednom, slike jedna po jedna (svaka u svom zahtevu). */
final class DesignService
{
    public const COUNT = 6;

    public static function briefGenerator(): BriefGenerator
    {
        $key = (string)Config::get('anthropic.api_key', '');
        if ($key === '') {
            return new DemoBriefGenerator();
        }
        return new ClaudeBriefGenerator(
            $key,
            (string)Config::get('anthropic.model', 'claude-opus-5-5'),
            (string)Config::get('anthropic.effort', 'medium'),
            (float)Config::get('anthropic.input_price_per_mtok', 4.0),
            (float)Config::get('anthropic.output_price_per_mtok', 20.0),
        );
    }

    /** Samo za testove: zamena servisa za slike. */
    public static ?ImageProvider $testProvider = null;

    public static function imageProvider(): ImageProvider
    {
        if (self::$testProvider) {
            return self::$testProvider;
        }
        if (Config::get('images.provider') === 'openai' && Config::get('images.openai_api_key')) {
            return new OpenAiImageProvider(
                (string)Config::get('images.openai_api_key'),
                (string)Config::get('images.openai_model', 'gpt-image-2'),
                (string)Config::get('images.openai_quality', 'medium'),
                (float)Config::get('images.cost_per_image_usd', 0.04),
            );
        }
        if (Config::get('images.provider') === 'gemini' && Config::get('images.gemini_api_key')) {
            return new GeminiImageProvider(
                (string)Config::get('images.gemini_api_key'),
                (string)Config::get('images.gemini_model', 'gemini-2.5-flash-image'),
                (float)Config::get('images.gemini_cost_per_image_usd', 0.039),
            );
        }
        return new DemoImageProvider();
    }

    /** Koliko je kupcu još ostalo generisanja. */
    public static function remaining(int $shopId, string $clientKey): int
    {
        $used = (int)Db::value('SELECT COUNT(*) FROM design_sessions WHERE shop_id = ? AND client_key = ?', [$shopId, $clientKey]);
        return max(0, (int)Config::get('limits.sessions_per_client', 2) - $used);
    }

    /**
     * @throws \DomainException poruka za kupca
     */
    public static function start(array $shop, array $input, string $clientKey, string $ip): array
    {
        $settings = Shop::settings($shop);
        if (empty($settings['ai']['enabled'])) {
            throw new \DomainException('AI dizajn trenutno nije dostupan. Opišite tortu rečima.');
        }

        $occasion = (string)($input['occasion'] ?? 'ostalo');
        $description = trim((string)($input['description'] ?? ''));
        $inscription = trim((string)($input['inscription'] ?? ''));
        $size = Db::one('SELECT * FROM cake_sizes WHERE id = ? AND shop_id = ? AND active = 1', [(int)($input['size_id'] ?? 0), $shop['id']]);

        if (!$size) {
            throw new \DomainException('Prvo izaberite veličinu torte.');
        }
        if (mb_strlen($description) < 3) {
            throw new \DomainException('Opišite motiv torte (npr. „jednorozi, roze i zlatna").');
        }
        if (mb_strlen($description) > 500 || mb_strlen($inscription) > 40) {
            throw new \DomainException('Opis je predugačak.');
        }
        if (!isset(Shop::OCCASIONS[$occasion])) {
            $occasion = 'ostalo';
        }

        if (self::remaining((int)$shop['id'], $clientKey) <= 0) {
            throw new \DomainException('Iskoristili ste sva generisanja dizajna. Izaberite jedan od ponuđenih ili opišite tortu rečima.');
        }
        $ipToday = (int)Db::value('SELECT COUNT(*) FROM design_sessions WHERE ip = ? AND created_at >= ?', [$ip, date('Y-m-d 00:00:00')]);
        if ($ipToday >= (int)Config::get('limits.sessions_per_ip_per_day', 6)) {
            throw new \DomainException('Dostignut je dnevni broj generisanja. Pokušajte sutra ili opišite tortu rečima.');
        }
        $month = (int)Db::value('SELECT COUNT(*) FROM design_sessions WHERE shop_id = ? AND created_at >= ?', [$shop['id'], date('Y-m-01 00:00:00')]);
        if ($month >= (int)$settings['ai']['monthly_limit']) {
            throw new \DomainException('AI dizajn je trenutno nedostupan. Opišite tortu rečima, poslastičar će vam se javiti.');
        }

        $request = [
            'occasion' => $occasion,
            'occasion_label' => Shop::OCCASIONS[$occasion],
            'description' => $description,
            'colors' => Palette::clean((array)($input['colors'] ?? [])),
            'inscription' => $inscription,
            'characters' => !empty($settings['capabilities']['characters']),
            'size' => [
                'id' => (int)$size['id'],
                'name' => $size['name'],
                'weight_kg' => (float)$size['weight_kg'],
                'tiers' => (int)$size['tiers'],
                'shape' => $size['shape'],
            ],
        ];

        $generator = self::briefGenerator();
        try {
            $result = $generator->generate($request, $settings['capabilities']);
        } catch (\Throwable $e) {
            logger('AI opisi nisu uspeli', ['shop' => $shop['id'], 'error' => $e->getMessage()]);
            throw new \DomainException('Pravljenje dizajna trenutno ne radi. Pokušajte ponovo za minut ili opišite tortu rečima.');
        }

        $token = random_token();
        $sessionId = Db::transaction(function () use ($shop, $token, $clientKey, $ip, $request, $result, $generator) {
            $id = Db::insert('design_sessions', [
                'shop_id' => $shop['id'],
                'token' => $token,
                'client_key' => $clientKey,
                'ip' => $ip,
                'input' => json_encode($request, JSON_UNESCAPED_UNICODE),
                'briefs' => json_encode($result['designs'], JSON_UNESCAPED_UNICODE),
                'generator' => $generator->name(),
                'cost_usd' => $result['usage']['cost_usd'],
                'created_at' => now(),
            ]);
            foreach (array_keys($result['designs']) as $idx) {
                Db::insert('design_images', [
                    'session_id' => $id, 'idx' => $idx, 'status' => 'pending',
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            return $id;
        });
        self::logUsage((int)$shop['id'], 'briefs', $generator->name(), $result['usage']);

        return [
            'token' => $token,
            'session_id' => $sessionId,
            'designs' => array_map(fn($d) => self::publicDesign($d, $settings), $result['designs']),
            'remaining' => self::remaining((int)$shop['id'], $clientKey),
        ];
    }

    /** Pravi (ili vraća već napravljenu) sliku broj $idx. */
    public static function image(array $shop, string $token, int $idx, string $clientKey): array
    {
        $session = Db::one('SELECT * FROM design_sessions WHERE token = ? AND shop_id = ? AND client_key = ?', [$token, $shop['id'], $clientKey]);
        if (!$session) {
            throw new \DomainException('Dizajn nije pronađen. Osvežite stranicu.');
        }
        $row = Db::one('SELECT * FROM design_images WHERE session_id = ? AND idx = ?', [$session['id'], $idx]);
        if (!$row) {
            throw new \DomainException('Dizajn nije pronađen.');
        }
        if ($row['status'] === 'done') {
            return ['status' => 'done', 'url' => upload_url($row['image_path'])];
        }

        // Samo jedan zahtev crta sliku; ostali čekaju (npr. dupli klik).
        $claimed = Db::run(
            "UPDATE design_images SET status = 'working', updated_at = ?
             WHERE id = ? AND (status IN ('pending', 'error') OR (status = 'working' AND updated_at < ?))",
            [now(), $row['id'], date('Y-m-d H:i:s', time() - 150)]
        )->rowCount();
        if ($claimed === 0) {
            return ['status' => 'working'];
        }

        $designs = json_decode((string)$session['briefs'], true) ?: [];
        $request = json_decode((string)$session['input'], true) ?: [];
        $design = $designs[$idx] ?? null;
        if (!$design) {
            throw new \DomainException('Dizajn nije pronađen.');
        }

        $provider = self::imageProvider();
        $checker = self::feasibilityChecker();
        $capabilities = Shop::settings($shop)['capabilities'];
        $minScore = (int)Config::get('check.min_score', 7);
        $maxAttempts = 1 + max(0, (int)Config::get('check.retries', 1));
        $best = null;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $image = self::draw($provider, $design, $request, (int)$shop['id']);
            } catch (\Throwable $e) {
                logger('AI slika nije uspela', ['shop' => $shop['id'], 'session' => $session['id'], 'idx' => $idx, 'error' => $e->getMessage()]);
                if ($best !== null) {
                    break;
                }
                Db::update('design_images', ['status' => 'error', 'error' => mb_substr($e->getMessage(), 0, 500), 'updated_at' => now()], ['id' => $row['id']]);
                return ['status' => 'error', 'error' => 'Slika nije uspela. Pokušajte ponovo.'];
            }
            Db::run('UPDATE design_sessions SET cost_usd = cost_usd + ? WHERE id = ?', [$image['cost_usd'], $session['id']]);
            self::logUsage((int)$shop['id'], 'image', $provider->name(), ['model' => $image['model'], 'cost_usd' => $image['cost_usd']]);

            $check = null;
            $mime = ['png' => 'image/png', 'jpg' => 'image/jpeg', 'webp' => 'image/webp'][$image['ext']] ?? null;
            if ($checker && $mime) {
                try {
                    $check = $checker->check($image['bytes'], $mime, $design, $request, $capabilities);
                    Db::run('UPDATE design_sessions SET cost_usd = cost_usd + ? WHERE id = ?', [$check['usage']['cost_usd'], $session['id']]);
                    self::logUsage((int)$shop['id'], 'check', 'claude', $check['usage']);
                    unset($check['usage']);
                } catch (\Throwable $e) {
                    // Provera je dodatak: ako ne uspe, slika ostaje bez ocene.
                    logger('Provera izvodljivosti nije uspela', ['shop' => $shop['id'], 'error' => $e->getMessage()]);
                }
            }

            $score = $check['score'] ?? null;
            if ($best === null || ($score !== null && $score > ($best['check']['score'] ?? 0))) {
                $best = ['image' => $image, 'check' => $check, 'attempt' => $attempt];
            }
            if ($score === null || $score >= $minScore) {
                break;
            }
            // Slika nije dovoljno izvodljiva: crtamo ponovo uz konkretne ispravke.
            $design['fixes'] = $check['fixes'] ?? [];
        }

        $path = Uploads::saveBytes($best['image']['bytes'], (int)$shop['id'], 'designs/' . date('Y-m'), $best['image']['ext']);
        Db::update('design_images', [
            'status' => 'done',
            'image_path' => $path,
            'error' => null,
            'check_score' => $best['check']['score'] ?? null,
            'check_result' => $best['check'] ? json_encode($best['check'], JSON_UNESCAPED_UNICODE) : null,
            'attempts' => $attempt > $maxAttempts ? $maxAttempts : $attempt,
            'updated_at' => now(),
        ], ['id' => $row['id']]);

        return ['status' => 'done', 'url' => upload_url($path)];
    }

    /** Jedno crtanje; ako servis odbije poznat lik, ista torta sa opštom temom. */
    private static function draw(ImageProvider $provider, array $design, array $request, int $shopId): array
    {
        try {
            return $provider->generate($design, $request);
        } catch (\Throwable $first) {
            if (empty($request['characters'])) {
                throw $first;
            }
            logger('Slika sa likom odbijena, pokušavam opštu temu', ['shop' => $shopId, 'error' => $first->getMessage()]);
            return $provider->generate($design, ['generic' => true] + $request);
        }
    }

    public static function feasibilityChecker(): ?FeasibilityChecker
    {
        $key = (string)Config::get('anthropic.api_key', '');
        if ($key === '' || !Config::get('check.enabled', true)) {
            return null;
        }
        return new FeasibilityChecker(
            $key,
            (string)Config::get('check.model', 'claude-haiku-5-5'),
            (float)Config::get('check.input_price_per_mtok', 0.10),
            (float)Config::get('check.output_price_per_mtok', 0.50),
        );
    }

    /** Izabrani dizajn za korpu (provera da pripada kupcu i da je slika gotova). */
    public static function selected(int $shopId, string $token, int $idx, string $clientKey): ?array
    {
        $session = Db::one('SELECT * FROM design_sessions WHERE token = ? AND shop_id = ? AND client_key = ?', [$token, $shopId, $clientKey]);
        if (!$session) {
            return null;
        }
        $row = Db::one("SELECT * FROM design_images WHERE session_id = ? AND idx = ? AND status = 'done'", [$session['id'], $idx]);
        $designs = json_decode((string)$session['briefs'], true) ?: [];
        if (!$row || !isset($designs[$idx])) {
            return null;
        }
        $input = json_decode((string)$session['input'], true) ?: [];
        return [
            'title' => $designs[$idx]['title'],
            'description' => $designs[$idx]['description'],
            'complexity' => (int)$designs[$idx]['complexity'],
            'techniques' => $designs[$idx]['techniques'],
            'image_path' => $row['image_path'],
            'request' => $input,
            'check' => json_decode((string)($row['check_result'] ?? ''), true) ?: null,
        ];
    }

    public static function surcharge(array $settings, int $complexity): float
    {
        return (float)($settings['design']['surcharge_' . max(1, min(3, $complexity))] ?? 0);
    }

    private static function publicDesign(array $d, array $settings): array
    {
        return [
            'title' => $d['title'],
            'description' => $d['description'],
            'complexity' => (int)$d['complexity'],
            'surcharge' => self::surcharge($settings, (int)$d['complexity']),
            'techniques' => array_map(fn($t) => Shop::TECHNIQUES[$t][0] ?? $t, $d['techniques']),
        ];
    }

    private static function logUsage(int $shopId, string $kind, string $provider, array $usage): void
    {
        Db::insert('ai_usage', [
            'shop_id' => $shopId,
            'kind' => $kind,
            'provider' => $provider,
            'model' => (string)($usage['model'] ?? ''),
            'input_tokens' => (int)($usage['input_tokens'] ?? 0),
            'output_tokens' => (int)($usage['output_tokens'] ?? 0),
            'cost_usd' => (float)($usage['cost_usd'] ?? 0),
            'created_at' => now(),
        ]);
    }
}
