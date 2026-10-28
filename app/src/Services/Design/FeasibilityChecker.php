<?php
declare(strict_types=1);

namespace App\Services\Design;

use Anthropic\Client;
use App\Config;
use App\Shop;

/**
 * Claude pogleda generisanu sliku i proveri da li poslastičarnica može tako da napravi tortu
 * (pravila iz docs/DIZAJN-PRAVILA.md, tehnike poslastičarnice, broj spratova, natpis).
 */
final class FeasibilityChecker
{
    private const SYSTEM = <<<'TXT'
You are a senior pastry chef in a Serbian pastry shop. You review an AI-generated photo of a cake and decide whether the shop can realistically make it by hand so that the customer will not be disappointed.

Check the photo against these rules:
1. The number of tiers must match the ordered size exactly.
2. Figurines are small, about a quarter of the tier height; a huge character means a fully sculpted 3D cake instead.
3. At most 3 figurines; wired decorations are light (balloons, stars, hearts, lollipops); nothing heavy floats without visible support.
4. Fondant has softly rounded edges; impossible glass-like perfection, gravity-defying parts or physically impossible structures are not acceptable.
5. Edible prints are flat (on top or as a band on the side).
6. Every decoration must be made with one of the shop's techniques; anything else is a problem.
7. One main focal point per tier; overall design is not overloaded.
8. If an inscription is requested, check that it is visible and spelled exactly; wrong letters are a problem.

Score 1–10: 9–10 the shop can make it nearly identical; 7–8 makeable with small simplifications; 5–6 needs noticeable changes; 1–4 not realistic for this shop.
Write problems and the note for the pastry chef in Serbian (Latin script), short and concrete. Write fixes in English as short instructions for the image generator.
TXT;

    public function __construct(
        private readonly string $apiKey,
        private readonly string $model,
        private readonly float $inputPricePerMTok = 0.10,
        private readonly float $outputPricePerMTok = 0.50,
    ) {
    }

    /**
     * @return array{score:int, feasible:bool, tiers_seen:int, inscription_ok:?bool, problems:list<string>, fixes:list<string>, note:string, usage:array}
     */
    public function check(string $imageBytes, string $mime, array $design, array $request, array $capabilities): array
    {
        $techniques = array_map(
            fn($k) => Shop::TECHNIQUES[$k][1],
            array_values(array_intersect(array_keys(Shop::TECHNIQUES), $capabilities['techniques'] ?? []))
        );
        $figurines = match ($capabilities['figurines'] ?? 'none') {
            'detailed' => 'detailed modeled figurines',
            'simple' => 'only simple small figurines',
            default => 'no figurines',
        };
        $size = $request['size'] ?? [];
        $inscription = trim((string)($request['inscription'] ?? ''));

        $text = implode("\n", [
            'ORDER',
            sprintf('Size: about %s kg, exactly %d tier(s), %s shape.', $size['weight_kg'] ?? '?', (int)($size['tiers'] ?? 1),
                ($size['shape'] ?? 'okrugla') === 'cetvrtasta' ? 'square' : 'round'),
            'Inscription requested: ' . ($inscription !== '' ? '"' . $inscription . '"' : 'none'),
            'Design that was ordered from the image generator: ' . ($design['image_prompt'] ?? ''),
            '',
            'SHOP CAPABILITIES',
            'Techniques: ' . implode('; ', $techniques),
            'Figurines: ' . $figurines . '. Maximum tiers: ' . (int)($capabilities['max_tiers'] ?? 1) . '.',
            !empty($capabilities['notes']) ? 'Chef notes: ' . $capabilities['notes'] : '',
            '',
            'Review the attached photo.',
        ]);

        $client = new Client(apiKey: $this->apiKey, baseUrl: Config::get('anthropic.base_url') ?: null, requestOptions: ['timeout' => 60.0, 'maxRetries' => 1]);
        $message = $client->messages->create(
            model: $this->model,
            maxTokens: 4000,
            system: self::SYSTEM,
            messages: [[
                'role' => 'user',
                'content' => [
                    ['type' => 'image', 'source' => ['type' => 'base64', 'mediaType' => $mime, 'data' => base64_encode($imageBytes)]],
                    ['type' => 'text', 'text' => $text],
                ],
            ]],
            outputConfig: [
                'effort' => 'low',
                'format' => ['type' => 'json_schema', 'schema' => self::schema()],
            ],
        );

        if ($message->stopReason === 'refusal') {
            throw new \RuntimeException('Provera slike je odbijena.');
        }
        $json = '';
        foreach ($message->content as $block) {
            if ($block->type === 'text') {
                $json .= $block->text;
            }
        }
        $data = json_decode($json, true);
        if (!is_array($data) || !isset($data['score'])) {
            throw new \RuntimeException('Provera slike nije vratila ocenu.');
        }
        $in = $message->usage->inputTokens;
        $out = $message->usage->outputTokens;
        $score = max(1, min(10, (int)$data['score']));
        return [
            'score' => $score,
            'feasible' => (bool)($data['feasible'] ?? $score >= 7),
            'tiers_seen' => (int)($data['tiers_seen'] ?? 0),
            'inscription_ok' => isset($data['inscription_ok']) ? (bool)$data['inscription_ok'] : null,
            'problems' => array_values(array_map('strval', (array)($data['problems'] ?? []))),
            'fixes' => array_values(array_map('strval', (array)($data['fixes'] ?? []))),
            'note' => (string)($data['note_for_chef'] ?? ''),
            'usage' => [
                'input_tokens' => $in,
                'output_tokens' => $out,
                'cost_usd' => ($in * $this->inputPricePerMTok + $out * $this->outputPricePerMTok) / 1_000_000,
                'model' => $this->model,
            ],
        ];
    }

    private static function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'score' => ['type' => 'integer'],
                'feasible' => ['type' => 'boolean'],
                'tiers_seen' => ['type' => 'integer'],
                'inscription_ok' => ['type' => ['boolean', 'null']],
                'problems' => ['type' => 'array', 'items' => ['type' => 'string']],
                'fixes' => ['type' => 'array', 'items' => ['type' => 'string']],
                'note_for_chef' => ['type' => 'string'],
            ],
            'required' => ['score', 'feasible', 'tiers_seen', 'inscription_ok', 'problems', 'fixes', 'note_for_chef'],
            'additionalProperties' => false,
        ];
    }
}
