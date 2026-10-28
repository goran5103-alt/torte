<?php
declare(strict_types=1);

namespace App\Services\Design;

/**
 * Šabloni kompozicije torte, izvedeni iz prave galerije (docs/DIZAJN-PRAVILA.md).
 * AI bira šablon i popunjava ga bojama, likom, imenom i brojem.
 */
final class DesignTemplates
{
    /**
     * key => [naziv, opis kompozicije za AI (engleski), složenost, potrebne tehnike (sve), potrebne figurice, min spratova, max spratova]
     * Figurice: null = nisu potrebne, 'simple' = bar jednostavne, 'detailed' = detaljne.
     */
    public const ALL = [
        'figurica' => ['Klasik sa figuricom',
            'single fondant-covered tier in the main theme color, a simple repeating pattern on the side (polka dots, stars or stripes), the name in fondant letters on the front, one modeled figurine of the theme or character on top next to a 3D fondant age number, sugar pearl border at the base',
            2, ['fondan'], 'simple', 1, 1],
        'dva_sprata' => ['Dva sprata sa likom',
            'two stacked fondant tiers: bottom tier with a pattern and the name in fondant letters on the front, top tier in a plain matching color, the character figurine and the age number on top, sugar pearl borders at the base of each tier',
            2, ['fondan'], 'simple', 2, 3],
        'jestiva_slika' => ['Jestiva slika',
            'cake with a flat top showing a printed edible image of the theme or character, framed by a piped cream or fondant border, the name written on the top or side, a few small matching decorations',
            1, ['jestiva_slika'], null, 1, 1],
        'scena' => ['Tematska scena',
            'the top of the cake is a small landscape scene (grass, sea, forest or city) with two or three modeled figurines and props such as a little house, trees or clouds, the name on the side',
            3, ['fondan'], 'detailed', 1, 2],
        'zicani_ukrasi' => ['Žičani ukrasi',
            'colorful cake with polka dots, many decorations on thin wires bursting out of the top (fondant balloons, stars, lollipops, hearts in the theme colors), a small topper or figurine in the middle, the name on the front',
            2, [], null, 1, 2],
        'zamak' => ['Zamak',
            'castle cake: stacked tiers with fondant towers topped by cone roofs, little windows and a door, flags, a princess or character figurine at the front, the name on the bottom tier',
            3, ['fondan'], 'simple', 2, 3],
        '3d' => ['3D oblik',
            'the whole cake is sculpted into a 3D object or character (for example a car, a ball, a teddy bear) covered in fondant with painted details, standing on a cake board with the name',
            3, ['3d'], null, 1, 1],
        'broj' => ['Broj ili slovo',
            'cake cut into the shape of the age number (or initial letter), covered in fondant or cream, decorated along its surface with small elements of the theme and a mini figurine',
            2, ['oblik'], null, 1, 1],
        'krem_ruze' => ['Krem ruže',
            'cake fully covered in piped buttercream rosettes in the theme colors, with a printed topper or a fondant bow of the theme on top, the name on a small topper',
            1, ['krem'], null, 1, 2],
        'srce' => ['Srce',
            'heart-shaped cake covered in one color, with a bow or a small figurine on top, small hearts or polka dots on the side and the name in fondant letters',
            1, ['oblik'], null, 1, 1],
    ];

    /**
     * Šabloni koje ova poslastičarnica može da napravi za izabranu veličinu.
     * @return array<string, array>
     */
    public static function allowed(array $capabilities, int $tiers): array
    {
        $techniques = $capabilities['techniques'] ?? [];
        $figurines = $capabilities['figurines'] ?? 'none';
        $rank = ['none' => 0, 'simple' => 1, 'detailed' => 2];
        $out = [];
        foreach (self::ALL as $key => $t) {
            [, , , $needs, $fig, $minTiers, $maxTiers] = $t;
            if (array_diff($needs, $techniques)) {
                continue;
            }
            if ($fig !== null && $rank[$figurines] < $rank[$fig]) {
                continue;
            }
            if ($tiers < $minTiers || $tiers > $maxTiers) {
                continue;
            }
            $out[$key] = $t;
        }
        // Rezerva da AI uvek ima bar jedan šablon.
        return $out ?: ['zicani_ukrasi' => self::ALL['zicani_ukrasi']];
    }
}
