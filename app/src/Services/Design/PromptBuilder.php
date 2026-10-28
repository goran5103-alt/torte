<?php
declare(strict_types=1);

namespace App\Services\Design;

final class PromptBuilder
{
    /** Konačan prompt za sliku: opis dizajna + pravila fotografije. */
    public static function imagePrompt(array $design, array $request): string
    {
        $parts = [
            'Photorealistic product photo of a real handmade celebration cake made by a small local pastry shop.',
            trim((string)$design['image_prompt']),
        ];
        $inscription = trim((string)($request['inscription'] ?? ''));
        if ($inscription !== '' && mb_strlen($inscription) <= 24) {
            $parts[] = 'The only text on the cake is "' . $inscription . '", spelled exactly like that.';
        } else {
            $parts[] = 'No text or lettering on the cake.';
        }
        $parts[] = 'The cake stands on a simple white cake stand, plain soft light-grey background, soft natural daylight, three-quarter front view, the whole cake visible.';
        $parts[] = 'Realistic proportions and textures of buttercream, ganache or fondant; decorations look hand-made and achievable. No logos, no brand names, no copyrighted characters, no people.';
        return implode(' ', $parts);
    }
}
