<?php

namespace Tests\Unit;

use App\Services\DatasetCatalog;
use App\Services\DatasetGenerator;
use Tests\TestCase;

class DatasetGeneratorTest extends TestCase
{
    public function test_every_preset_has_repeatable_data_and_unique_ordered_headers(): void
    {
        $generator = $this->app->make(DatasetGenerator::class);
        foreach ($this->app->make(DatasetCatalog::class)->presets() as $key => $preset) {
            $first = $generator->generate($preset['columns'], 40, 12345, $key);
            $this->assertSame($first, $generator->generate($preset['columns'], 40, 12345, $key));
            $this->assertCount(40, $first);
            $this->assertSame(array_column($preset['columns'], 'name'), array_keys($first[0]));
            $this->assertCount(40, array_unique(array_column($first, 'ID')));
        }
    }

    public function test_names_emails_and_company_contacts_stay_related(): void
    {
        $catalog = $this->app->make(DatasetCatalog::class);
        $generator = $this->app->make(DatasetGenerator::class);
        $people = $generator->generate($catalog->presets()['people']['columns'], 100, 42);
        foreach ($people as $person) {
            $this->assertSame(implode(' ', array_filter([$person['Voornaam'], $person['Tussenvoegsel'], $person['Achternaam']])), $person['Volledige naam']);
            $this->assertStringStartsWith(strtolower($person['Voornaam']).'.', $person['E-mail']);
            $this->assertStringEndsWith('.test', $person['E-mail']);
        }
        $this->assertCount(100, array_unique(array_column($people, 'E-mail')));

        $companies = $generator->generate($catalog->presets()['companies']['columns'], 100, 42, 'companies');
        foreach ($companies as $company) {
            $this->assertSame(substr($company['Website'], 8), explode('@', $company['E-mail'])[1]);
            $this->assertStringStartsWith(strtolower(explode(' ', $company['Contactpersoon'])[0]).'.', $company['E-mail']);
        }
    }

    public function test_car_and_computer_attributes_stay_related(): void
    {
        $catalog = $this->app->make(DatasetCatalog::class);
        $generator = $this->app->make(DatasetGenerator::class);
        $cars = $generator->generate($catalog->presets()['cars']['columns'], 200, 42, 'cars');
        foreach ($cars as $car) {
            if ($car['Merk'] === 'Tesla') {
                $this->assertContains($car['Model'], ['Model 3', 'Model Y']);
                $this->assertSame('Elektrisch', $car['Brandstof']);
                $this->assertGreaterThanOrEqual($car['Model'] === 'Model Y' ? 2021 : 2019, $car['Bouwjaar']);
            }
        }

        $computers = $generator->generate($catalog->presets()['computers']['columns'], 200, 42, 'computers');
        foreach ($computers as $computer) {
            if ($computer['Merk'] === 'Apple') {
                $this->assertStringStartsWith('Apple M', $computer['Processor']);
                $this->assertSame('macOS', $computer['Besturingssysteem']);
            } else {
                $this->assertSame('Windows 11', $computer['Besturingssysteem']);
            }
        }
        $this->assertCount(200, array_unique(array_column($computers, 'Serienummer')));
    }

    public function test_all_catalog_types_can_be_combined_and_reordered_without_changing_values(): void
    {
        $columns = [];
        foreach ($this->app->make(DatasetCatalog::class)->types() as $key => $type) {
            $columns[] = ['name' => $key, 'type' => $key, ...($key === 'choice' ? ['choices' => "0\nOptie twee"] : [])];
        }
        $generator = $this->app->make(DatasetGenerator::class);
        // The catalog is larger than the per-file limit; exercise it in batches.
        foreach (array_chunk($columns, 40) as $batch) {
            $first = $generator->generate($batch, 40, 17, 'custom');
            $reordered = $generator->generate(array_reverse($batch), 40, 17, 'custom');
            foreach ($first as $index => $record) {
                foreach ($record as $name => $value) {
                    $this->assertSame($value, $reordered[$index][$name]);
                }
            }
        }
    }

    public function test_custom_ranges_include_negative_numbers_exact_decimals_dates_and_zero_choices(): void
    {
        $columns = [
            ['name' => 'Getal', 'type' => 'integer', 'min' => -10, 'max' => -5],
            ['name' => 'Bedrag', 'type' => 'decimal', 'min' => 0.07, 'max' => 0.07, 'decimals' => 2],
            ['name' => 'Datum', 'type' => 'date', 'from' => '2024-02-29', 'to' => '2024-02-29'],
            ['name' => 'Keuze', 'type' => 'choice', 'choices' => "\n0\n"],
        ];
        $records = $this->app->make(DatasetGenerator::class)->generate($columns, 40, 42, 'custom');
        foreach ($records as $record) {
            $this->assertGreaterThanOrEqual(-10, $record['Getal']);
            $this->assertLessThanOrEqual(-5, $record['Getal']);
            $this->assertEquals(0.07, $record['Bedrag']);
            $this->assertSame('2024-02-29', $record['Datum']);
            $this->assertSame('0', $record['Keuze']);
        }
    }
}
