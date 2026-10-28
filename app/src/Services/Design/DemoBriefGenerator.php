<?php
declare(strict_types=1);

namespace App\Services\Design;

/** Opisi bez AI-a (kad nema API ključa) — da bi ceo tok mogao da se testira. */
final class DemoBriefGenerator implements BriefGenerator
{
    public function name(): string
    {
        return 'demo';
    }

    public function generate(array $request, array $capabilities): array
    {
        $colors = $request['colors'] ?: ['bela', 'roze'];
        $c1 = $colors[0];
        $c2 = $colors[1] ?? 'bela';
        $techniques = $capabilities['techniques'] ?? ['krem'];
        $has = fn(string $t) => in_array($t, $techniques, true);
        $theme = mb_strimwidth(trim((string)($request['description'] ?? '')), 0, 60, '…');

        $base = $has('fondan') ? 'fondan' : 'krem';
        $variants = [
            ['Nežna klasika', "Glatka $c1 obloga sa diskretnim ukrasima na ivici.", [$base], 1],
            ['Drip radost', "Torta u boji $c1 sa prelivom koji se sliva i slatkišima na vrhu.", [$has('drip') ? 'drip' : $base, $has('slatkisi') ? 'slatkisi' : $base], 2],
            ['Tema na vrhu', "Tema „{$theme}\" prikazana jestivom slikom ili topperom na vrhu.", [$has('jestiva_slika') ? 'jestiva_slika' : 'topper_papir'], 1],
            ['Dvobojni talasi', "Prelaz iz $c1 u $c2 sa teksturom kremom.", ['krem'], 2],
            ['Svečana', "Elegantna torta sa $c2 detaljima i ukrasima od šećerne paste.", [$base, $has('secerno_cvece') ? 'secerno_cvece' : $base], 3],
            ['Razigrana', "Vesela torta u bojama $c1 i $c2 sa figuricama i natpisom.", [$base, 'topper_papir'], 3],
        ];

        $designs = [];
        foreach ($variants as [$title, $desc, $tech, $complexity]) {
            $designs[] = [
                'title' => $title,
                'description' => $desc,
                'techniques' => array_values(array_unique(array_intersect($tech, $techniques) ?: [$base])),
                'complexity' => $complexity,
                'image_prompt' => "Demo design \"$title\" in " . implode(' and ', Palette::english($colors) ?: ['pink']),
                'colors' => [$c1, $c2],
            ];
        }
        return [
            'designs' => $designs,
            'usage' => ['input_tokens' => 0, 'output_tokens' => 0, 'cost_usd' => 0.0, 'model' => 'demo'],
        ];
    }
}
