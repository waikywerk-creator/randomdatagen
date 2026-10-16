<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>Nepdata-generator</title>
    <style>
        :root {
            --ink: #17201d;
            --muted: #63706b;
            --line: #dce4df;
            --surface: #ffffff;
            --soft: #f3f7f4;
            --accent: #176b50;
            --accent-dark: #0f513d;
            --accent-soft: #e5f4ed;
            --danger: #b42318;
            --danger-soft: #fff1f0;
            --shadow: 0 18px 50px rgba(32, 55, 46, .08);
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        * { box-sizing: border-box; }
        body { margin: 0; min-width: 320px; color: var(--ink); background: #f7f9f7; }
        button, input, select { font: inherit; }
        button, input, select, a { outline-offset: 3px; }
        [hidden] { display: none !important; }

        .page { width: min(1180px, calc(100% - 32px)); margin: 0 auto; padding: 42px 0 64px; }
        .masthead { display: flex; align-items: end; justify-content: space-between; gap: 24px; margin-bottom: 24px; }
        .eyebrow { margin: 0 0 8px; color: var(--accent); font-size: .76rem; font-weight: 800; letter-spacing: .11em; text-transform: uppercase; }
        h1 { margin: 0; font-size: clamp(2rem, 5vw, 3.25rem); line-height: .98; letter-spacing: -.045em; }
        .intro { max-width: 590px; margin: 12px 0 0; color: var(--muted); line-height: 1.6; }
        .status { flex: 0 0 auto; display: inline-flex; align-items: center; gap: 8px; padding: 8px 12px; border: 1px solid #c8dfd3; border-radius: 999px; color: var(--accent-dark); background: var(--accent-soft); font-size: .84rem; font-weight: 700; }
        .status-dot { width: 8px; height: 8px; border-radius: 50%; background: #2c936e; box-shadow: 0 0 0 4px rgba(44, 147, 110, .12); }

        .privacy { display: flex; gap: 14px; align-items: flex-start; margin-bottom: 24px; padding: 15px 18px; border: 1px solid #cfe3d8; border-radius: 14px; background: var(--accent-soft); color: #244c3d; line-height: 1.5; }
        .privacy svg { flex: 0 0 auto; margin-top: 1px; }
        .privacy strong { display: block; color: var(--accent-dark); }
        .privacy p { margin: 2px 0 0; font-size: .91rem; }

        .layout { display: grid; grid-template-columns: 350px minmax(0, 1fr); gap: 24px; align-items: start; }
        .card { border: 1px solid var(--line); border-radius: 18px; background: var(--surface); box-shadow: var(--shadow); }
        .config { position: sticky; top: 20px; padding: 24px; }
        .card-heading { margin: 0; font-size: 1.13rem; letter-spacing: -.02em; }
        .card-copy { margin: 7px 0 22px; color: var(--muted); font-size: .9rem; line-height: 1.5; }

        .field { margin-top: 18px; }
        label, legend { display: block; margin-bottom: 7px; font-size: .88rem; font-weight: 750; }
        .hint { display: block; margin-top: 6px; color: var(--muted); font-size: .78rem; line-height: 1.4; }
        input, select { width: 100%; min-height: 44px; padding: 9px 11px; border: 1px solid #cdd7d1; border-radius: 10px; color: var(--ink); background: white; transition: border-color .15s, box-shadow .15s; }
        input:focus, select:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(23, 107, 80, .13); }
        [aria-invalid="true"] { border-color: var(--danger); }
        .field-error { margin: 6px 0 0; color: var(--danger); font-size: .8rem; }
        .inline-fields { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }

        .error-summary { margin-bottom: 20px; padding: 15px 17px; border: 1px solid #f1b4af; border-radius: 12px; color: #7a271a; background: var(--danger-soft); }
        .error-summary strong { display: block; margin-bottom: 6px; }
        .error-summary ul { margin: 0; padding-left: 20px; }
        .error-summary a { color: inherit; }

        .primary { width: 100%; min-height: 48px; margin-top: 24px; border: 0; border-radius: 11px; color: white; background: var(--accent); font-weight: 800; cursor: pointer; box-shadow: 0 9px 20px rgba(23, 107, 80, .2); }
        .primary:hover { background: var(--accent-dark); }
        .primary:disabled { opacity: .7; cursor: wait; }

        .preview { min-height: 490px; overflow: hidden; }
        .preview-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 18px; padding: 22px 24px 18px; border-bottom: 1px solid var(--line); }
        .summary { margin: 6px 0 0; color: var(--muted); font-size: .86rem; }
        .badge { display: inline-block; padding: 3px 8px; border-radius: 999px; color: var(--accent-dark); background: var(--accent-soft); font-size: .75rem; font-weight: 750; }
        .table-wrap { max-width: 100%; overflow: auto; }
        table { width: 100%; border-collapse: collapse; font-size: .84rem; white-space: nowrap; }
        th { padding: 11px 14px; color: #52605a; background: var(--soft); font-size: .71rem; letter-spacing: .045em; text-align: left; text-transform: uppercase; }
        td { max-width: 280px; padding: 12px 14px; border-top: 1px solid #edf1ee; overflow: hidden; text-overflow: ellipsis; }
        tbody tr:hover { background: #fafcfb; }
        .empty { display: grid; min-height: 390px; place-items: center; padding: 42px; text-align: center; }
        .empty-icon { display: grid; width: 58px; height: 58px; margin: 0 auto 16px; place-items: center; border-radius: 17px; color: var(--accent); background: var(--accent-soft); }
        .empty h2 { margin: 0 0 8px; font-size: 1.1rem; }
        .empty p { max-width: 390px; margin: 0; color: var(--muted); font-size: .9rem; line-height: 1.55; }

        .export-bar { display: flex; align-items: center; justify-content: space-between; gap: 18px; padding: 16px 20px; border-top: 1px solid var(--line); background: #fbfcfb; }
        .export-note { margin: 0; color: var(--muted); font-size: .8rem; }
        .export-actions { display: flex; gap: 9px; }
        .export-actions form { margin: 0; }
        .secondary { min-height: 40px; padding: 8px 13px; border: 1px solid #c9d5ce; border-radius: 9px; color: var(--accent-dark); background: white; font-size: .84rem; font-weight: 750; cursor: pointer; }
        .secondary:hover { border-color: var(--accent); background: var(--accent-soft); }

        .sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0; }

        @media (max-width: 820px) {
            .page { width: min(100% - 22px, 680px); padding-top: 26px; }
            .masthead { display: block; }
            .status { margin-top: 18px; }
            .layout { grid-template-columns: 1fr; }
            .config { position: static; }
        }
        @media (max-width: 520px) {
            .page { width: calc(100% - 16px); }
            .privacy { padding: 13px; }
            .config, .preview-head { padding: 18px; }
            .inline-fields { grid-template-columns: 1fr; }
            .preview-head, .export-bar { align-items: stretch; flex-direction: column; }
            .export-actions { display: grid; grid-template-columns: 1fr 1fr; }
            .secondary { width: 100%; }
        }
        @media (prefers-reduced-motion: reduce) { * { scroll-behavior: auto !important; transition: none !important; } }
    </style>
</head>
<body>
@php
    $currentType = old('customer_type', $options['customer_type'] ?? request('customer_type', 'mixed'));
    $currentCount = old('count', $options['count'] ?? request('count', 100));
    $currentSeed = old('seed', $options['seed'] ?? request('seed', ''));
    $currentPrivatePercentage = old('private_percentage', $options['private_percentage'] ?? request('private_percentage', 60));
    $previewRecords = collect($records ?? [])->take(10)->map(function ($record) {
        if (is_object($record) && method_exists($record, 'toArray')) {
            return $record->toArray();
        }
        return (array) $record;
    });
    $columns = $previewRecords->flatMap(fn ($record) => array_keys($record))->unique()->values();
    $columnLabels = [
        'id' => 'ID', 'customer_type' => 'Type', 'type' => 'Type',
        'first_name' => 'Voornaam', 'infix' => 'Tussenvoegsel', 'last_name' => 'Achternaam',
        'full_name' => 'Naam', 'company_name' => 'Bedrijfsnaam', 'legal_form' => 'Rechtsvorm',
        'email' => 'E-mailadres', 'phone' => 'Telefoon', 'date_of_birth' => 'Geboortedatum',
        'street' => 'Straat', 'house_number' => 'Huisnr.', 'house_number_addition' => 'Toevoeging',
        'postal_code' => 'Postcode', 'city' => 'Plaats', 'country' => 'Land',
        'chamber_of_commerce_number' => 'KvK-nummer', 'kvk_number' => 'KvK-nummer',
        'vat_number' => 'Btw-nummer', 'contact_name' => 'Contactpersoon',
    ];
    $typeLabels = ['private' => 'Particulier', 'business' => 'Zakelijk', 'mixed' => 'Gemengd'];
@endphp

<main class="page">
    <header class="masthead">
        <div>
            <p class="eyebrow">Veilige testdata</p>
            <h1>Nepdata-generator</h1>
            <p class="intro">Maak in een paar seconden geloofwaardige Nederlandse klantgegevens voor ontwikkeling, demo’s en tests.</p>
        </div>
        <div class="status"><span class="status-dot" aria-hidden="true"></span> Klaar om te genereren</div>
    </header>

    <section class="privacy" aria-label="Privacyinformatie">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3 5 6v5c0 4.7 2.9 8.2 7 10 4.1-1.8 7-5.3 7-10V6l-7-3Z" stroke="currentColor" stroke-width="1.8"/><path d="m9 12 2 2 4-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <div>
            <strong>Volledig synthetisch, zonder echte klantdata</strong>
            <p>De gegevens zijn bedoeld voor testdoeleinden. Gebruik ze niet om echte personen of bedrijven te benaderen.</p>
        </div>
    </section>

    @if ($errors->any())
        <div class="error-summary" role="alert" tabindex="-1" id="error-summary">
            <strong>Controleer de volgende velden:</strong>
            <ul>
                @foreach ($errors->keys() as $field)
                    <li><a href="#{{ $field }}">{{ $errors->first($field) }}</a></li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="layout">
        <section class="card config" aria-labelledby="config-title">
            <h2 class="card-heading" id="config-title">Dataset instellen</h2>
            <p class="card-copy">Kies de samenstelling. Met dezelfde seed krijg je steeds exact dezelfde set.</p>

            <form method="POST" action="/preview" id="generator-form">
                @csrf
                <div class="field">
                    <label for="customer_type">Soort klant</label>
                    <select id="customer_type" name="customer_type" aria-invalid="{{ $errors->has('customer_type') ? 'true' : 'false' }}" @error('customer_type') aria-describedby="customer_type-error" @enderror>
                        <option value="private" @selected($currentType === 'private')>Particulier</option>
                        <option value="business" @selected($currentType === 'business')>Zakelijk</option>
                        <option value="mixed" @selected($currentType === 'mixed')>Particulier en zakelijk</option>
                    </select>
                    @error('customer_type')<p class="field-error" id="customer_type-error">{{ $message }}</p>@enderror
                </div>

                <div class="inline-fields">
                    <div class="field">
                        <label for="count">Aantal records</label>
                        <input id="count" name="count" type="number" min="1" max="1000" step="1" inputmode="numeric" value="{{ $currentCount }}" required aria-invalid="{{ $errors->has('count') ? 'true' : 'false' }}" @error('count') aria-describedby="count-error" @enderror>
                        <span class="hint">1 t/m 1.000</span>
                        @error('count')<p class="field-error" id="count-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="field">
                        <label for="seed">Seed <span class="sr-only">(optioneel)</span></label>
                        <input id="seed" name="seed" type="number" step="1" inputmode="numeric" value="{{ $currentSeed }}" placeholder="Optioneel" aria-invalid="{{ $errors->has('seed') ? 'true' : 'false' }}" @error('seed') aria-describedby="seed-error" @enderror>
                        <span class="hint">Voor herhaalbaarheid</span>
                        @error('seed')<p class="field-error" id="seed-error">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="field" id="percentage-field" @if($currentType !== 'mixed') hidden @endif>
                    <label for="private_percentage">Aandeel particulieren</label>
                    <input id="private_percentage" name="private_percentage" type="number" min="0" max="100" step="1" inputmode="numeric" value="{{ $currentPrivatePercentage }}" aria-invalid="{{ $errors->has('private_percentage') ? 'true' : 'false' }}" @error('private_percentage') aria-describedby="private_percentage-error" @enderror>
                    <span class="hint">Percentage binnen een gemengde dataset (0–100).</span>
                    @error('private_percentage')<p class="field-error" id="private_percentage-error">{{ $message }}</p>@enderror
                </div>

                <button type="submit" class="primary" id="generate-button">Voorbeeld genereren</button>
            </form>
        </section>

        <section class="card preview" aria-labelledby="preview-title" aria-live="polite">
            @if ($previewRecords->isNotEmpty())
                <div class="preview-head">
                    <div>
                        <h2 class="card-heading" id="preview-title">Voorbeeld</h2>
                        <p class="summary">
                            <strong>{{ number_format((int) $currentCount, 0, ',', '.') }}</strong> records
                            · {{ $typeLabels[$currentType] ?? ucfirst((string) $currentType) }}
                            @if(filled($currentSeed)) · seed {{ $currentSeed }} @endif
                        </p>
                    </div>
                    <span class="badge">Eerste {{ $previewRecords->count() }} zichtbaar</span>
                </div>

                <div class="table-wrap" tabindex="0" aria-label="Voorbeeldgegevens, horizontaal scrollbaar">
                    <table>
                        <caption class="sr-only">Voorbeeld van de gegenereerde klantgegevens</caption>
                        <thead><tr>
                            @foreach ($columns as $column)
                                <th scope="col">{{ $columnLabels[$column] ?? str($column)->replace('_', ' ')->title() }}</th>
                            @endforeach
                        </tr></thead>
                        <tbody>
                            @foreach ($previewRecords as $record)
                                <tr>
                                    @foreach ($columns as $column)
                                        @php($value = $record[$column] ?? null)
                                        <td title="{{ is_scalar($value) ? $value : '' }}">
                                            @if(in_array($column, ['customer_type', 'type'], true))
                                                <span class="badge">{{ $typeLabels[$value] ?? ucfirst((string) $value) }}</span>
                                            @elseif(is_bool($value))
                                                {{ $value ? 'Ja' : 'Nee' }}
                                            @elseif(is_array($value) || is_object($value))
                                                {{ json_encode($value, JSON_UNESCAPED_UNICODE) }}
                                            @else
                                                {{ filled($value) ? $value : '—' }}
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="export-bar">
                    <p class="export-note">De export bevat alle {{ number_format((int) $currentCount, 0, ',', '.') }} records.</p>
                    <div class="export-actions" aria-label="Exportopties">
                        @foreach (['csv' => 'CSV downloaden', 'json' => 'JSON downloaden'] as $format => $label)
                            <form method="POST" action="/export/{{ $format }}">
                                @csrf
                                <input type="hidden" name="customer_type" value="{{ $currentType }}">
                                <input type="hidden" name="count" value="{{ $currentCount }}">
                                <input type="hidden" name="seed" value="{{ $currentSeed }}">
                                <input type="hidden" name="private_percentage" value="{{ $currentPrivatePercentage }}">
                                <button type="submit" class="secondary">{{ $label }}</button>
                            </form>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="empty">
                    <div>
                        <div class="empty-icon" aria-hidden="true">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none"><path d="M7 3h10a2 2 0 0 1 2 2v14l-3-2-4 2-4-2-3 2V5a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.7"/><path d="M8 8h8M8 12h5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                        </div>
                        <h2 id="preview-title">Nog geen gegevens gegenereerd</h2>
                        <p>Stel links je dataset samen en genereer een voorbeeld. Daarna kun je dezelfde gegevens downloaden als CSV of JSON.</p>
                    </div>
                </div>
            @endif
        </section>
    </div>
</main>

<script>
    (() => {
        const type = document.getElementById('customer_type');
        const percentageField = document.getElementById('percentage-field');
        const form = document.getElementById('generator-form');
        const button = document.getElementById('generate-button');

        const syncPercentage = () => {
            percentageField.hidden = type.value !== 'mixed';
        };

        type.addEventListener('change', syncPercentage);
        form.addEventListener('submit', () => {
            button.disabled = true;
            button.textContent = 'Genereren…';
        });

        document.getElementById('error-summary')?.focus();
        syncPercentage();
    })();
</script>
</body>
</html>
