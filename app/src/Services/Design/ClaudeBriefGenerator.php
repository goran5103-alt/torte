<?php
declare(strict_types=1);

namespace App\Services\Design;

use Anthropic\Client;
use App\Shop;

/** Opisi dizajna preko Claude API-ja (strukturisan JSON izlaz). */
final class ClaudeBriefGenerator implements BriefGenerator
{
    private const SYSTEM = <<<'TXT'
You are an experienced cake designer working for a small Serbian pastry shop. A customer describes the cake they want; you propose exactly 6 different cake designs that THIS shop can realistically make by hand.

Hard rules:
- Use only the decorating techniques the shop lists as available. Never rely on a technique that is not listed.
- If the shop does not make figurines, use no modeled figures at all. If it makes only simple figurines, keep any figures small and simple (stars, hearts, balloons, simple shapes).
- Never exceed the shop's maximum number of tiers or the tier count of the chosen size.
- Never depict copyrighted or trademarked characters, logos or brands (Disney, Pixar, Marvel, Paw Patrol, football club crests, etc.). If the customer asks for one, translate it into a generic theme with matching colors, shapes and mood (e.g. "Elsa" becomes "an icy winter princess theme in pale blue, white and silver with snowflakes").
- Proportions, colors and decorations must be achievable by one skilled pastry chef in a normal workday. No gravity-defying structures, no floating elements, no perfect airbrushed illustrations unless edible print is available.
- Make the 6 designs clearly different from each other (style, color balance, decoration placement), ranging from simple to more elaborate, while all fitting the customer's wish.
- Complexity: 1 = simple (smooth coating, few decorations), 2 = medium, 3 = elaborate (many hand-made elements). Include at least two designs of complexity 1 or 2.

Field instructions:
- title: short Serbian name for the design (Latin script, max 5 words).
- description: 1–2 sentences in Serbian (Latin script) for the customer, describing what the cake will look like.
- techniques: keys of the shop techniques used.
- image_prompt: detailed English description of the cake only (shape, tiers, coating, colors, decorations and their placement, any inscription), for a photorealistic image generator. Do not describe the background.
TXT;

    public function __construct(
        private readonly string $apiKey,
        private readonly string $model,
        private readonly string $effort = 'medium',
        private readonly float $inputPricePerMTok = 4.0,
        private readonly float $outputPricePerMTok = 20.0,
    ) {
    }

    public function name(): string
    {
        return 'claude';
    }

    public function generate(array $request, array $capabilities): array
    {
        $techniqueKeys = array_values(array_intersect(array_keys(Shop::TECHNIQUES), $capabilities['techniques'] ?? []));
        if (!$techniqueKeys) {
            $techniqueKeys = ['krem'];
        }

        $client = new Client(apiKey: $this->apiKey, requestOptions: ['timeout' => 90.0, 'maxRetries' => 1]);
        $message = $client->beta->messages->create(
            model: $this->model,
            maxTokens: 16000,
            system: self::SYSTEM,
            messages: [['role' => 'user', 'content' => $this->userPrompt($request, $capabilities, $techniqueKeys)]],
            outputConfig: [
                'effort' => $this->effort,
                'format' => ['type' => 'json_schema', 'schema' => $this->schema($techniqueKeys)],
            ],
            // Ako model odbije zahtev, server ga sam ponovi na preporučenom rezervnom modelu.
            fallbacks: 'default',
            betas: ['server-side-fallback-2026-07-01'],
        );

        if ($message->stopReason === 'refusal') {
            throw new \RuntimeException('AI nije mogao da napravi dizajn za ovaj opis. Pokušajte da ga preformulišete.');
        }

        $text = '';
        foreach ($message->content as $block) {
            if ($block->type === 'text') {
                $text .= $block->text;
            }
        }
        $data = json_decode($text, true);
        $designs = is_array($data['designs'] ?? null) ? $data['designs'] : [];
        $designs = array_values(array_filter($designs, fn($d) => is_array($d) && !empty($d['image_prompt'])));
        if (count($designs) < 3) {
            throw new \RuntimeException('AI je vratio nepotpun odgovor. Pokušajte ponovo.');
        }

        $in = $message->usage->inputTokens;
        $out = $message->usage->outputTokens;
        return [
            'designs' => array_map(fn($d) => [
                'title' => (string)($d['title'] ?? ''),
                'description' => (string)($d['description'] ?? ''),
                'techniques' => array_values(array_intersect($techniqueKeys, (array)($d['techniques'] ?? []))),
                'complexity' => max(1, min(3, (int)($d['complexity'] ?? 2))),
                'image_prompt' => (string)$d['image_prompt'],
            ], array_slice($designs, 0, 6)),
            'usage' => [
                'input_tokens' => $in,
                'output_tokens' => $out,
                'cost_usd' => ($in * $this->inputPricePerMTok + $out * $this->outputPricePerMTok) / 1_000_000,
                'model' => $this->model,
            ],
        ];
    }

    private function userPrompt(array $request, array $capabilities, array $techniqueKeys): string
    {
        $techniques = array_map(fn($k) => "- $k: " . Shop::TECHNIQUES[$k][1], $techniqueKeys);
        $figurines = match ($capabilities['figurines'] ?? 'none') {
            'detailed' => 'detailed hand-modeled sugar paste figurines (animals, people)',
            'simple' => 'only simple small figurines (stars, hearts, balloons, simple shapes)',
            default => 'no figurines',
        };
        $size = $request['size'] ?? [];
        $tiers = min((int)($size['tiers'] ?? 1), (int)($capabilities['max_tiers'] ?? 1)) ?: 1;

        $lines = [
            'SHOP CAPABILITIES',
            'Available techniques:',
            ...$techniques,
            'Figurines: ' . $figurines,
            'Maximum tiers: ' . (int)($capabilities['max_tiers'] ?? 1),
        ];
        if (!empty($capabilities['notes'])) {
            $lines[] = 'Notes from the pastry chef: ' . $capabilities['notes'];
        }
        $lines[] = '';
        $lines[] = 'CUSTOMER REQUEST';
        $lines[] = 'Occasion: ' . ($request['occasion_label'] ?? '');
        $lines[] = sprintf('Size: %s, about %s kg, %d tier(s), %s shape', $size['name'] ?? '', $size['weight_kg'] ?? '', $tiers, ($size['shape'] ?? 'okrugla') === 'okrugla' ? 'round' : 'square');
        if (!empty($request['colors'])) {
            $lines[] = 'Preferred colors: ' . implode(', ', Palette::english($request['colors']));
        }
        if (($request['inscription'] ?? '') !== '') {
            $lines[] = 'Inscription on the cake (keep exactly, it is in Serbian): "' . $request['inscription'] . '"';
        }
        $lines[] = 'Customer wish (in Serbian, may mention characters you must translate into a generic theme):';
        $lines[] = '"""' . ($request['description'] ?? '') . '"""';
        return implode("\n", $lines);
    }

    private function schema(array $techniqueKeys): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'designs' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'title' => ['type' => 'string'],
                            'description' => ['type' => 'string'],
                            'techniques' => ['type' => 'array', 'items' => ['type' => 'string', 'enum' => $techniqueKeys]],
                            'complexity' => ['type' => 'integer', 'enum' => [1, 2, 3]],
                            'image_prompt' => ['type' => 'string'],
                        ],
                        'required' => ['title', 'description', 'techniques', 'complexity', 'image_prompt'],
                        'additionalProperties' => false,
                    ],
                ],
            ],
            'required' => ['designs'],
            'additionalProperties' => false,
        ];
    }
}
