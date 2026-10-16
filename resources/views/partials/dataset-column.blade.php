@php
    $columnType = is_string($column['type'] ?? null) ? $column['type'] : 'text';
    $numeric = in_array($columnType, ['integer', 'decimal', 'price', 'percentage'], true);
    $dated = in_array($columnType, ['date', 'datetime'], true);
    $inputValue = static fn ($value, $default = '') => is_scalar($value) ? $value : $default;
@endphp
<fieldset class="column" data-column>
    <legend class="sr-only">Kolom <span data-number>{{ is_numeric($index) ? $index + 1 : '' }}</span></legend>
    <div class="column-main">
        <span class="column-number" data-number aria-hidden="true">{{ is_numeric($index) ? $index + 1 : '' }}</span>
        <div>
            <label for="column-{{ $index }}-name" data-label="name">Kolomnaam</label>
            <input id="column-{{ $index }}-name" name="columns[{{ $index }}][name]" data-property="name" value="{{ $inputValue($column['name'] ?? '') }}" maxlength="80" required>
        </div>
        <div>
            <label for="column-{{ $index }}-type" data-label="type">Soort waarde</label>
            <select id="column-{{ $index }}-type" name="columns[{{ $index }}][type]" data-property="type">
                @foreach (collect($types)->groupBy('group', true) as $group => $entries)
                    <optgroup label="{{ $group }}">
                        @foreach ($entries as $key => $entry)<option value="{{ $key }}" @selected($columnType === $key)>{{ $entry['label'] }}</option>@endforeach
                    </optgroup>
                @endforeach
            </select>
        </div>
        <div class="column-actions">
            <button type="button" data-action="up" class="icon-button" aria-label="Kolom omhoog" title="Omhoog">↑</button>
            <button type="button" data-action="down" class="icon-button" aria-label="Kolom omlaag" title="Omlaag">↓</button>
            <button type="button" data-action="remove" class="icon-button remove" aria-label="Kolom verwijderen" title="Verwijderen">×</button>
        </div>
    </div>
    <div class="suggestion-tools">
        <button type="button" class="suggest-button" data-action="suggest">Waarden voorstellen</button>
        <span class="hint suggestion-status" data-suggestion-status role="status"></span>
    </div>
    <div class="suggestion-preview" data-suggestion-preview hidden>
        <label for="column-{{ $index }}-suggestions" data-preview-label>Voorgestelde waarden</label>
        <p class="hint" data-suggestion-description></p>
        <textarea id="column-{{ $index }}-suggestions" rows="5" maxlength="12000" data-preview-values></textarea>
        <div class="suggestion-buttons">
            <button type="button" class="secondary" data-action="apply-suggestions">Gebruik deze keuzelijst</button>
            <button type="button" class="suggest-button" data-action="discard-suggestions">Sluiten</button>
        </div>
        <p class="hint">Je kunt de waarden aanpassen. Toepassen vervangt de keuzelijst van deze kolom.</p>
    </div>
    <div class="column-options numeric-options" data-options="numeric" @if(! $numeric) hidden @endif>
        @foreach (['min' => ['Minimum', 0], 'max' => ['Maximum', $columnType === 'percentage' ? 100 : 1000]] as $property => [$label, $default])
            <div>
                <label for="column-{{ $index }}-{{ $property }}" data-label="{{ $property }}">{{ $label }}</label>
                <input id="column-{{ $index }}-{{ $property }}" name="columns[{{ $index }}][{{ $property }}]" data-property="{{ $property }}" type="number" min="-1000000000" max="1000000000" step="any" value="{{ $inputValue($column[$property] ?? $default, $default) }}" @disabled(! $numeric)>
            </div>
        @endforeach
        <div data-decimals @if($columnType === 'integer') hidden @endif>
            <label for="column-{{ $index }}-decimals" data-label="decimals">Decimalen</label>
            <select id="column-{{ $index }}-decimals" name="columns[{{ $index }}][decimals]" data-property="decimals" @disabled(! $numeric || $columnType === 'integer')>
                @foreach (range(0, 6) as $digits)<option value="{{ $digits }}" @selected((int) ($column['decimals'] ?? 2) === $digits)>{{ $digits }}</option>@endforeach
            </select>
        </div>
    </div>
    <div class="column-options date-options" data-options="date" @if(! $dated) hidden @endif>
        @foreach (['from' => ['Van', '2020-01-01'], 'to' => ['Tot en met', '2030-12-31']] as $property => [$label, $default])
            <div>
                <label for="column-{{ $index }}-{{ $property }}" data-label="{{ $property }}">{{ $label }}</label>
                <input id="column-{{ $index }}-{{ $property }}" name="columns[{{ $index }}][{{ $property }}]" data-property="{{ $property }}" type="date" min="1900-01-01" max="2100-12-31" value="{{ $inputValue($column[$property] ?? $default, $default) }}" @disabled(! $dated)>
            </div>
        @endforeach
    </div>
    <div class="column-options" data-options="choice" @if($columnType !== 'choice') hidden @endif>
        <div class="wide">
            <label for="column-{{ $index }}-choices" data-label="choices">Mogelijke waarden (één per regel)</label>
            <textarea id="column-{{ $index }}-choices" name="columns[{{ $index }}][choices]" data-property="choices" rows="3" maxlength="12000" placeholder="Nieuw&#10;In behandeling&#10;Afgerond" @disabled($columnType !== 'choice')>{{ $inputValue($column['choices'] ?? '') }}</textarea>
        </div>
    </div>
    <div class="column-options" data-options="text" @if($columnType !== 'text') hidden @endif>
        <div class="wide">
            <label for="column-{{ $index }}-value" data-label="value">Vaste tekst (optioneel)</label>
            <input id="column-{{ $index }}-value" name="columns[{{ $index }}][value]" data-property="value" maxlength="500" value="{{ $inputValue($column['value'] ?? '') }}" placeholder="Leeg laten voor een willekeurige omschrijving" @disabled($columnType !== 'text')>
        </div>
    </div>
</fieldset>
