<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>Nepdata-generator — stel je eigen tabel samen</title>
    <link rel="stylesheet" href="{{ asset('dataset-generator.css') }}">
    <script src="{{ asset('dataset-generator.js') }}" defer></script>
</head>
<body>
@php
    $current = array_replace($options, old());
    $currentColumns = is_array($current['columns'] ?? null) ? $current['columns'] : $options['columns'];
    $previewRows = array_slice($records, 0, 10);
    $previewColumns = $options['columns'];
    $currentDataset = is_string($current['dataset'] ?? null) ? $current['dataset'] : 'people';
@endphp
<main class="page">
    <header class="masthead">
        <div>
            <p class="eyebrow">Jouw tabel. Jouw testdata.</p>
            <h1>Van idee naar dataset.</h1>
            <p class="intro">Personen, auto’s, computers of je eigen combinatie. Stel je kolommen samen en download met één klik een Excel- of CSV-bestand.</p>
        </div>
        <span class="badge">Volledig synthetisch</span>
    </header>

    @if ($errors->any())
        <div class="error-summary" role="alert" tabindex="-1" id="error-summary">
            <strong>Controleer je instellingen:</strong>
            <ul>
                @foreach ($errors->all() as $message)<li>{{ $message }}</li>@endforeach
            </ul>
        </div>
    @endif
    <noscript><p class="notice">Schakel JavaScript in om onderwerpen te wisselen en kolommen toe te voegen. De getoonde tabel kun je ook zonder JavaScript genereren.</p></noscript>

    <form method="POST" action="{{ route('datasets.download') }}" id="dataset-form">
        @csrf
        <div class="layout">
            <aside class="card config" aria-labelledby="settings-title">
                <p class="step">01 / Instellingen</p>
                <h2 id="settings-title">Wat wil je maken?</h2>
                <div class="field">
                    <label for="dataset">Onderwerp</label>
                    <select id="dataset" name="dataset">
                        @foreach ($presets as $key => $preset)
                            <option value="{{ $key }}" @selected(($current['dataset'] ?? '') === $key)>{{ $preset['label'] }}</option>
                        @endforeach
                    </select>
                    <p class="hint" id="preset-description">{{ $presets[$currentDataset]['description'] ?? '' }}</p>
                </div>
                <div class="field">
                    <label for="count">Aantal rijen</label>
                    <input id="count" name="count" type="number" min="1" max="{{ config('datasets.max_rows') }}" step="1" value="{{ is_scalar($current['count']) ? $current['count'] : 100 }}" required>
                    <span class="hint">1 t/m {{ number_format(config('datasets.max_rows'), 0, ',', '.') }} rijen per bestand.</span>
                </div>
                <div class="field">
                    <label for="format">Bestandsformaat</label>
                    <select id="format" name="format">
                        @foreach (['xlsx' => 'Excel (.xlsx)', 'csv' => 'CSV (.csv)', 'json' => 'JSON (.json)'] as $key => $label)
                            <option value="{{ $key }}" @selected(($current['format'] ?? '') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <details class="advanced" @if(filled($current['seed'] ?? null)) open @endif>
                    <summary>Herhaalbare data</summary>
                    <div class="field">
                        <label for="seed">Seed (optioneel)</label>
                        <input id="seed" name="seed" type="number" min="0" max="2147483647" step="1" value="{{ is_scalar($current['seed'] ?? null) ? $current['seed'] : '' }}" placeholder="Automatisch">
                        <span class="hint">Dezelfde seed en instellingen geven dezelfde data. Wis de seed voor een nieuwe dataset.</span>
                    </div>
                </details>
                <button class="primary" type="submit">Genereren en downloaden <span aria-hidden="true">↓</span></button>
                <button class="secondary preview-button" type="submit" formaction="{{ route('datasets.preview') }}">Eerst een voorbeeld bekijken</button>
                <p class="local-note">De app bewaart je gegenereerde dataset niet. Contactgegevens zijn fictief en bedoeld voor tests.</p>
            </aside>

            <section class="card editor" aria-labelledby="columns-title">
                <div class="section-head">
                    <div>
                        <p class="step">02 / Kolommen</p>
                        <h2 id="columns-title">Maak de tabel die je nodig hebt.</h2>
                        <p class="hint">Pas namen en typen aan. Combineer gerust kolommen uit verschillende onderwerpen.</p>
                        <p class="hint">Laat waarden voorstellen bij een naam zoals Studie, Beroep of Sport. Kies daarna een ingebouwde lijst of AI met je eigen API-sleutel.</p>
                    </div>
                    <span class="counter" id="column-count" aria-live="polite">{{ count($currentColumns) }} / {{ config('datasets.max_columns') }}</span>
                </div>
                <div id="columns">
                    @foreach ($currentColumns as $index => $column)
                        @include('partials.dataset-column', ['index' => $index, 'column' => is_array($column) ? $column : []])
                    @endforeach
                </div>
                <div class="editor-footer">
                    <button class="secondary" type="button" id="add-column">+ Kolom toevoegen</button>
                    <p class="hint" id="editor-status" role="status"></p>
                </div>
            </section>
        </div>
    </form>

    <section class="card preview" aria-labelledby="preview-title">
        <div class="section-head">
            <div>
                <p class="step">Voorbeeld</p>
                <h2 id="preview-title">{{ $previewRows ? 'Zo ziet je dataset eruit.' : 'Je volgende dataset begint hierboven.' }}</h2>
                <p class="hint" id="preview-status" aria-live="polite">
                    @if ($previewRows)
                        Eerste {{ count($previewRows) }} van {{ count($records) }} rijen · {{ count($previewColumns) }} kolommen · seed {{ $options['seed'] }}.
                    @else
                        Je kunt direct downloaden of eerst de eerste tien rijen bekijken.
                    @endif
                </p>
            </div>
        </div>
        @if ($previewRows)
            <div class="table-wrap" tabindex="0" aria-label="Voorbeeldgegevens, horizontaal scrollbaar">
                <table>
                    <caption class="sr-only">Voorbeeld van de synthetische dataset</caption>
                    <thead><tr>@foreach ($previewColumns as $column)<th scope="col">{{ $column['name'] }}</th>@endforeach</tr></thead>
                    <tbody>
                        @foreach ($previewRows as $record)
                            <tr>@foreach ($previewColumns as $column)
                                @php($value = $record[$column['name']] ?? null)
                                <td>{{ is_bool($value) ? ($value ? 'Ja' : 'Nee') : ($value === null || $value === '' ? '—' : $value) }}</td>
                            @endforeach</tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
    <footer class="page-footer">Nederlandse testdata · Excel, CSV en JSON · Geen account nodig</footer>
</main>

<dialog id="suggestion-dialog" aria-labelledby="suggestion-dialog-title" aria-describedby="suggestion-dialog-description">
    <form id="suggestion-options-form" autocomplete="off">
        <div class="dialog-heading">
            <div>
                <p class="step">Waarden voorstellen</p>
                <h2 id="suggestion-dialog-title">Kies je AI-opties</h2>
            </div>
            <button type="button" class="icon-button" id="suggestion-dialog-close" aria-label="AI-opties sluiten">×</button>
        </div>
        <p class="hint" id="suggestion-dialog-description">Voor de kolom <strong id="suggestion-column-name"></strong>. Je bekijkt het voorstel voordat je het toepast.</p>
        <div class="field">
            <label for="suggestion-provider">Bron van de waarden</label>
            <select id="suggestion-provider">
                <option value="local">Zonder AI — ingebouwde lijsten</option>
                <option value="openai">OpenAI — eigen API-sleutel</option>
            </select>
        </div>
        <p class="hint" id="suggestion-local-help">Werkt zonder sleutel met bekende onderwerpen, zoals Studie, Beroep, Sport en Kleur.</p>
        <fieldset id="suggestion-ai-fields" class="ai-fields" hidden disabled>
            <legend class="sr-only">OpenAI-instellingen</legend>
            <div class="field">
                <label for="suggestion-api-key">OpenAI API-sleutel</label>
                <input id="suggestion-api-key" type="password" minlength="16" maxlength="512" autocomplete="off" autocapitalize="off" spellcheck="false" placeholder="Plak je API-sleutel" required>
                <p class="hint">Alleen gebruikt voor dit verzoek. De sleutel wordt gewist wanneer dit venster sluit.</p>
            </div>
            <div class="dialog-settings">
                <div class="field">
                    <label for="suggestion-model">Model</label>
                    <input id="suggestion-model" value="{{ \App\Services\OpenAiChoiceSuggestions::DEFAULT_MODEL }}" maxlength="100" pattern="[A-Za-z0-9][A-Za-z0-9._:\-]*" spellcheck="false" required>
                </div>
                <div class="field">
                    <label for="suggestion-count">Aantal waarden</label>
                    <input id="suggestion-count" type="number" min="1" max="100" step="1" value="20" required>
                </div>
            </div>
            <div class="field">
                <label for="suggestion-context">Extra toelichting (optioneel)</label>
                <textarea id="suggestion-context" rows="2" maxlength="300" placeholder="Bijvoorbeeld: Nederlandse hbo-studies in de techniek"></textarea>
            </div>
            <p class="ai-notice">De kolomnaam en je toelichting worden naar OpenAI gestuurd. Dit verzoek gebruikt je eigen OpenAI API-tegoed. Je tabelgegevens worden niet meegestuurd.</p>
        </fieldset>
        <div class="dialog-footer">
            <button type="button" class="secondary" id="suggestion-dialog-cancel">Annuleren</button>
            <button type="submit" class="primary">Voorstel maken</button>
        </div>
    </form>
</dialog>

<template id="column-template">
    @include('partials.dataset-column', ['index' => '__INDEX__', 'column' => ['name' => '', 'type' => 'text']])
</template>
<script type="application/json" id="dataset-catalog">{!! \Illuminate\Support\Js::encode(['presets' => $presets, 'types' => $types, 'maxColumns' => config('datasets.max_columns'), 'hasPreview' => (bool) $previewRows, 'suggestUrl' => route('datasets.choices.suggest')]) !!}</script>
</body>
</html>
