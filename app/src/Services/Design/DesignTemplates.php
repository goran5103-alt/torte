<?php
declare(strict_types=1);

namespace App\Services\Design;

/**
 * Šabloni kompozicije torte i biblioteka ukrasa, izvedeni iz 2.305 pravih torti
 * jedne beogradske poslastičarnice (docs/DIZAJN-PRAVILA.md).
 * AI bira šablon prema povodu i mogućnostima poslastičarnice, pa ga popunjava.
 */
final class DesignTemplates
{
    private const KIDS = ['decji_rodjendan'];
    private const ADULT = ['rodjendan', 'firma', 'ostalo'];
    private const LOVE = ['godisnjica', 'vencanje'];

    /**
     * key => [
     *   'name' => naziv, 'composition' => opis za AI (engleski), 'complexity' => 1–3,
     *   'needs' => potrebne tehnike (sve), 'any' => bar jedna od ovih tehnika,
     *   'figurines' => null|'simple'|'detailed', 'tiers' => [min, max], 'occasions' => povodi
     * ]
     */
    public const ALL = [
        // --- dečiji rođendani ---
        'figurica' => [
            'name' => 'Klasik sa figuricom',
            'composition' => 'single fondant-covered tier in the main theme color, a simple repeating pattern on the side (polka dots, stars or stripes), the name in fondant letters on the front, one modeled figurine of the theme or character on top next to a 3D fondant age number, sugar pearl border at the base',
            'complexity' => 2, 'needs' => ['fondan'], 'figurines' => 'simple', 'tiers' => [1, 1],
            'occasions' => ['decji_rodjendan', 'rodjendan'],
        ],
        'dva_sprata' => [
            'name' => 'Dva sprata sa likom',
            'composition' => 'two stacked fondant tiers: bottom tier with a pattern and the name in fondant letters on the front, top tier in a plain matching color, the character figurine and the age number on top, sugar pearl borders at the base of each tier',
            'complexity' => 2, 'needs' => ['fondan'], 'figurines' => 'simple', 'tiers' => [2, 3],
            'occasions' => ['decji_rodjendan', 'rodjendan'],
        ],
        'jestiva_slika' => [
            'name' => 'Jestiva slika',
            'composition' => 'cake with a flat top showing a printed edible image of the theme or character (or a photo), framed by a piped cream or fondant border, the name written on the top or side, a few small matching decorations',
            'complexity' => 1, 'needs' => ['jestiva_slika'], 'tiers' => [1, 1],
            'occasions' => ['decji_rodjendan', 'rodjendan', 'firma', 'slava', 'krstenje', 'ostalo'],
        ],
        'scena' => [
            'name' => 'Tematska scena',
            'composition' => 'the top of the cake is a small landscape scene (grass, sea, forest, road or city) with two or three modeled figurines and props such as a little house, trees, mushrooms or clouds, the name on the side',
            'complexity' => 3, 'needs' => ['fondan'], 'figurines' => 'detailed', 'tiers' => [1, 2],
            'occasions' => self::KIDS,
        ],
        'zicani_ukrasi' => [
            'name' => 'Žičani ukrasi',
            'composition' => 'colorful cake with polka dots or stripes, many decorations on thin wires bursting out of the top (fondant balloons, stars, lollipops, hearts in the theme colors), a small topper or figurine in the middle, the name on the front',
            'complexity' => 2, 'needs' => [], 'tiers' => [1, 2],
            'occasions' => ['decji_rodjendan', 'rodjendan'],
        ],
        'zamak' => [
            'name' => 'Zamak',
            'composition' => 'castle cake: stacked tiers with fondant towers topped by cone roofs, little windows and a door, flags, a princess or character figurine at the front, the name on the bottom tier',
            'complexity' => 3, 'needs' => ['fondan'], 'figurines' => 'simple', 'tiers' => [2, 3],
            'occasions' => self::KIDS,
        ],
        'lik_3d' => [
            'name' => 'Torta u obliku lika ili predmeta',
            'composition' => 'the whole cake is sculpted into the character or object itself (for example a car, a ball, a teddy bear, an animal, a toy) covered in fondant with painted details, standing on a cake board with the name',
            'complexity' => 3, 'needs' => ['3d'], 'tiers' => [1, 1],
            'occasions' => ['decji_rodjendan', 'rodjendan', 'firma', 'ostalo'],
        ],
        'broj' => [
            'name' => 'Broj ili slovo',
            'composition' => 'cake cut into the shape of the age number (or initial letter), covered in fondant or cream, decorated along its surface with small elements of the theme and a mini figurine',
            'complexity' => 2, 'needs' => ['oblik'], 'tiers' => [1, 1],
            'occasions' => ['decji_rodjendan', 'rodjendan', 'godisnjica'],
        ],
        'poklon' => [
            'name' => 'Poklon kutija',
            'composition' => 'cake shaped as a wrapped gift box (square or round hat box) with polka dots or stripes, a big fondant bow and ribbon on top, a small tag with the name',
            'complexity' => 2, 'needs' => ['fondan'], 'tiers' => [1, 2],
            'occasions' => ['decji_rodjendan', 'rodjendan', 'ostalo'],
        ],
        'slatkisi' => [
            'name' => 'Drip sa slatkišima',
            'composition' => 'tall cake with a chocolate or colored drip running down the sides, the top overloaded with chocolates, candy bars, macarons, lollipops and sprinkles, the name or age on a small topper',
            'complexity' => 2, 'needs' => ['drip'], 'any' => ['slatkisi'], 'tiers' => [1, 2],
            'occasions' => ['decji_rodjendan', 'rodjendan', 'ostalo'],
        ],

        // --- odrasli, 18. rođendan, hobi ---
        'cokolada' => [
            'name' => 'Čokoladna elegancija',
            'composition' => 'cake (round or heart-shaped) covered in glossy dark chocolate glaze, decorated with piped chocolate swirls or lace, chocolate fans, a few fresh strawberries or pralines and gold accents, a short message piped in chocolate on top',
            'complexity' => 1, 'needs' => ['ganas'], 'tiers' => [1, 2],
            'occasions' => ['rodjendan', 'godisnjica', 'slava', 'vencanje', 'ostalo'],
        ],
        'kapitone' => [
            'name' => 'Kapitone (prošiveno)',
            'composition' => 'fondant cake with a quilted diamond pattern and small silver or gold dragées at the crossings, a rhinestone ribbon at the base, a crown, bow or sugar flowers on top, the name on a plaque',
            'complexity' => 2, 'needs' => ['fondan'], 'tiers' => [1, 3],
            'occasions' => ['rodjendan', 'godisnjica', 'vencanje', 'krstenje', 'ostalo'],
        ],
        'predmet_3d' => [
            'name' => 'Predmet iz hobija',
            'composition' => 'cake sculpted as an object related to the person (a handbag, a suitcase, a bottle, a guitar, a camera, a book, a football, a car), realistic fondant details, placed on a board with a short message',
            'complexity' => 3, 'needs' => ['3d'], 'tiers' => [1, 1],
            'occasions' => ['rodjendan', 'firma', 'ostalo'],
        ],
        'grb_dres' => [
            'name' => 'Klub, dres ili zastava',
            'composition' => 'flat rectangular or round cake showing a sports jersey with the name and number, a club crest or a flag (as edible print or fondant pieces), with a ball or small fondant elements in the club colors',
            'complexity' => 1, 'any' => ['jestiva_slika', 'fondan'], 'tiers' => [1, 1],
            'occasions' => ['rodjendan', 'decji_rodjendan', 'firma', 'ostalo'],
        ],
        'poruka' => [
            'name' => 'Torta sa porukom',
            'composition' => 'simple rectangular sheet cake with a smooth top carrying a written message or a logo, small piped border and a few matching decorations in the corners',
            'complexity' => 1, 'needs' => [], 'tiers' => [1, 1],
            'occasions' => ['firma', 'slava', 'rodjendan', 'krstenje', 'ostalo'],
        ],

        // --- ljubav, godišnjice, venčanja ---
        'srce' => [
            'name' => 'Srce',
            'composition' => 'heart-shaped cake covered in one color (red, white or pink fondant), a written love message, small fondant hearts or roses, a bow or a rhinestone ribbon',
            'complexity' => 1, 'needs' => ['oblik'], 'tiers' => [1, 1],
            'occasions' => ['godisnjica', 'vencanje', 'rodjendan', 'decji_rodjendan'],
        ],
        'silueta' => [
            'name' => 'Silueta',
            'composition' => 'white fondant tiers with black silhouettes cut from fondant (a couple, children on a swing, a city skyline, a tree with hearts), a few red accents such as hearts or roses',
            'complexity' => 2, 'needs' => ['fondan'], 'tiers' => [1, 4],
            'occasions' => ['vencanje', 'godisnjica', 'rodjendan'],
        ],
        'svadbena' => [
            'name' => 'Klasična svadbena',
            'composition' => 'tall stacked wedding cake of 3 to 5 white or ivory fondant tiers with lace or pearl patterns, a satin or rhinestone ribbon at the base of each tier, a cascade of sugar roses or orchids down one side, a couple topper or monogram on top',
            'complexity' => 3, 'needs' => ['fondan'], 'any' => ['secerno_cvece', 'sveze_cvece'], 'tiers' => [2, 5],
            'occasions' => ['vencanje'],
        ],
        'drapiranje' => [
            'name' => 'Drapiranje i perle',
            'composition' => 'elegant fondant tiers with draped fabric-like swags, strings of sugar pearls, a big bow or brooch and a few sugar flowers, soft pastel or white tones',
            'complexity' => 2, 'needs' => ['fondan'], 'tiers' => [2, 5],
            'occasions' => ['vencanje', 'krstenje', 'godisnjica'],
        ],

        // --- krštenje, beba, slava ---
        'beba' => [
            'name' => 'Beba i krštenje',
            'composition' => 'pastel blue or pink fondant cake with a sleeping baby figurine in a blanket, booties or a teddy bear, a small cross for a christening, the name and date in fondant letters, small dots or stars on the side',
            'complexity' => 2, 'needs' => ['fondan'], 'figurines' => 'simple', 'tiers' => [1, 2],
            'occasions' => ['krstenje', 'decji_rodjendan'],
        ],
        'knjiga' => [
            'name' => 'Otvorena knjiga',
            'composition' => 'cake shaped as an open book; one page with a printed image (icon, photo or drawing) and the other with a written message, a ribbon bookmark, rosary or cross',
            'complexity' => 2, 'needs' => [], 'any' => ['jestiva_slika', 'fondan'], 'tiers' => [1, 1],
            'occasions' => ['slava', 'krstenje', 'firma', 'ostalo'],
        ],

        // --- univerzalno ---
        'krem_ruze' => [
            'name' => 'Krem ruže',
            'composition' => 'cake fully covered in piped buttercream rosettes or ruffles in the theme colors (can be ombre), with a small topper, crown or bow on top, the name on a small plaque',
            'complexity' => 1, 'needs' => ['krem'], 'tiers' => [1, 3],
            'occasions' => ['decji_rodjendan', 'rodjendan', 'krstenje', 'vencanje', 'godisnjica'],
        ],
        'cvece' => [
            'name' => 'Cvetna',
            'composition' => 'smooth cake with a crescent or cascade of large sugar paste or fresh flowers (roses, peonies, orchids) on one side and top, a ribbon at the base, minimal lettering',
            'complexity' => 2, 'any' => ['secerno_cvece', 'sveze_cvece'], 'tiers' => [1, 4],
            'occasions' => ['rodjendan', 'godisnjica', 'vencanje', 'krstenje', 'slava'],
        ],
    ];

    /**
     * Elementi od kojih AI sklapa dizajn (iz prakse). Šalje se Claude-u kao inspiracija.
     */
    public const ELEMENTS = [
        'side patterns' => 'polka dots, stars, vertical stripes, chevron, zebra or leopard print, checkerboard, harlequin diamonds, quilted (kapitone), ruffles, waves, clouds, grass, brick wall, city skyline, piano keys, music notes, paw prints, hearts, flowers, lace',
        'borders' => 'sugar pearls, fondant rope, satin ribbon, rhinestone ribbon, piped shells, grass, fondant scallops',
        'top accents' => 'modeled figurine(s), 3D age number, crown, big bow, wired balloons/stars/lollipops, sugar flowers, edible print disc, chocolates and macarons, fresh berries, gold leaf, sparkler candles',
        'lettering' => 'name in fondant letters on the front, name on a plaque or banner, message piped in chocolate or icing on top, age number on top or on the side',
        'shapes' => 'round, square, rectangular sheet, heart, number or letter, gift box, open book, sculpted object or character',
        'extras next to the cake' => 'matching cupcakes, cake pops, decorated cookies',
    ];

    /**
     * Šabloni koje ova poslastičarnica može da napravi za izabranu veličinu i povod.
     * Ako za povod ima premalo šablona, dodaju se ostali izvodljivi.
     * @return array<string, array>
     */
    public static function allowed(array $capabilities, int $tiers, string $occasion = ''): array
    {
        $techniques = $capabilities['techniques'] ?? [];
        $figurines = $capabilities['figurines'] ?? 'none';
        $rank = ['none' => 0, 'simple' => 1, 'detailed' => 2];

        $feasible = [];
        foreach (self::ALL as $key => $t) {
            if (array_diff($t['needs'] ?? [], $techniques)) {
                continue;
            }
            if (!empty($t['any']) && !array_intersect($t['any'], $techniques)) {
                continue;
            }
            if (($t['figurines'] ?? null) !== null && $rank[$figurines] < $rank[$t['figurines']]) {
                continue;
            }
            [$min, $max] = $t['tiers'];
            if ($tiers < $min || $tiers > $max) {
                continue;
            }
            $feasible[$key] = $t;
        }

        $forOccasion = $occasion === '' ? $feasible
            : array_filter($feasible, fn($t) => in_array($occasion, $t['occasions'], true));
        if (count($forOccasion) < 4) {
            $forOccasion += $feasible;
        }
        // Rezerva da AI uvek ima bar jedan šablon.
        return $forOccasion ?: ['poruka' => self::ALL['poruka']];
    }

    public static function composition(string $key): ?string
    {
        return self::ALL[$key]['composition'] ?? null;
    }
}
