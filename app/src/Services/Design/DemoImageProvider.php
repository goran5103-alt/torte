<?php
declare(strict_types=1);

namespace App\Services\Design;

/** Nacrtana ilustracija torte (SVG), bez AI-a i bez troška. Služi za testiranje toka. */
final class DemoImageProvider implements ImageProvider
{
    public function name(): string
    {
        return 'demo';
    }

    public function generate(array $design, array $request): array
    {
        $colors = $design['colors'] ?? ($request['colors'] ?: ['roze', 'bela']);
        $c1 = Palette::hex($colors[0] ?? 'roze');
        $c2 = Palette::hex($colors[1] ?? 'bela');
        $tiers = max(1, min(3, (int)($request['size']['tiers'] ?? 1)));
        $complexity = (int)($design['complexity'] ?? 1);
        $seed = crc32(($design['title'] ?? '') . ($request['description'] ?? ''));
        $techniques = $design['techniques'] ?? [];

        $svg = [];
        $svg[] = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="1024" height="1024">';
        $svg[] = '<defs><linearGradient id="bg" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#f7f3f0"/><stop offset="1" stop-color="#ebe4df"/></linearGradient></defs>';
        $svg[] = '<rect width="512" height="512" fill="url(#bg)"/>';
        // postolje
        $svg[] = '<ellipse cx="256" cy="430" rx="170" ry="22" fill="#ffffff" stroke="#ddd"/>';
        $svg[] = '<rect x="236" y="432" width="40" height="40" fill="#ffffff" stroke="#ddd"/>';

        $bottom = 425;
        $width = 300;
        $height = $tiers === 1 ? 170 : ($tiers === 2 ? 120 : 92);
        for ($t = 0; $t < $tiers; $t++) {
            $w = $width - $t * 70;
            $x = 256 - $w / 2;
            $y = $bottom - $height;
            $fill = $t % 2 === 0 ? $c1 : $c2;
            $svg[] = sprintf('<rect x="%d" y="%d" width="%d" height="%d" rx="10" fill="%s" stroke="rgba(0,0,0,.08)"/>', $x, $y, $w, $height, $fill);
            $svg[] = sprintf('<ellipse cx="256" cy="%d" rx="%d" ry="14" fill="%s" opacity=".9"/>', $y, $w / 2, $this->lighten($fill));

            if (in_array('drip', $techniques, true) && $t === $tiers - 1) {
                for ($i = 0; $i < 9; $i++) {
                    $dx = $x + 15 + $i * ($w - 30) / 8;
                    $len = 18 + (($seed >> $i) & 15) * 2;
                    $svg[] = sprintf('<rect x="%d" y="%d" width="12" height="%d" rx="6" fill="#5a3420"/>', $dx - 6, $y, $len);
                }
                $svg[] = sprintf('<ellipse cx="256" cy="%d" rx="%d" ry="14" fill="#5a3420"/>', $y, $w / 2);
            }
            // ukrasi na ivici
            $dots = 6 + $complexity * 3;
            for ($i = 0; $i < $dots; $i++) {
                $dx = $x + 12 + $i * ($w - 24) / max(1, $dots - 1);
                $svg[] = sprintf('<circle cx="%d" cy="%d" r="%d" fill="%s"/>', $dx, $bottom - 6, 5 + $complexity, $c2 === $fill ? '#ffffff' : $c2);
            }
            $bottom = $y;
        }

        // vrh: topper ili cvetići
        $top = $bottom;
        if ($complexity >= 2) {
            for ($i = 0; $i < 3 + $complexity; $i++) {
                $cx = 256 + ($i - (2 + $complexity) / 2) * 26;
                $svg[] = sprintf('<circle cx="%d" cy="%d" r="11" fill="%s" stroke="#fff" stroke-width="3"/>', $cx, $top - 8, $i % 2 ? $c2 : '#ffffff');
            }
        }
        $inscription = trim((string)($request['inscription'] ?? ''));
        if ($inscription !== '') {
            $svg[] = sprintf('<text x="256" y="%d" font-family="Georgia, serif" font-size="26" text-anchor="middle" fill="#3a2a2a">%s</text>',
                $top - 40, htmlspecialchars(mb_strimwidth($inscription, 0, 22, '…'), ENT_XML1));
        }
        $svg[] = sprintf('<text x="256" y="52" font-family="system-ui, sans-serif" font-size="22" font-weight="700" text-anchor="middle" fill="#3a2a2a">%s</text>',
            htmlspecialchars((string)($design['title'] ?? ''), ENT_XML1));
        $svg[] = '<text x="256" y="80" font-family="system-ui, sans-serif" font-size="14" text-anchor="middle" fill="#8a7f79">DEMO ilustracija · bez AI slike</text>';
        $svg[] = '</svg>';

        return ['bytes' => implode("\n", $svg), 'ext' => 'svg', 'cost_usd' => 0.0, 'model' => 'demo'];
    }

    private function lighten(string $hex): string
    {
        $hex = ltrim($hex, '#');
        $rgb = array_map('hexdec', str_split($hex, 2));
        return sprintf('#%02x%02x%02x', ...array_map(fn($c) => (int)min(255, $c + (255 - $c) * .35), $rgb));
    }
}
