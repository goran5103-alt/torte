# Torte: platforma za poručivanje torti

Poslastičarnice dobijaju svoj link (`ime.proba.sbs`) koji kače na Instagram. Kupac preko njega bira veličinu, ukus i izgled torte (uz 6 AI predloga koje poslastičarnica realno može da napravi) ili kolače, bira slobodan datum i šalje porudžbinu. Poslastičar porudžbine prima emailom i u svom admin panelu.

- `docs/ISTRAZIVANJE.md`: istraživanje tržišta (Srbija, Austrija)
- `docs/PLAN.md`: plan i faze
- `docs/UPUTSTVO-HOSTING.md`: postavljanje na DirectAdmin bez SSH-a

## Delovi

| Adresa | Ko | Šta |
|---|---|---|
| `proba.sbs` | vlasnik platforme | poslastičarnice, nalozi, AI potrošnja |
| `ime.proba.sbs` | kupac | torta (čarobnjak + AI), kolači, korpa, porudžbina, IPS QR za avans |
| `ime.proba.sbs/admin` | poslastičar | porudžbine, proizvodnja, katalog, galerija, kalendar, AI tehnike, podešavanja |

Jedan kod i jedna baza služe sve poslastičarnice. Poslastičarnica se prepoznaje po poddomenu (ili sopstvenom domenu).

## Tehnologija

- PHP 8.1+ bez frameworka, MySQL/MariaDB, običan HTML/CSS/JS (bez build koraka)
- `anthropic-ai/sdk`: Claude pravi 6 opisa dizajna u skladu sa tehnikama poslastičarnice
- Slike: OpenAI Images API ili demo ilustracije (`app/src/Services/Design`)
- `phpmailer/phpmailer` za email, `chillerlan/php-qrcode` za NBS IPS QR

```
app/              aplikacija (van public_html)
  src/            kod (Controllers, Services, Kernel…)
  templates/      PHP šabloni
  database/       SQL migracije
  config/         config.php (pravi ga instalacija)
public/           public_html glavnog domena (index.php, assets, uploads)
subdomain-stub/   index.php + .htaccess za folder poddomena
tools/build.php   pravi build/torte-paket.zip za upload
```

## Lokalni razvoj

```bash
cd app && composer install
```

```bash
php -S 127.0.0.1:8080 -t public public/index.php
```

Otvorite `http://localhost:8080/install` (glavni domen: `localhost:8080`). Poslastičarnica je onda na `http://test.localhost:8080`.

## Paket za hosting

```bash
php tools/build.php
```
