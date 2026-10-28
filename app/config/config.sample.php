<?php
// Primer konfiguracije. Instalacija (https://proba.sbs/install) pravi config.php automatski.
return [
    // Nasumičan dugačak string, koristi se za potpisivanje linkova
    'app_secret' => 'promeni-me',

    // Glavni domen (dashboard). Poslastičarnice su na poddomenima: ime.proba.sbs
    'main_domain' => 'proba.sbs',
    'scheme' => 'https',

    'db' => [
        'host' => 'localhost',
        'port' => 3306,
        'name' => '',
        'user' => '',
        'pass' => '',
    ],

    'mail' => [
        // smtp | mail | log (log = upis u app/storage/logs/mail.log, za testiranje)
        'transport' => 'log',
        'host' => '',
        'port' => 587,
        'user' => '',
        'pass' => '',
        'secure' => 'tls',
        'from_email' => 'no-reply@proba.sbs',
        'from_name' => 'Porudžbine',
    ],

    'anthropic' => [
        // Bez ključa se koriste demo opisi dizajna
        'api_key' => '',
        'model' => 'claude-opus-5-5',
        'effort' => 'medium',
    ],

    'images' => [
        // demo | openai | gemini
        'provider' => 'demo',
        'gemini_api_key' => '',
        'gemini_model' => 'gemini-2.5-flash-image',
        'gemini_cost_per_image_usd' => 0.039,
        'openai_api_key' => '',
        'openai_model' => 'gpt-image-2',
        'openai_quality' => 'medium',
        // Procena troška po slici (USD), za evidenciju potrošnje
        'cost_per_image_usd' => 0.04,
    ],

    'limits' => [
        // Koliko puta jedan kupac može da generiše 6 dizajna
        'sessions_per_client' => 2,
        // Koliko generisanja dnevno sa jedne IP adrese
        'sessions_per_ip_per_day' => 6,
    ],

    // Samo za lokalni razvoj: dozvoljava ?shop=slug u adresi
    'dev' => false,
];
