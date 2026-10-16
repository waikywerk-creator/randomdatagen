<?php

namespace App\Http\Requests;

use App\Services\DatasetCatalog;
use App\Services\Support\NumericRange;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class GenerateDatasetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $columns = $this->input('columns');
        if (! is_array($columns)) {
            return;
        }

        $this->merge(['columns' => array_map(static function ($column) {
            if (is_array($column) && isset($column['name']) && is_string($column['name'])) {
                $column['name'] = trim($column['name']);
            }

            return $column;
        }, $columns)]);
    }

    public function rules(DatasetCatalog $catalog): array
    {
        return [
            'dataset' => ['required', Rule::in(array_keys($catalog->presets()))],
            'count' => ['required', 'integer', 'min:1', 'max:'.config('datasets.max_rows')],
            'seed' => ['nullable', 'integer', 'between:0,2147483647'],
            'format' => ['required', Rule::in(['csv', 'xlsx', 'json'])],
            'columns' => ['required', 'array', 'list', 'min:1', 'max:'.config('datasets.max_columns')],
            'columns.*' => ['required', 'array:name,type,min,max,decimals,from,to,choices,value'],
            'columns.*.name' => ['required', 'string', 'max:80', 'not_regex:/[\x00-\x1F\x7F]/u'],
            'columns.*.type' => ['required', Rule::in(array_keys($catalog->types()))],
            'columns.*.min' => ['nullable', 'numeric', 'between:-1000000000,1000000000'],
            'columns.*.max' => ['nullable', 'numeric', 'between:-1000000000,1000000000'],
            'columns.*.decimals' => ['nullable', 'integer', 'between:0,6'],
            'columns.*.from' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:1900-01-01', 'before_or_equal:2100-12-31'],
            'columns.*.to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:1900-01-01', 'before_or_equal:2100-12-31'],
            'columns.*.choices' => ['nullable', 'string', 'max:12000', 'not_regex:/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u'],
            'columns.*.value' => ['nullable', 'string', 'max:500', 'not_regex:/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            // Cross-field validation only runs on structurally valid input.
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $names = [];
            foreach ($this->input('columns') as $index => $column) {
                $name = mb_strtolower($column['name']);
                if (in_array($name, $names, true)) {
                    $validator->errors()->add("columns.$index.name", 'Elke kolom moet een unieke naam hebben.');
                }
                $names[] = $name;

                if (in_array($column['type'], ['integer', 'decimal', 'price', 'percentage'], true)) {
                    $min = $column['min'] ?? 0;
                    $max = $column['max'] ?? ($column['type'] === 'percentage' ? 100 : 1000);
                    if ($min > $max) {
                        $validator->errors()->add("columns.$index.max", 'Het maximum moet minimaal gelijk zijn aan het minimum.');
                    }
                    if ($column['type'] === 'integer' && (floor((float) $min) != $min || floor((float) $max) != $max)) {
                        $validator->errors()->add("columns.$index.min", 'Gebruik hele getallen voor de grenzen van een geheel getal.');
                    }
                    [$scaledMinimum, $scaledMaximum] = NumericRange::forColumn($column);
                    if ($scaledMinimum > $scaledMaximum) {
                        $validator->errors()->add("columns.$index.decimals", 'Binnen dit bereik past geen waarde met het gekozen aantal decimalen.');
                    }
                }

                if (in_array($column['type'], ['date', 'datetime'], true)
                    && ($column['from'] ?? '2020-01-01') > ($column['to'] ?? '2030-12-31')) {
                    $validator->errors()->add("columns.$index.to", 'De einddatum moet op of na de begindatum liggen.');
                }

                if ($column['type'] === 'choice') {
                    $choices = $this->choices($column['choices'] ?? '');
                    if (count($choices) < 1 || count($choices) > 100 || collect($choices)->contains(fn ($value) => mb_strlen($value) > 120)) {
                        $validator->errors()->add("columns.$index.choices", 'Vul 1 t/m 100 keuzes in, één per regel, van maximaal 120 tekens.');
                    }
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute is verplicht.',
            'string' => ':attribute moet tekst zijn.',
            'integer' => ':attribute moet een heel getal zijn.',
            'numeric' => ':attribute moet een getal zijn.',
            'in' => 'Kies een geldige waarde voor :attribute.',
            'between' => ':attribute valt buiten het toegestane bereik.',
            'count.min' => 'Genereer minimaal één rij.',
            'count.max' => 'Je kunt per keer maximaal 1.000 rijen genereren.',
            'columns.required' => 'Voeg minimaal één kolom toe.',
            'columns.min' => 'Voeg minimaal één kolom toe.',
            'columns.max' => 'Je kunt maximaal 50 kolommen gebruiken.',
            'columns.*.name.max' => 'Een kolomnaam mag maximaal 80 tekens bevatten.',
            'not_regex' => ':attribute bevat ongeldige besturingstekens.',
            'date_format' => 'Gebruik een geldige datum voor :attribute.',
        ];
    }

    public function attributes(): array
    {
        return [
            'dataset' => 'Het onderwerp', 'count' => 'Het aantal rijen', 'seed' => 'De seed',
            'format' => 'Het bestandsformaat', 'columns.*.name' => 'De kolomnaam',
            'columns.*.type' => 'Het kolomtype', 'columns.*.min' => 'Het minimum',
            'columns.*.max' => 'Het maximum', 'columns.*.decimals' => 'Het aantal decimalen',
            'columns.*.from' => 'De begindatum', 'columns.*.to' => 'De einddatum',
            'columns.*.choices' => 'De keuzelijst', 'columns.*.value' => 'De tekst',
        ];
    }

    public function options(): array
    {
        $options = $this->validated();
        $options['count'] = (int) $options['count'];
        $options['seed'] = isset($options['seed']) ? (int) $options['seed'] : random_int(0, 2147483647);

        return $options;
    }

    private function choices(string $value): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R/u', $value)), fn ($value) => $value !== ''));
    }
}
