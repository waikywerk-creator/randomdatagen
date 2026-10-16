(() => {
    'use strict';
    const catalog = JSON.parse(document.getElementById('dataset-catalog').textContent);
    const form = document.getElementById('dataset-form');
    const columns = document.getElementById('columns');
    const preset = document.getElementById('dataset');
    const addButton = document.getElementById('add-column');
    const status = document.getElementById('editor-status');
    const numericTypes = ['integer', 'decimal', 'price', 'percentage'];
    const suggestionRequests = new WeakMap();
    const suggestionDialog = document.getElementById('suggestion-dialog');
    const suggestionOptions = document.getElementById('suggestion-options-form');
    const suggestionProvider = document.getElementById('suggestion-provider');
    const apiKeyInput = document.getElementById('suggestion-api-key');
    let suggestionTarget = null;

    function updateAiFields() {
        const useAi = suggestionProvider.value === 'openai';
        const fields = document.getElementById('suggestion-ai-fields');
        fields.hidden = !useAi;
        fields.disabled = !useAi;
        document.getElementById('suggestion-local-help').hidden = useAi;
        if (!useAi) apiKeyInput.value = '';
    }

    function openSuggestionOptions(row, button) {
        const nameInput = row.querySelector('[data-property="name"]');
        const name = nameInput.value.trim();
        if (!name) {
            suggestionStatus(row, 'Vul eerst een kolomnaam in, bijvoorbeeld Studie.', true);
            nameInput.focus();
            return;
        }
        suggestionOptions.reset();
        updateAiFields();
        suggestionTarget = { row, button };
        document.getElementById('suggestion-column-name').textContent = name;
        suggestionDialog.showModal();
    }

    suggestionProvider.addEventListener('change', updateAiFields);
    document.getElementById('suggestion-dialog-cancel').addEventListener('click', () => suggestionDialog.close());
    document.getElementById('suggestion-dialog-close').addEventListener('click', () => suggestionDialog.close());
    suggestionDialog.addEventListener('close', () => {
        apiKeyInput.value = '';
        suggestionOptions.reset();
        suggestionTarget = null;
    });
    suggestionOptions.addEventListener('submit', event => {
        event.preventDefault();
        const target = suggestionTarget;
        if (!target?.row.isConnected) {
            suggestionDialog.close();
            return;
        }
        const options = { provider: suggestionProvider.value };
        if (options.provider === 'openai') {
            options.api_key = apiKeyInput.value.trim();
            options.model = document.getElementById('suggestion-model').value.trim();
            options.count = Number(document.getElementById('suggestion-count').value);
            options.context = document.getElementById('suggestion-context').value.trim();
        }
        apiKeyInput.value = '';
        suggestionDialog.close();
        suggestChoices(target.row, target.button, options);
    });

    function updateOptions(row) {
        const type = row.querySelector('[data-property="type"]').value;
        const visible = {
            numeric: numericTypes.includes(type),
            date: ['date', 'datetime'].includes(type),
            choice: type === 'choice',
            text: type === 'text',
        };
        row.querySelectorAll('[data-options]').forEach(group => {
            group.hidden = !visible[group.dataset.options];
            group.querySelectorAll('input, select, textarea').forEach(input => {
                input.disabled = group.hidden;
                input.required = !group.hidden && input.dataset.property === 'choices';
            });
        });
        row.querySelector('[data-decimals]').hidden = type === 'integer';
        row.querySelector('[data-property="decimals"]').disabled = !visible.numeric || type === 'integer';
        ['min', 'max'].forEach(key => {
            row.querySelector(`[data-property="${key}"]`).step = type === 'integer' ? '1' : 'any';
        });
    }

    function reindex() {
        const rows = [...columns.children];
        rows.forEach((row, index) => {
            row.querySelectorAll('[data-number]').forEach(label => { label.textContent = index + 1; });
            row.querySelectorAll('[data-property]').forEach(input => {
                input.name = `columns[${index}][${input.dataset.property}]`;
                input.id = `column-${index}-${input.dataset.property}`;
            });
            row.querySelectorAll('[data-label]').forEach(label => {
                label.htmlFor = `column-${index}-${label.dataset.label}`;
            });
            row.querySelector('[data-preview-values]').id = `column-${index}-suggestions`;
            row.querySelector('[data-preview-label]').htmlFor = `column-${index}-suggestions`;
            row.querySelector('[data-action="up"]').disabled = index === 0;
            row.querySelector('[data-action="down"]').disabled = index === rows.length - 1;
            row.querySelector('[data-action="remove"]').disabled = rows.length === 1;
            updateOptions(row);
        });
        addButton.disabled = rows.length >= catalog.maxColumns;
        document.getElementById('column-count').textContent = `${rows.length} / ${catalog.maxColumns}`;
    }

    function createColumn(column) {
        const row = document.getElementById('column-template').content.firstElementChild.cloneNode(true);
        row.querySelectorAll('[data-property]').forEach(input => {
            const key = input.dataset.property;
            const defaults = { min: 0, max: column.type === 'percentage' ? 100 : 1000, decimals: 2, from: '2020-01-01', to: '2030-12-31' };
            input.value = column[key] ?? defaults[key] ?? '';
        });
        return row;
    }

    function markChanged(event) {
        if (event?.target?.matches('[data-preview-values]')) return;
        if (catalog.hasPreview) {
            document.getElementById('preview-status').textContent = 'Instellingen gewijzigd. Bekijk een nieuw voorbeeld of download de aangepaste dataset.';
        }
    }

    function suggestionStatus(row, message, error = false) {
        const output = row.querySelector('[data-suggestion-status]');
        output.textContent = message;
        output.dataset.error = String(error);
    }

    function discardSuggestion(row) {
        suggestionRequests.get(row)?.abort();
        suggestionRequests.delete(row);
        const button = row.querySelector('[data-action="suggest"]');
        button.disabled = false;
        button.removeAttribute('aria-busy');
        row.querySelector('[data-suggestion-preview]').hidden = true;
        suggestionStatus(row, '');
    }

    async function suggestChoices(row, button, options = { provider: 'local' }) {
        discardSuggestion(row);
        const nameInput = row.querySelector('[data-property="name"]');
        const name = nameInput.value.trim();
        if (!name) {
            suggestionStatus(row, 'Vul eerst een kolomnaam in, bijvoorbeeld Studie.', true);
            nameInput.focus();
            return;
        }

        const controller = new AbortController();
        suggestionRequests.set(row, controller);
        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
        suggestionStatus(row, options.provider === 'openai' ? 'AI maakt een voorstel…' : 'Waarden zoeken…');

        try {
            const response = await fetch(catalog.suggestUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': form.querySelector('[name="_token"]').value,
                },
                body: JSON.stringify({ name, ...options }),
                signal: controller.signal,
            });
            const result = await response.json();
            if (controller.signal.aborted || !row.isConnected || nameInput.value.trim() !== name) return;
            if (!response.ok) {
                if (response.status === 419) {
                    throw new Error('Je sessie is verlopen. Vernieuw de pagina en probeer het opnieuw.');
                }
                const fallback = response.status === 429
                    ? 'Je hebt veel voorstellen opgevraagd. Probeer het over een minuut opnieuw.'
                    : 'Het voorstel kon niet worden opgehaald. Probeer het opnieuw.';
                throw new Error(Object.values(result.errors ?? {}).flat()[0]
                    || (response.status === 429 && result.source !== 'openai' ? fallback : result.message) || fallback);
            }
            if (!Array.isArray(result.values) || !result.values.length) {
                throw new Error('Er zijn geen passende waarden gevonden. Vul zelf een keuzelijst in.');
            }

            row.querySelector('[data-preview-values]').value = result.values.join('\n');
            const source = result.source === 'openai' ? 'voorgesteld door OpenAI. Controleer de waarden.' : 'voorbeelden uit de ingebouwde lijsten.';
            row.querySelector('[data-suggestion-description]').textContent = `${result.label} · ${result.values.length} ${source}`;
            row.querySelector('[data-suggestion-preview]').hidden = false;
            suggestionStatus(row, 'Voorstel klaar. Bekijk de waarden en pas ze toe.');
        } catch (error) {
            if (error.name !== 'AbortError' && row.isConnected && suggestionRequests.get(row) === controller) {
                suggestionStatus(row, error instanceof TypeError
                    ? 'De verbinding is onderbroken. Probeer het opnieuw.'
                    : error instanceof SyntaxError ? 'Er kwam geen geldig antwoord terug. Probeer het opnieuw.' : error.message, true);
            }
        } finally {
            if (suggestionRequests.get(row) === controller) {
                suggestionRequests.delete(row);
                button.disabled = false;
                button.removeAttribute('aria-busy');
            }
        }
    }

    preset.addEventListener('change', () => {
        const selected = catalog.presets[preset.value];
        [...columns.children].forEach(discardSuggestion);
        columns.replaceChildren(...selected.columns.map(createColumn));
        document.getElementById('preset-description').textContent = selected.description;
        status.textContent = `Standaardkolommen voor ${selected.label.toLowerCase()} geladen.`;
        reindex();
    });

    addButton.addEventListener('click', () => {
        if (columns.children.length >= catalog.maxColumns) return;
        const names = [...columns.querySelectorAll('[data-property="name"]')].map(input => input.value.toLowerCase());
        let number = columns.children.length + 1;
        while (names.includes(`kolom ${number}`)) number++;
        const row = createColumn({ name: `Kolom ${number}`, type: 'text' });
        columns.append(row);
        reindex();
        row.querySelector('[data-property="name"]').focus();
        status.textContent = 'Kolom toegevoegd.';
        markChanged();
    });

    columns.addEventListener('change', event => {
        if (event.target.dataset.property === 'type') {
            const row = event.target.closest('[data-column]');
            discardSuggestion(row);
            if (event.target.value === 'percentage') {
                row.querySelector('[data-property="min"]').value = 0;
                row.querySelector('[data-property="max"]').value = 100;
            }
            updateOptions(row);
        }
    });

    columns.addEventListener('input', event => {
        if (event.target.dataset.property === 'name') {
            discardSuggestion(event.target.closest('[data-column]'));
        }
    });

    columns.addEventListener('click', event => {
        const button = event.target.closest('[data-action]');
        if (!button || button.disabled) return;
        const row = button.closest('[data-column]');
        const action = button.dataset.action;
        if (action === 'suggest') {
            openSuggestionOptions(row, button);
            return;
        }
        if (action === 'discard-suggestions') {
            discardSuggestion(row);
            return;
        }
        if (action === 'apply-suggestions') {
            const choices = row.querySelector('[data-preview-values]').value.trim();
            if (!choices) {
                suggestionStatus(row, 'Vul minimaal één waarde in voordat je de keuzelijst toepast.', true);
                return;
            }
            row.querySelector('[data-property="type"]').value = 'choice';
            row.querySelector('[data-property="choices"]').value = choices;
            updateOptions(row);
            discardSuggestion(row);
            suggestionStatus(row, 'Keuzelijst toegepast. Je kunt de waarden hieronder aanpassen.');
            markChanged();
            return;
        }
        if (action === 'remove' && columns.children.length > 1) {
            const focusRow = row.nextElementSibling || row.previousElementSibling;
            discardSuggestion(row);
            row.remove();
            focusRow.querySelector('[data-property="name"]').focus();
            status.textContent = 'Kolom verwijderd.';
        } else if (action === 'up' && row.previousElementSibling) {
            columns.insertBefore(row, row.previousElementSibling);
            status.textContent = 'Kolom omhoog verplaatst.';
        } else if (action === 'down' && row.nextElementSibling) {
            columns.insertBefore(row.nextElementSibling, row);
            status.textContent = 'Kolom omlaag verplaatst.';
        }
        reindex();
        markChanged();
    });

    form.addEventListener('input', markChanged);
    form.addEventListener('change', markChanged);
    form.addEventListener('submit', () => {
        const seed = document.getElementById('seed');
        if (seed.value === '') {
            seed.value = crypto.getRandomValues(new Uint32Array(1))[0] % 2147483648;
        }
    });

    reindex();
    document.getElementById('error-summary')?.focus();
})();
