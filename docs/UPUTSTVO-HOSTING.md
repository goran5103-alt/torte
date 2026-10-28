# Postavljanje na DirectAdmin hosting (bez SSH-a)

Primer za domen `proba.sbs` i test poslastičarnicu `test.proba.sbs`.

## 1. Pre uploada

1. **PHP verzija:** u DirectAdmin-u (Account Manager → Select PHP version) izaberite PHP **8.2 ili noviji**. Potrebne ekstenzije su pdo_mysql, curl, gd i mbstring (obično su uključene).
2. **Baza:** Account Manager → MySQL Management → Create new Database. Zapišite ime baze, korisnika i lozinku.
3. **SSL:** Account Manager → SSL Certificates → Let's Encrypt za `proba.sbs` i `www.proba.sbs`.

## 2. Upload

1. Otvorite File Manager i uđite u `domains/proba.sbs/`.
2. Uploadujte `torte-paket.zip` i izaberite **Extract** u tom folderu. Dobićete:
   ```
   domains/proba.sbs/
   ├── app/               aplikacija (nije dostupna sa interneta)
   ├── public_html/       javni deo (spaja se sa postojećim)
   └── subdomain-stub/    fajlovi za poddomene
   ```
3. U `public_html` obrišite podrazumevani `index.html`, ako postoji.

## 3. Instalacija

1. Otvorite **https://proba.sbs/install**.
2. Stranica proverava server. Sve stavke treba da budu zelene. Ako „Upis u…" nije zelen, u File Manager-u postavite dozvole tog foldera na 755.
3. Unesite podatke baze iz koraka 1.2, glavni domen `proba.sbs` i vaš admin email i lozinku.
4. Posle instalacije se otvara prijava. Instalacija više nije dostupna, jer je napravljen `app/config/config.php`.

## 4. Test poslastičarnica (`test.proba.sbs`)

1. **Poddomen:** Account Manager → Subdomain Management → dodajte `test`. DirectAdmin pravi folder `public_html/test`.
2. U folder `public_html/test` uploadujte **oba** fajla iz `subdomain-stub`: `index.php` i `.htaccess`. Fajl `.htaccess` je skriven; u File Manager-u uključite prikaz skrivenih fajlova.
3. **SSL** za `test.proba.sbs` (Let's Encrypt, isto kao u koraku 1.3).
4. Na **https://proba.sbs** kliknite **+ Nova poslastičarnica**:
   - poddomen: `test`
   - početni katalog: **Test: Poslastičarnica Suzana**
   - status: Demo
5. Na sledećoj stranici su prikazani adresa, email i lozinka za admin poslastičara. Dugme **Uđi u admin** vas uvodi bez lozinke.

Svaka sledeća poslastičarnica ide istim koracima 1–5 (kasnije i bez koraka 1–3, kad se uključi wildcard poddomen).

## 5. AI ključevi

Dok ključevi nisu uneti, aplikacija radi u **demo režimu**: opisi dizajna su šablonski, a slike su nacrtane ilustracije. Ceo tok može da se testira bez troška.

U File Manager-u otvorite `app/config/config.php` (Edit) i popunite:

```php
'anthropic' => [
    'api_key' => 'sk-ant-...',          // console.anthropic.com → API Keys
    'model' => 'claude-opus-5-5',
    'effort' => 'medium',
],
'images' => [
    'provider' => 'openai',             // 'demo' = bez AI slika
    'openai_api_key' => 'sk-...',       // platform.openai.com → API keys
    'openai_model' => 'gpt-image-1',
    'openai_quality' => 'medium',
    'cost_per_image_usd' => 0.04,
],
```

Na oba sajta postavite mesečni limit potrošnje.

## 6. Email

1. Napravite email nalog, npr. `no-reply@proba.sbs` (E-mail Manager → E-mail Accounts).
2. U `app/config/config.php`:

```php
'mail' => [
    'transport' => 'smtp',       // 'log' = upis u app/storage/logs/mail.log (za test)
    'host' => 'mail.proba.sbs',
    'port' => 587,
    'user' => 'no-reply@proba.sbs',
    'pass' => 'lozinka-email-naloga',
    'secure' => 'tls',
    'from_email' => 'no-reply@proba.sbs',
    'from_name' => 'Porudžbine',
],
```

## 7. Ažuriranje

1. Uploadujte novi `torte-paket.zip` u `domains/proba.sbs/` i izaberite Extract (prepisati postojeće fajlove). `config.php` i uploadovane slike se ne diraju.
2. Ako dashboard prikaže „Postoje nove izmene baze", kliknite **Ažuriraj bazu**.

## 8. Indeksiranje

Nova poslastičarnica je podrazumevano sakrivena od Google-a (noindex i robots.txt). Glavni domen i svi admin delovi su uvek sakriveni. Kad poslastičarnica postane prava, u dashboard-u isključite „Sakrij od Google-a".
