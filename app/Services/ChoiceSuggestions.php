<?php

namespace App\Services;

use Illuminate\Support\Str;

class ChoiceSuggestions
{
    public function forName(string $name): ?array
    {
        $normalised = ' '.$this->normalise($name).' ';
        $best = null;
        $bestLength = 0;

        foreach (config('choice_suggestions') as $key => $topic) {
            foreach ($topic['aliases'] as $alias) {
                $alias = $this->normalise($alias);
                // Prefer specific phrases, e.g. "invoice status" over "status".
                if (strlen($alias) > $bestLength && str_contains($normalised, ' '.$alias.' ')) {
                    $bestLength = strlen($alias);
                    $best = ['topic' => $key, 'label' => $topic['label'], 'values' => $topic['values'], 'source' => 'local'];
                }
            }
        }

        return $best;
    }

    private function normalise(string $value): string
    {
        return trim(preg_replace('/[^a-z0-9]+/', ' ', Str::lower(Str::ascii($value))));
    }
}
