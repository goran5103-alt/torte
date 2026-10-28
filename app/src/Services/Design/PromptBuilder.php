<?php
declare(strict_types=1);

namespace App\Services\Design;

final class PromptBuilder
{
    /** Konačan prompt za sliku: opis dizajna + pravila fotografije. */
    /**
     * @param bool $generic true = rezervna verzija bez likova i brendova (ako servis odbije prvi prompt)
     */
    public static function imagePrompt(array $design, array $request, bool $generic = false): string
    {
        $parts = [
            'Photorealistic product photo of a real handmade celebration cake made by a small local pastry shop.',
            self::structure($request),
            DesignTemplates::composition((string)($design['template'] ?? '')) ? 'Composition: ' . DesignTemplates::composition((string)$design['template']) . '.' : '',
            rtrim(trim((string)($generic ? ($design['image_prompt_generic'] ?? $design['image_prompt']) : $design['image_prompt'])), '.') . '.',
        ];
        $inscription = trim((string)($request['inscription'] ?? ''));
        if ($inscription !== '' && mb_strlen($inscription) <= 24) {
            $parts[] = 'The only text on the cake is "' . $inscription . '", spelled exactly like that.';
        } else {
            $parts[] = 'No text or lettering on the cake.';
        }
        $parts[] = 'The cake stands on a simple white cake stand, plain soft light-grey background, soft natural daylight, three-quarter front view, the whole cake visible.';
        $parts[] = 'Realistic proportions and textures of buttercream, ganache or fondant; decorations look hand-made and achievable. No people.';
        if ($generic || empty($request['characters'])) {
            $parts[] = 'No logos, no brand names, no copyrighted characters.';
        }
        return implode(' ', array_filter($parts));
    }

    /** Broj spratova i oblik iz izabrane veličine, da slika ne obeća veću tortu od poručene. */
    private static function structure(array $request): string
    {
        $size = $request['size'] ?? [];
        $tiers = max(1, (int)($size['tiers'] ?? 1));
        $shape = ($size['shape'] ?? 'okrugla') === 'cetvrtasta' ? 'square' : 'round';
        $kg = (float)($size['weight_kg'] ?? 0);
        return sprintf(
            'The cake is a %s cake with exactly %s%s.',
            $shape,
            $tiers === 1 ? 'one single tier (not stacked)' : "$tiers stacked tiers",
            $kg > 0 ? sprintf(', weighing about %s kg, so keep it modest in height and size', rtrim(rtrim(number_format($kg, 1, '.', ''), '0'), '.')) : ''
        );
    }
}
