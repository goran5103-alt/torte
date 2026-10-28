<?php
declare(strict_types=1);

namespace App\Services\Design;

/** Boje koje kupac može da izabere (naziv => [hex, engleski naziv za prompt]). */
final class Palette
{
    public const COLORS = [
        'bela' => ['#fbf8f3', 'white'],
        'roze' => ['#f4a6c1', 'pink'],
        'pastel roze' => ['#f9d3e0', 'pastel pink'],
        'crvena' => ['#d6334a', 'red'],
        'ljubičasta' => ['#b18bd6', 'lilac purple'],
        'plava' => ['#5b8fd6', 'blue'],
        'svetloplava' => ['#bfe0f7', 'light blue'],
        'tirkizna' => ['#5cc8c0', 'turquoise'],
        'zelena' => ['#7cc49a', 'green'],
        'žuta' => ['#f7d65c', 'yellow'],
        'narandžasta' => ['#f5a35c', 'orange'],
        'zlatna' => ['#d4af37', 'gold'],
        'srebrna' => ['#bfc3c7', 'silver'],
        'čokoladna' => ['#6b4226', 'chocolate brown'],
        'crna' => ['#2b2b2b', 'black'],
    ];

    /** @return list<string> važeći nazivi iz unosa */
    public static function clean(array $names): array
    {
        return array_values(array_slice(array_unique(array_filter(
            array_map(fn($n) => is_string($n) ? trim($n) : '', $names),
            fn($n) => isset(self::COLORS[$n])
        )), 0, 4));
    }

    public static function english(array $names): array
    {
        return array_map(fn($n) => self::COLORS[$n][1], self::clean($names));
    }

    public static function hex(string $name): string
    {
        return self::COLORS[$name][0] ?? '#f4a6c1';
    }
}
