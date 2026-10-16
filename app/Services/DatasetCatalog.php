<?php

namespace App\Services;

class DatasetCatalog
{
    public function types(): array
    {
        return config('datasets.types');
    }

    public function presets(): array
    {
        return config('datasets.presets');
    }

    public function defaults(): array
    {
        return [
            'dataset' => 'people',
            'count' => 100,
            'seed' => null,
            'format' => 'xlsx',
            'columns' => $this->presets()['people']['columns'],
        ];
    }
}
