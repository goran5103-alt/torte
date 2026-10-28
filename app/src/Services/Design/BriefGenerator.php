<?php
declare(strict_types=1);

namespace App\Services\Design;

/** Pravi 6 opisa dizajna torte na osnovu želje kupca i mogućnosti poslastičarnice. */
interface BriefGenerator
{
    /**
     * @param array $request kupčeva želja (occasion, description, colors, inscription, size)
     * @param array $capabilities profil mogućnosti (techniques, figurines, max_tiers, notes)
     * @return array{designs: list<array{title:string, description:string, techniques:list<string>, complexity:int, image_prompt:string}>, usage: array{input_tokens:int, output_tokens:int, cost_usd:float, model:string}}
     */
    public function generate(array $request, array $capabilities): array;

    public function name(): string;
}
