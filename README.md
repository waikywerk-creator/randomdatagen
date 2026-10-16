# Nepdata-generator

Een Laravel-app waarmee je zelf tabellen met reproduceerbare, synthetische testdata samenstelt voor ontwikkeling, demo's en tests.

## Quick install

Vereist: **PHP 8.3+** en **Composer 2** in je PATH. Composer controleert de benodigde PHP-extensies, waaronder `zip`, `gd`, `mbstring` en XML.

Clone of download deze repository en open een terminal in de projectmap. Voer bij een nieuwe installatie uit:

```shell
composer run setup
composer run dev
```

Open **http://127.0.0.1:8000**. `setup` installeert de PHP-pakketten, maakt zo nodig `.env` en genereert de applicatiesleutel. Je hoeft geen database te maken of migraties uit te voeren: sessies en cache gebruiken bestanden en taken worden direct verwerkt. Node.js en een frontend-build zijn niet nodig voor deze interface.

Bij volgende keren starten is alleen `composer run dev` nodig. Stop de server met `Ctrl+C`. Op Windows kun je ook dubbelklikken op **`Start Nepdata Generator.cmd`**.

## Quick use

1. Kies **Personen**, **Bedrijven**, **Auto’s**, **Computers** of **Zelf samenstellen**.
2. Voeg kolommen toe en pas namen, typen en volgorde aan. Bijvoorbeeld **Toegewezen aan** met het type **Volledige naam** bij computers.
3. Kies het aantal rijen en het bestandsformaat: **Excel**, **CSV** of **JSON**.
4. Optioneel: vul **Studie** als kolomnaam in en klik op **Waarden voorstellen**. Kies een ingebouwde lijst zonder AI, of OpenAI met je eigen API-sleutel. Bekijk het voorstel en klik op **Gebruik deze keuzelijst**.
5. Klik op **Genereren en downloaden**. Met **Eerst een voorbeeld bekijken** controleer je vooraf de eerste tien rijen.

Voor gewoon gebruik heb je geen API-sleutel nodig. Alleen de optionele OpenAI-functie gebruikt je eigen sleutel en API-tegoed.

## Mogelijkheden

- Standaardsets voor personen, bedrijven, auto's en computers, of zelf samenstellen
- Kolommen uit alle onderwerpen combineren, hernoemen, toevoegen, verwijderen en verplaatsen
- Meer dan 50 kolomtypen: namen, contactgegevens, adressen, bedrijven, auto's, hardware, getallen, datums, tekst en technische gegevens
- Instelbare numerieke bereiken en decimalen, datumbereiken, eigen keuzelijsten en vaste tekst
- Waarden voorstellen op basis van een kolomnaam: ingebouwde lijsten zonder AI, of OpenAI met een zelf ingevoerde API-sleutel
- 1–1.000 rijen en 1–50 kolommen per bestand
- Samenhangende namen, e-mailadressen, automodellen en computerconfiguraties
- Direct genereren en downloaden als echte Excel (`.xlsx`), UTF-8 CSV of JSON
- Optioneel voorbeeld van de eerste tien rijen
- Reproduceerbare output met een optionele seed; de browser vult een lege seed bij verzenden automatisch in
- Geen opslag van gegenereerde datasets

Contactgegevens blijven bewust fictief: e-mailadressen en websites gebruiken `.test`; adressen en telefoonnummers zijn testwaarden. Hardware- en voertuiggegevens zijn voorbeeldconfiguraties uit een vaste catalogus, geen actuele productcatalogus. De generator maakt geen BSN, IBAN, geldig KvK-nummer of geldig btw-nummer.

## Instellingen en keuzelijsten

Een eigen keuzelijst bevat één waarde per regel. Voor getallen stel je een minimum, maximum en eventueel het aantal decimalen in. Voor datums stel je een begin- en einddatum in; beide grenzen zijn inclusief. Een percentage van `10` verschijnt in Excel als `10%` (de numerieke celwaarde is `0,1`).

### Keuzelijst laten voorstellen

Vul een kolomnaam in, bijvoorbeeld **Studie**, en klik bij die kolom op **Waarden voorstellen**. Er opent een venster met twee opties:

- **Zonder AI — ingebouwde lijsten**: direct een voorstel voor een bekend onderwerp, zonder sleutel of externe verbinding.
- **OpenAI — eigen API-sleutel**: vul je API-sleutel, model en gewenste aantal waarden (1–100) in. Voeg eventueel een toelichting toe, zoals “Nederlandse hbo-studies in de techniek”. Standaard wordt `gpt-4.1-mini` gebruikt; een ander model moet gestructureerde antwoorden ondersteunen en beschikbaar zijn voor je API-account. Het verzoek gebruikt je eigen OpenAI API-tegoed.

Klik op **Voorstel maken**, bekijk en wijzig het voorstel en klik op **Gebruik deze keuzelijst**. De kolom wordt dan een **Eigen keuzelijst**. De generator kiest per rij een van deze waarden; alle exportformaten gebruiken dezelfde lijst. Bestaande waarden blijven staan totdat je een voorstel toepast. Controleer AI-voorstellen op inhoudelijke juistheid.

De lokale optie werkt met 19 ingebouwde onderwerpen en Nederlandse en Engelse synoniemen, ook in namen als **Gevolgde opleiding**. Specifieke onderwerpen zoals **Opleidingsniveau** en **Factuurstatus** hebben een eigen lijst. Het zijn voorbeeldlijsten, geen volledige of actuele registers. Een onbekend onderwerp krijgt bij deze optie een melding; je kunt dan zelf waarden invullen of OpenAI kiezen.

De API-sleutel wordt alleen voor het gekozen verzoek gebruikt. De app schrijft hem niet naar browseropslag, `.env`, sessie, cache of export; het invoerveld wordt bij verzenden en sluiten gewist. Er wordt geen sleutel in de pagina opgenomen. De OpenAI-aanroep loopt via de Laravel-server naar de vaste officiële API; alleen de kolomnaam, toelichting en het gewenste aantal waarden gaan mee als onderwerpgegevens. De gegenereerde tabel wordt niet meegestuurd. API-verzoeken gebruiken `store: false`, een time-out en geen automatische retries. In productie hoort de app via HTTPS bereikbaar te zijn.

De koppeling gebruikt de [OpenAI Responses API met gestructureerde antwoorden](https://developers.openai.com/api/docs/guides/structured-outputs?api-mode=responses). De tests gebruiken gesimuleerde API-antwoorden, inclusief geweigerde sleutels, limieten, time-outs, onvolledige antwoorden en ongeldige waarden. Voor een echte OpenAI-controle voer je zelf een geldige API-sleutel in het venster in.

Met dezelfde seed en instellingen krijg je dezelfde gegevens, ook bij een ander exportformaat. Kolommen verplaatsen of verwijderen verandert de waarden in de overige kolommen niet. Wis de seed voor een nieuwe dataset. Van onderwerp wisselen laadt de standaardkolommen voor dat onderwerp.

## Starten

De snelle installatie staat hierboven. Hieronder vind je de Windows-starter en de losse installatiecommando's.

### Makkelijkste manier op Windows

Dubbelklik op **`Start Nepdata Generator.cmd`** in de projectmap. De starter:

- controleert automatisch of PHP beschikbaar is;
- maakt zo nodig `.env` en de applicatiesleutel aan;
- installeert eenmalig ontbrekende pakketten via je geïnstalleerde Composer;
- start Laravel en opent de generator in je browser.

Op een nieuwe installatie moet Composer 2 beschikbaar zijn. De starter ondersteunt ook een lokaal aanwezige `composer.phar` als alternatief; dit bestand wordt niet in de repository opgenomen.

Laat het geopende terminalvenster actief zolang je de generator gebruikt. Druk in dat venster op `Ctrl+C` om de applicatie te stoppen.

Je kunt de starter ook vanuit PowerShell uitvoeren:

```powershell
.\start.ps1
```

Een andere poort gebruiken of de browser niet automatisch openen:

```powershell
.\start.ps1 -Port 8080
.\start.ps1 -NoBrowser
```

### Handmatig starten

Voor een nieuwe installatie in PowerShell:

```powershell
Copy-Item .env.example .env
composer install
php artisan key:generate
php artisan serve
```

Op macOS/Linux vervang je de eerste regel door `cp .env.example .env`. Gebruik bij een bestaande installatie de bestaande `.env` en applicatiesleutel; start dan alleen de server.

De voorbeeldinstellingen zijn `SESSION_DRIVER=file`, `CACHE_STORE=file` en `QUEUE_CONNECTION=sync`. Heb je nog een oudere `.env` met database-instellingen, pas deze drie waarden dan aan en voer `php artisan config:clear` uit. De bestaande lokale `.env` wordt door de starter niet overschreven.

Open daarna `http://127.0.0.1:8000`. De styling en JavaScript staan direct in `public/`.

## Bestanden in de repository

Commit de broncode, tests, startscripts, README, `.env.example`, `composer.json` en **`composer.lock`**. Laat de kleine `.gitignore`-bestanden in de opslag-, cache- en databasemappen staan; daarmee blijft de benodigde mappenstructuur behouden.

Persoonlijke `.env`-bestanden, `composer.phar`, afhankelijkheden (`vendor/`, `node_modules/`), de lokale SQLite-database, logs, sessies, caches en eventuele exports in `exports/` worden genegeerd. Gebruik `.env.example` alleen voor voorbeeldinstellingen zonder geheime sleutels.

## Testen en formatteren

```powershell
php artisan test
php vendor/bin/pint
```

## Opbouw

- `config/datasets.php`: centrale catalogus van kolomtypen en standaardsets
- `config/choice_suggestions.php`: lokale voorbeeldlijsten en synoniemen voor kolomnamen
- `app/Services/ChoiceSuggestions.php`: herkenning van bekende onderwerpen op basis van de kolomnaam
- `app/Services/OpenAiChoiceSuggestions.php`: OpenAI-verzoeken en controle van voorgestelde waarden
- `app/Services/DatasetCatalog.php`: catalogus en standaardinstellingen voor de interface
- `app/Services/DatasetGenerator.php`: kolommen genereren met gedeelde context per rij
- `app/Services/DatasetExporter.php`: CSV- en Excel-export met expliciete celtypen
- `app/Http/Requests/GenerateDatasetRequest.php`: schema-, bereik- en invoervalidatie
- `app/Http/Controllers/DatasetGeneratorController.php`: voorbeeld en directe download
- `resources/views/dataset-generator.blade.php` en `partials/dataset-column.blade.php`: Nederlandstalige interface
- `public/dataset-generator.js` en `.css`: kolomeditor en responsive vormgeving
- `app/Services/CustomerGenerator.php`: hergebruikte logica voor fictieve Nederlandse klantgegevens
- `tests/`: unit- en featuretests

Nieuwe standaardsets voeg je toe in `config/datasets.php`. Voor een nieuw kolomtype voeg je daar een definitie toe en implementeer je de waarde in `DatasetGenerator`; de editor en validatie gebruiken automatisch de catalogus. Pas voor een nieuw Excel-celtype zo nodig ook `DatasetExporter` aan.

Een nieuw lokaal onderwerp voor **Waarden voorstellen** voeg je toe in `config/choice_suggestions.php` met een `label`, `aliases` en `values`. Synoniemen worden als hele woorden of woordgroepen herkend; de langste passende woordgroep krijgt voorrang. `POST /datasets/choices/suggest` accepteert `name` en optioneel `provider` (`local` of `openai`; standaard `local`). Voor OpenAI geef je ook `api_key` en eventueel `model`, `count` en `context` mee. Antwoorden bevatten `label`, `values`, `source` en `topic`; fouten geven een JSON-melding zonder providerdiagnostiek of credentials.

## Export en compatibiliteit

`POST /datasets/preview` en `POST /datasets/download` accepteren `dataset`, `count`, een optionele `seed`, `format` (`xlsx`, `csv`, `json`) en een geordende lijst `columns`. Elke kolom bevat `name`, `type` en eventueel `min`, `max`, `decimals`, `from`, `to`, `choices` of `value`.

CSV gebruikt een puntkomma, een UTF-8 BOM voor Excel en bescherming tegen spreadsheet-formules in zowel kolomnamen als tekstwaarden. Negatieve numerieke waarden blijven getallen. Excel behoudt getallen, booleans, datums en tijden als getypeerde cellen, behandelt tekst altijd als letterlijke tekst en bevat filters en een vastgezette kopregel. JSON bevat metadata met het schema, de seed en `synthetic: true`.

De bestaande klantverzoeken op `POST /preview`, `POST /export/csv` en `POST /export/json` blijven beschikbaar, inclusief gemengde particuliere/zakelijke datasets en de oorspronkelijke kolomnamen.

## Licentie

Dit project is beschikbaar onder de [MIT-licentie](LICENSE).
