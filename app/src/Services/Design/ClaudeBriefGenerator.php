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
- Every design must have EXACTLY the number of tiers given for the chosen size (a small cake of 1.5 kg cannot become a two-tier cake), and the stated shape. State the tier count explicitly at the start of each image_prompt.
- Characters: follow the shop's character rule given in SHOP CAPABILITIES. Never add brand logos or club crests the customer did not ask for.
- Proportions, colors and decorations must be achievable by one skilled pastry chef in a normal workday. No gravity-defying structures, no floating elements, no perfect airbrushed illustrations unless edible print is available.
- Every design must be based on one of the ALLOWED TEMPLATES listed below (the compositions real Serbian pastry shops make). Fill the template with the customer's theme, colors, name and age. Use at least 4 different templates across the 6 designs when possible; include at least one template of complexity 1 and at most two of complexity 3.
- Make the 6 designs clearly different from each other (template, color balance, decoration placement), ranging from simple to more elaborate, while all fitting the customer's wish. If the customer names a character, show it in every design, each time in a different way (figurine, edible print, scene, wired decorations).
- Typical style in Serbia: bright, saturated colors; fondant; name in fondant letters on the front; age number on top; sugar pearl borders.
- Complexity: 1 = simple (smooth coating, few decorations), 2 = medium, 3 = elaborate (many hand-made elements). Include at least two designs of complexity 1 or 2.

Field instructions:
- template: key of the allowed template used.
- title: short Serbian name for the design (Latin script, max 5 words).
- description: 1–2 sentences in Serbian (Latin script) for the customer, describing what the cake will look like.
- techniques: keys of the shop techniques used.
- image_prompt: detailed English description of the cake only (shape, tiers, coating, colors, decorations and their placement, any inscription), for a photorealistic image generator. Do not describe the background.
- image_prompt_generic: the same design, but with any named character, brand or logo replaced by generic theme elements (colors, shapes, symbols). Used if the image generator refuses the first prompt.
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
        $tiers = min((int)($request['size']['tiers'] ?? 1), (int)($capabilities['max_tiers'] ?? 1)) ?: 1;
        $templates = DesignTemplates::allowed($capabilities, $tiers);

        $client = new Client(apiKey: $this->apiKey, requestOptions: ['timeout' => 90.0, 'maxRetries' => 1]);
        $message = $client->beta->messages->create(
            model: $this->model,
            maxTokens: 16000,
            system: self::SYSTEM,
            messages: [['role' => 'user', 'content' => $this->userPrompt($request, $capabilities, $techniqueKeys, $templates)]],
            outputConfig: [
                'effort' => $this->effort,
                'format' => ['type' => 'json_schema', 'schema' => $this->schema($techniqueKeys, array_keys($templates))],
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
                'template' => (string)($d['template'] ?? ''),
                'title' => (string)($d['title'] ?? ''),
                'description' => (string)($d['description'] ?? ''),
                'techniques' => array_values(array_intersect($techniqueKeys, (array)($d['techniques'] ?? []))),
                'complexity' => max(1, min(3, (int)($d['complexity'] ?? 2))),
                'image_prompt' => (string)$d['image_prompt'],
                'image_prompt_generic' => (string)($d['image_prompt_generic'] ?? $d['image_prompt']),
            ], array_slice($designs, 0, 6)),
            'usage' => [
                'input_tokens' => $in,
                'output_tokens' => $out,
                'cost_usd' => ($in * $this->inputPricePerMTok + $out * $this->outputPricePerMTok) / 1_000_000,
                'model' => $this->model,
            ],
        ];
    }

    private function userPrompt(array $request, array $capabilities, array $techniqueKeys, array $templates): string
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
        if (!empty($capabilities['characters'])) {
            $lines[] = 'Character rule: characters ALLOWED. If the customer names a character (cartoon, movie, superhero, game), make it clearly recognizable on the cake'
                . ' using the shop\'s techniques: edible printed image, printed topper, or a modeled figurine if figurines are available. Keep its official colors.';
        } else {
            $lines[] = 'Character rule: characters NOT allowed. Never depict named characters, logos or brands; translate them into a generic theme'
                . ' with matching colors, shapes and mood (e.g. "Elsa" becomes an icy winter princess theme in pale blue, white and silver).';
        }
        if (!empty($capabilities['notes'])) {
            $lines[] = 'Notes from the pastry chef: ' . $capabilities['notes'];
        }
        $lines[] = '';
        $lines[] = 'ALLOWED TEMPLATES (key: composition, complexity)';
        foreach ($templates as $key => $t) {
            $lines[] = "- $key: {$t[1]} (complexity {$t[2]})";
        }
        $lines[] = '';
        $lines[] = 'CUSTOMER REQUEST';
        $lines[] = 'Occasion: ' . ($request['occasion_label'] ?? '');
        $lines[] = sprintf('Size: %s, about %s kg, EXACTLY %d tier(s), %s shape', $size['name'] ?? '', $size['weight_kg'] ?? '', $tiers, ($size['shape'] ?? 'okrugla') === 'okrugla' ? 'round' : 'square');
        if (!empty($request['colors'])) {
            $lines[] = 'Preferred colors: ' . implode(', ', Palette::english($request['colors']));
        }
        if (($request['inscription'] ?? '') !== '') {
            $lines[] = 'Inscription on the cake (keep exactly, it is in Serbian): "' . $request['inscription'] . '"';
        }
        $lines[] = 'Customer wish (in Serbian):';
        $lines[] = '"""' . ($request['description'] ?? '') . '"""';
        return implode("\n", $lines);
    }

    private function schema(array $techniqueKeys, array $templateKeys): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'designs' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'template' => ['type' => 'string', 'enum' => $templateKeys],
                            'title' => ['type' => 'string'],
                            'description' => ['type' => 'string'],
                            'techniques' => ['type' => 'array', 'items' => ['type' => 'string', 'enum' => $techniqueKeys]],
                            'complexity' => ['type' => 'integer', 'enum' => [1, 2, 3]],
                            'image_prompt' => ['type' => 'string'],
                            'image_prompt_generic' => ['type' => 'string'],
                        ],
                        'required' => ['template', 'title', 'description', 'techniques', 'complexity', 'image_prompt', 'image_prompt_generic'],
                        'additionalProperties' => false,
                    ],
                ],
            ],
            'required' => ['designs'],
            'additionalProperties' => false,
        ];
    }
}
