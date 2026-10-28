# Plan aplikacije (radni naziv: „Torte")

Status: plan odobren. **Faza 1a je napravljena** (vidi README.md i docs/UPUTSTVO-HOSTING.md).

## 1. Odluke koje su donete

| Pitanje | Odluka |
|---|---|
| Model | Platforma: jedna aplikacija, mnogo poslastičarnica, svaka ima svoj link |
| Kome stižu porudžbine | Poslastičarnici (klijentu platforme) |
| Jezik | Samo srpski (latinica). Austrija kasnije kao zaseban domen i prevod |
| Plaćanje u prvoj verziji | Avans preko IPS QR koda i uplate na račun, ili plaćanje pri preuzimanju. Kartice kasnije |
| Prodaja | Bez intervjua. Pravi se osnovna verzija i nudi se „na hladno" preko Instagrama |
| Domen (test faza) | `proba.sbs` je jednostavan admin dashboard za vlasnika platforme. Svaka poslastičarnica dobija poddomen, npr. `test.proba.sbs` |
| Prvi klijent | Izmišljena poslastičarnica na `test.proba.sbs`, na kojoj se sve gradi i testira |

**Posledica hladne prodaje:** demo je glavni prodajni alat. Za svaku poslastičarnicu kojoj pišete treba za par minuta da napravite **njen demo**: njeno ime, logo, boje i nekoliko torti. Onda u poruci pošaljete link: „Napravio sam vam stranicu za porudžbine, probajte". To je mnogo jače od opisa aplikacije.

## 1a. Domeni i poddomeni

```
proba.sbs              → admin dashboard za vas (poslastičarnice, demo, AI potrošnja)
test.proba.sbs         → izmišljena test poslastičarnica (kupac)
test.proba.sbs/admin   → admin panel te poslastičarnice
ime.proba.sbs          → svaki sledeći klijent, isto
```

**Jedan kod, jedna baza, za sve.** Aplikacija pročita poddomen iz adrese i učita odgovarajuću poslastičarnicu. Prilagođavanje klijentu (ime, logo, boje, katalog, cene, tehnike) čuva se u bazi, ne u kodu. Zato je „serijska proizvodnja" samo novi red u bazi, napravljen kroz vaš dashboard.

Dva načina da poddomen stigne do aplikacije na hostingu:

1. **Wildcard poddomen `*.proba.sbs` (preporuka):** jednom se podesi DNS i hosting da svi poddomeni idu u isti folder. Novi klijent se pravi samo u dashboardu, bez diranja hostinga. Potreban je i wildcard SSL sertifikat, koji cPanel AutoSSL daje ako je DNS na istom hostingu.
2. **Ručno po klijentu:** u cPanelu se doda poddomen koji pokazuje na isti folder. Radi svuda, ali je jedan korak više po klijentu.

Kod radi isto u oba slučaja, pa se odluka može doneti kad se zna koji je hosting.

Kasnije klijent može da poveže i svoj domen (npr. `porudzbine.slatkikutak.rs`). Aplikacija će ga prepoznati na isti način.

## 2. Uloge

1. **Kupac**: dolazi sa Instagrama, bez registracije, uglavnom sa telefona.
2. **Poslastičar** (klijent): ima admin panel svoje poslastičarnice.
3. **Vi** (vlasnik platforme): superadmin. Otvarate poslastičarnice i demo naloge, pratite potrošnju AI-a.

## 3. Tok za kupca (telefon na prvom mestu)

Link: `vasdomen.rs/ime-poslasticarnice`

1. **Početna**: logo, kratak opis, izbor „Torte" ili „Kolači".
2. **Torta, korak po korak**:
   1. **Veličina**: broj osoba, na osnovu kojeg se računaju kilogrami. Okrugla ili četvrtasta, broj spratova. Sve to podešava poslastičar.
   2. **Ukus**: lista ukusa sa opisom, alergenima i eventualnom doplatom.
   3. **Motiv**: kupac opiše temu („rođendan za devojčicu od 5 godina, jednorozi, roze i zlatna"), izabere boje, upiše natpis na torti i dobije **6 AI dizajna** (detaljno u poglavlju 4). Bira jedan i može da doda napomenu. Opcija „bez AI-a": kupac samo opiše ili izabere nešto iz galerije radova.
   4. **Datum i preuzimanje**: kalendar prikazuje samo slobodne dane (rok poručivanja i kapacitet), uz izbor preuzimanja ili dostave.
3. **Kolači**: obična lista sa količinom (kg ili komad) i korpom.
4. **Pregled i podaci**: ime, telefon, email, napomena, okvirna cena i pravila avansa. Uz to obavezno polje „Razumem da je slika ilustracija".
5. **Potvrda**: broj porudžbine. Ako poslastičar traži avans, prikazuju se IPS QR kod i podaci za uplatu. Kupac dobija email sa rezimeom.

**Okvirna, ne konačna cena.** Prikazuje se kao „od X RSD". Poslastičar je potvrđuje ili koriguje, jer složenost dizajna procenjuje on.

## 4. AI dizajn

### Kako radi (dva koraka)

1. **Claude** (Anthropic) dobija:
   - kupčev opis, boje, natpis, veličinu i broj spratova,
   - **profil mogućnosti poslastičarnice**: koje tehnike radi (šlag ili krem obloga, fondan, drip, sveže cveće, jestiva slika, topperi od papira ili akrila, modelovane figurice da/ne, najviše N spratova...),
   - pravila: bez licenciranih likova i logotipa, samo ono što se ručno može napraviti, realne proporcije.

   Rezultat je 6 različitih opisa dizajna. Svaki ima naziv, kratak opis za kupca, tehnike koje koristi, složenost (1–3) i detaljan prompt za sliku.
2. **Model za slike** pravi 6 slika, svaku posebno, da bi se pojavljivale jedna po jedna. Tako je i za shared hosting bezbednije: ne stoji jedan dug zahtev od 60+ sekundi.

### Zašto dva koraka
- „Napravljivost" se ne može postići samo promptom za sliku. Claude zato prvo odluči šta je realno za **tu** poslastičarnicu, pa tek onda opisuje sliku.
- Složenost (1–3) služi poslastičaru kao pomoć za cenu.

### Likovi iz crtaća
AI nikad ne crta zaštićene likove (Disney, Paw Patrol, Marvel...). Pravi „temu": boje, simbole, atmosferu. Ako kupac napiše „Elsa", dobija „ledenu, zimsku princezu temu u plavoj i srebrnoj". U napomeni može da napiše šta želi, a dogovor je između njega i poslastičara.

### Ograničenja i troškovi
- Najviše 2 generisanja (2 × 6 slika) po kupcu i porudžbini, plus dnevni limit po IP adresi i mesečni limit po poslastičarnici.
- Procena troška po generisanju (6 slika): Claude oko 0,04 $ plus slike 0,03–0,25 $, ukupno **oko 0,10–0,30 $**. Tačno ćemo izmeriti u fazi 1.
- **Model za slike** treba izabrati testom: isti 10 motiva kroz 2–3 servisa (OpenAI gpt-image, Google Gemini/Imagen, Flux), pa poredimo kvalitet i cenu. Kod je napisan tako da se servis menja u podešavanjima.

## 5. Admin panel poslastičara

**Faza 1 (MVP):**
- Porudžbine: lista, detalj sa izabranim dizajnom, statusi (nova → potvrđena → avans plaćen → u izradi → gotova → preuzeta / otkazana), konačna cena, napomena.
- Obaveštenje o novoj porudžbini emailom.
- Katalog: veličine, ukusi (sa alergenima), kolači, cene.
- Profil mogućnosti (tehnike za AI).
- Kalendar: minimalan rok poručivanja, najviše torti po danu, neradni dani.
- Podešavanja: ime, logo, boje, Instagram, telefon, uslovi avansa (%) i podaci računa za IPS QR.
- Galerija radova (fotografije poslastičara), prikazuje se kupcu.

**Faza 2:**
- Dnevni spisak za proizvodnju (šta se pravi kog dana).
- Obaveštenja kupcu o promeni statusa (email, kasnije Viber ili WhatsApp).
- Baza kupaca i podsetnik „rođendan je za mesec dana" (uz pristanak kupca).
- Statistika: broj porudžbina, promet, najpopularniji ukusi.

## 6. Superadmin (vi), na `proba.sbs`

Namerno jednostavan:
- Brzo pravljenje poslastičarnice ili demoa: ime, slug (link), logo, boje, šablon kataloga („tipična srpska poslastičarnica": veličine, ukusi, kolači sa okvirnim cenama), koji se posle samo menja.
- Pregled svih poslastičarnica: status (demo / probni period / aktivna / ugašena), broj porudžbina, AI potrošnja.
- Uključivanje i isključivanje AI-a i limita po poslastičarnici.

## 7. Tehnologija

| Deo | Izbor | Razlog |
|---|---|---|
| Backend | PHP 8.2+, bez velikog frameworka, Composer za biblioteke | Radi na svakom shared hostingu |
| Baza | MySQL / MariaDB | Standard na shared hostingu |
| Frontend | Običan HTML, CSS i JavaScript, bez build koraka | Brzo na telefonu, jednostavno za održavanje |
| AI tekst | Claude API (zvanični Anthropic PHP SDK) | Opisi dizajna i provera napravljivosti |
| AI slike | Zameniv servis (test u fazi 1) | Cena i kvalitet se brzo menjaju |
| Email | SMTP (PHPMailer) | `mail()` na shared hostingu često završi u spamu |
| IPS QR | NBS IPS QR standard (string + QR slika) | Bez troška kartičnog plaćanja. Detalje treba proveriti |

**Zahtevi za hosting:** PHP 8.2+, MySQL, cURL, `max_execution_time` od najmanje 60 s (zbog generisanja slike), SSL i mogućnost cron zadataka (za podsetnike u fazi 2).

**Bezbednost:** CSRF zaštita, hešovane lozinke, API ključevi samo na serveru (nikad u browseru), limiti protiv zloupotrebe AI-a, provera tipa i veličine uploadovanih slika.

## 8. Podaci (glavne tabele)

- `shops`: poslastičarnica (slug, ime, boje, logo, status, uslovi, podaci za IPS, AI limiti, profil mogućnosti)
- `users`: nalozi (poslastičar ili superadmin)
- `cake_sizes`, `cake_flavors`, `products` (kolači), `gallery_images`
- `blocked_days`, kapacitet po danu
- `design_sessions`: jedno generisanje (opis kupca, Claude odgovor, trošak)
- `design_images`: 6 slika po sesiji
- `orders`, `order_items`: porudžbina, stavke, izabrani dizajn, status, cene
- `ai_usage`: evidencija troškova po poslastičarnici

## 9. Faze

| Faza | Sadržaj | Cilj |
|---|---|---|
| **0. Test AI-a** | Skripta: 10 tipičnih motiva kroz Claude i 2–3 servisa za slike, poređenje slika i cene | Odluka koji servis, i dokaz da su dizajni „napravljivi" |
| **1a. test.proba.sbs** | Izmišljena poslastičarnica: tok kupca (torte i kolači), AI dizajn, admin poslastičara, email obaveštenja, IPS QR. Plus jednostavan dashboard na `proba.sbs` | Sve radi i testirano je na jednom primeru |
| **1b. Prvi pravi klijent** | Na konkretnom primeru: novi poddomen, prilagođavanje (logo, boje, katalog, tehnike) | Vidimo šta fali i šta se ponavlja |
| **1c. Serijska proizvodnja** | Šabloni kataloga, pravljenje demoa u par klikova, uputstvo za podešavanje poddomena | Demo za novog klijenta za 5 minuta |
| **2. Rast** | Spisak za proizvodnju, obaveštenja kupcu, baza kupaca i podsetnici, statistika, upload referentne slike kupca | Zadržavanje klijenata |
| **3. Plaćanje i Austrija** | Kartice (npr. Monri), pretplata poslastičara, nemački prevod i zaseban domen | Širenje |

## 10. Poslovni model (predlog, odluka kasnije)

- Probni period 30 dana besplatno.
- Mesečna pretplata, npr. 1.990–2.990 RSD, uz uključen broj AI generisanja. Preko limita se ili doplaćuje ili se AI isključuje.
- Alternativa: mali procenat ili fiksni iznos po porudžbini. Ovo je teže za naplatu jer se plaćanje vrši van platforme.

## 11. Rizici

| Rizik | Mera |
|---|---|
| Slika obećava više nego što poslastičar može | Profil mogućnosti, „ilustracija" kao obavezna potvrda, poslastičar potvrđuje konačnu cenu i izgled |
| Likovi iz crtaća | AI ih nikad ne crta, pravi samo temu |
| Trošak AI-a (zloupotreba) | Limiti po kupcu, IP adresi i poslastičarnici, plus evidencija troška |
| Spor odgovor AI-a na shared hostingu | Svaka slika u zasebnom zahtevu, prikaz jedna po jedna |
| Poslastičari ne žele još jedan alat | Sve stiže emailom, admin radi na telefonu, demo je već popunjen |
| Neformalni (kućni) poslastičari | Aplikacija ne traži firmu. IPS QR radi i sa ličnim računom (treba proveriti) |

## 12. Otvorena pitanja

1. ~~Naziv platforme i domen?~~ Za test fazu: `proba.sbs`.
2. Koji hosting? Da li podržava wildcard poddomene (`*.proba.sbs`)?
3. Da li odmah otvarate API naloge (Anthropic i servis za slike) za fazu 0?
