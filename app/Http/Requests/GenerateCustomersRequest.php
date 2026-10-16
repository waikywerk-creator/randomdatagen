<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateCustomersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'customer_type' => ['required', Rule::in(['private', 'business', 'mixed'])],
            'count' => ['required', 'integer', 'min:1', 'max:1000'],
            'seed' => ['nullable', 'integer', 'min:0', 'max:2147483647'],
            'private_percentage' => ['required_if:customer_type,mixed', 'nullable', 'integer', 'between:0,100'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'customer_type.required' => 'Kies welk soort klanten je wilt genereren.',
            'customer_type.in' => 'Het gekozen klanttype is niet geldig.',
            'count.required' => 'Vul het aantal klanten in.',
            'count.integer' => 'Het aantal moet een heel getal zijn.',
            'count.min' => 'Genereer minimaal één klant.',
            'count.max' => 'Je kunt per keer maximaal 1.000 klanten genereren.',
            'seed.integer' => 'De seed moet een heel getal zijn.',
            'seed.min' => 'De seed mag niet negatief zijn.',
            'private_percentage.required_if' => 'Vul het aandeel particuliere klanten in.',
            'private_percentage.between' => 'Het aandeel particulier moet tussen 0 en 100 liggen.',
        ];
    }

    /** @return array{customer_type:string,count:int,seed:int|null,private_percentage:int} */
    public function options(): array
    {
        $validated = $this->validated();

        return [
            'customer_type' => (string) $validated['customer_type'],
            'count' => (int) $validated['count'],
            'seed' => isset($validated['seed']) && $validated['seed'] !== '' ? (int) $validated['seed'] : null,
            'private_percentage' => (int) ($validated['private_percentage'] ?? 60),
        ];
    }
}
