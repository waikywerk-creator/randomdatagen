<?php

namespace App\Services;

use App\Services\Support\DeterministicRandom;
use App\Services\Support\NumericRange;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Support\Str;
use InvalidArgumentException;

class DatasetGenerator
{
    // Related attributes are selected together, rather than from unrelated lists.
    private const CARS = [
        ['Toyota', 'Corolla', 2019, ['Benzine', 'Hybride']],
        ['Toyota', 'Yaris', 2020, ['Benzine', 'Hybride']],
        ['Volkswagen', 'Golf', 2013, ['Benzine', 'Diesel']],
        ['Volkswagen', 'ID.3', 2020, ['Elektrisch']],
        ['BMW', '320i', 2015, ['Benzine']],
        ['BMW', 'i4', 2022, ['Elektrisch']],
        ['Tesla', 'Model 3', 2019, ['Elektrisch']],
        ['Tesla', 'Model Y', 2021, ['Elektrisch']],
        ['Renault', 'Clio', 2015, ['Benzine']],
        ['Renault', 'Zoe', 2015, ['Elektrisch']],
        ['Ford', 'Focus', 2015, ['Benzine', 'Diesel']],
        ['Kia', 'Niro', 2017, ['Hybride']],
        ['Hyundai', 'IONIQ 5', 2021, ['Elektrisch']],
        ['Peugeot', '208', 2020, ['Benzine']],
        ['Volvo', 'XC40', 2018, ['Benzine']],
    ];

    private const COMPUTERS = [
        ['Apple', 'MacBook Air M2', 'Apple M2', [8, 16, 24], [256, 512, 1024], 'macOS'],
        ['Apple', 'MacBook Pro M3', 'Apple M3', [8, 16, 24], [512, 1024, 2048], 'macOS'],
        ['Dell', 'Latitude 5440', 'Intel Core i5-1345U', [8, 16, 32], [256, 512, 1024], 'Windows 11'],
        ['Dell', 'XPS 13 9315', 'Intel Core i7-1250U', [8, 16, 32], [256, 512, 1024], 'Windows 11'],
        ['HP', 'EliteBook 840 G10', 'Intel Core i5-1335U', [8, 16, 32], [256, 512, 1024], 'Windows 11'],
        ['Lenovo', 'ThinkPad T14 Gen 4 AMD', 'AMD Ryzen 7 PRO 7840U', [16, 32], [512, 1024], 'Windows 11'],
        ['Lenovo', 'ThinkCentre M70q Gen 3', 'Intel Core i5-12400T', [8, 16, 32], [256, 512, 1024], 'Windows 11'],
        ['ASUS', 'Zenbook 14 OLED UX3402', 'Intel Core i7-1260P', [16], [512, 1024], 'Windows 11'],
    ];

    public function __construct(private readonly CustomerGenerator $customers) {}

    /** @return list<array<string, bool|int|float|string|null>> */
    public function generate(array $columns, int $count, int $seed, string $dataset = 'people'): array
    {
        if ($count < 1 || $count > config('datasets.max_rows') || count($columns) < 1 || count($columns) > config('datasets.max_columns')) {
            throw new InvalidArgumentException('Invalid dataset dimensions.');
        }

        $people = $this->customers->generate($count, 'private', $this->seed($seed, 'people'));
        $companies = $this->customers->generate($count, 'business', $this->seed($seed, 'companies'));
        $records = [];

        foreach ($people as $index => $person) {
            $sequence = $index + 1;
            $rowSeed = $this->seed($seed, 'row:'.$sequence);
            $car = $this->car(new DeterministicRandom($this->seed($rowSeed, 'car')));
            $computer = $this->computer(new DeterministicRandom($this->seed($rowSeed, 'computer')));
            $record = [];

            foreach ($columns as $column) {
                // Stable per column: moving/removing other columns does not change its values.
                $random = new DeterministicRandom($this->seed($rowSeed, $column['type'].':'.$column['name']));
                $record[$column['name']] = $this->value($column, $sequence, $rowSeed, $random, $person, $companies[$index], $car, $computer, $dataset);
            }

            $records[] = $record;
        }

        return $records;
    }

    private function value(array $column, int $sequence, int $seed, DeterministicRandom $random, array $person, array $company, array $car, array $computer, string $dataset): bool|int|float|string|null
    {
        $address = $dataset === 'companies' ? $company : $person;
        $uuid = hash('sha256', $seed.':uuid:'.$column['name']);

        return match ($column['type']) {
            'id' => $sequence,
            'uuid' => substr($uuid, 0, 8).'-'.substr($uuid, 8, 4).'-4'.substr($uuid, 13, 3).'-8'.substr($uuid, 17, 3).'-'.substr($uuid, 20, 12),
            'code' => sprintf('ART-%06d', $sequence),
            'first_name', 'infix', 'last_name', 'full_name', 'date_of_birth', 'email', 'phone' => $person[$column['type']],
            'username' => Str::slug($person['full_name'], '.').'.'.$sequence,
            'job_title' => $random->pick(['Developer', 'Projectmanager', 'Accountmanager', 'Analist', 'Ontwerper', 'Administratief medewerker', 'Adviseur']),
            'website' => $company['website'],
            'address' => trim($address['street'].' '.$address['house_number'].($address['house_number_addition'] ?? '')),
            'street', 'house_number', 'postal_code', 'city', 'province', 'country_code' => $address[$column['type']],
            'country' => 'Nederland',
            'company_name', 'legal_form' => $company[$column['type']],
            'contact_name' => $company['contact_full_name'],
            'company_email' => $company['email'],
            'department' => $random->pick(['IT', 'Verkoop', 'Marketing', 'Financiën', 'HR', 'Logistiek', 'Klantenservice']),
            'car_make', 'car_model', 'car_year', 'car_color', 'car_fuel', 'car_mileage' => $car[$column['type']],
            'car_reference' => 'SYN-AUTO-'.strtoupper(substr(hash('sha256', $seed.':car'), 0, 12)).'-'.$sequence,
            'computer_brand', 'computer_model', 'computer_cpu', 'computer_ram', 'computer_storage', 'computer_os' => $computer[$column['type']],
            'serial_number' => 'SYN-PC-'.strtoupper(substr(hash('sha256', $seed.':pc'), 0, 12)).'-'.$sequence,
            'integer', 'decimal', 'price', 'percentage' => $this->number($column, $random),
            'date', 'datetime' => $this->date($column, $random),
            'time' => sprintf('%02d:%02d:%02d', $random->integer(0, 23), $random->integer(0, 59), $random->integer(0, 59)),
            'boolean' => $random->integer(0, 1) === 1,
            'choice' => $random->pick(array_values(array_filter(array_map('trim', preg_split('/\R/u', $column['choices'])), fn ($value) => $value !== ''))),
            'status' => $random->pick(['Nieuw', 'Actief', 'In behandeling', 'Afgerond', 'Inactief']),
            'text' => $column['value'] ?? $random->pick(['Voorbeeld voor een demonstratie.', 'Fictieve gegevens voor ontwikkeling.', 'Testrecord voor controle.', 'Omschrijving van een voorbeelditem.']),
            'ipv4' => '192.0.2.'.$random->integer(1, 254),
            'mac_address' => '02:'.implode(':', str_split(substr(hash('sha256', $seed.':mac'), 0, 10), 2)),
            'version' => $random->integer(1, 9).'.'.$random->integer(0, 20).'.'.$random->integer(0, 99),
            default => throw new InvalidArgumentException('Unknown column type.'),
        };
    }

    private function car(DeterministicRandom $random): array
    {
        [$make, $model, $firstYear, $fuels] = $random->pick(self::CARS);
        $year = $random->integer($firstYear, 2025);

        return [
            'car_make' => $make,
            'car_model' => $model,
            'car_year' => $year,
            'car_fuel' => $random->pick($fuels),
            'car_color' => $random->pick(['Zwart', 'Wit', 'Grijs', 'Blauw', 'Rood', 'Zilver', 'Groen']),
            'car_mileage' => $random->integer(3000, 25000) * (2026 - $year),
        ];
    }

    private function computer(DeterministicRandom $random): array
    {
        [$brand, $model, $cpu, $ram, $storage, $os] = $random->pick(self::COMPUTERS);

        return [
            'computer_brand' => $brand, 'computer_model' => $model, 'computer_cpu' => $cpu,
            'computer_ram' => $random->pick($ram), 'computer_storage' => $random->pick($storage), 'computer_os' => $os,
        ];
    }

    private function number(array $column, DeterministicRandom $random): int|float
    {
        [$minimum, $maximum, , $scale] = NumericRange::forColumn($column);
        // 52 bits cover the validated numeric range, including six decimal places.
        $bucket = $random->integer(0, 67108863) * 67108864 + $random->integer(0, 67108863);
        $value = $minimum + $bucket % ($maximum - $minimum + 1);

        return $column['type'] === 'integer' ? $value : $value / $scale;
    }

    private function date(array $column, DeterministicRandom $random): string
    {
        $start = new DateTimeImmutable($column['from'] ?? '2020-01-01', new DateTimeZone('UTC'));
        $end = new DateTimeImmutable($column['to'] ?? '2030-12-31', new DateTimeZone('UTC'));
        $date = $start->modify('+'.$random->integer(0, (int) $start->diff($end)->days).' days');

        return $column['type'] === 'datetime'
            ? $date->modify('+'.$random->integer(0, 86399).' seconds')->format('Y-m-d H:i:s')
            : $date->format('Y-m-d');
    }

    private function seed(int $seed, string $context): int
    {
        return (int) hexdec(substr(hash('sha256', $seed.':'.$context), 0, 13));
    }
}
