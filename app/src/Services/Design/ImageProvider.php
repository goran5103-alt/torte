<?php
declare(strict_types=1);

namespace App\Services\Design;

/** Servis koji od opisa dizajna pravi sliku. Menja se u config.php (images.provider). */
interface ImageProvider
{
    /**
     * @param array $design jedan opis dizajna (title, image_prompt, colors...)
     * @param array $request kupčeva želja (inscription, size...)
     * @return array{bytes: string, ext: string, cost_usd: float, model: string}
     */
    public function generate(array $design, array $request): array;

    public function name(): string;
}
