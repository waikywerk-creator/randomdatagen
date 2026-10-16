<?php

// The catalog drives validation, generation and the column editor.
$types = [];
$groups = [
    'Identificatie' => [
        'id' => 'Oplopend nummer', 'uuid' => 'Unieke ID (UUID)', 'code' => 'Artikelcode',
    ],
    'Personen en contact' => [
        'first_name' => 'Voornaam', 'infix' => 'Tussenvoegsel', 'last_name' => 'Achternaam',
        'full_name' => 'Volledige naam', 'date_of_birth' => 'Geboortedatum', 'job_title' => 'Functie',
        'email' => 'E-mailadres', 'phone' => 'Telefoonnummer', 'username' => 'Gebruikersnaam',
        'website' => 'Website',
    ],
    'Adressen' => [
        'address' => 'Volledig adres', 'street' => 'Straat', 'house_number' => 'Huisnummer',
        'postal_code' => 'Postcode', 'city' => 'Plaats', 'province' => 'Provincie',
        'country' => 'Land', 'country_code' => 'Landcode',
    ],
    'Bedrijven' => [
        'company_name' => 'Bedrijfsnaam', 'legal_form' => 'Rechtsvorm', 'department' => 'Afdeling',
        'contact_name' => 'Contactpersoon', 'company_email' => 'Zakelijk e-mailadres',
    ],
    'Auto’s' => [
        'car_make' => 'Automerk', 'car_model' => 'Automodel', 'car_year' => 'Bouwjaar',
        'car_color' => 'Autokleur', 'car_fuel' => 'Brandstof', 'car_mileage' => 'Kilometerstand',
        'car_reference' => 'Voertuigreferentie',
    ],
    'Computers' => [
        'computer_brand' => 'Computermerk', 'computer_model' => 'Computermodel',
        'computer_cpu' => 'Processor', 'computer_ram' => 'RAM (GB)',
        'computer_storage' => 'Opslag (GB)', 'computer_os' => 'Besturingssysteem',
        'serial_number' => 'Serienummer',
    ],
    'Algemeen' => [
        'integer' => 'Geheel getal', 'decimal' => 'Decimaal getal', 'price' => 'Bedrag (€)',
        'percentage' => 'Percentage', 'date' => 'Datum', 'datetime' => 'Datum en tijd',
        'time' => 'Tijd', 'boolean' => 'Ja / nee', 'choice' => 'Eigen keuzelijst',
        'status' => 'Status', 'text' => 'Tekst / omschrijving',
    ],
    'Technisch' => [
        'ipv4' => 'IP-adres', 'mac_address' => 'MAC-adres', 'version' => 'Versienummer',
    ],
];

foreach ($groups as $group => $entries) {
    foreach ($entries as $key => $label) {
        $types[$key] = ['label' => $label, 'group' => $group];
    }
}

$columns = static function (array $fields): array {
    return array_map(
        static fn (string $name, string $type): array => ['name' => $name, 'type' => $type],
        array_keys($fields), array_values($fields),
    );
};

return [
    'max_rows' => 1000,
    'max_columns' => 50,
    'types' => $types,
    'presets' => [
        'people' => [
            'label' => 'Personen',
            'description' => 'Nederlandse personen met bijpassende contactgegevens.',
            'columns' => $columns([
                'ID' => 'id', 'Voornaam' => 'first_name', 'Tussenvoegsel' => 'infix',
                'Achternaam' => 'last_name', 'Volledige naam' => 'full_name',
                'Geboortedatum' => 'date_of_birth', 'E-mail' => 'email',
                'Telefoon' => 'phone', 'Adres' => 'address', 'Postcode' => 'postal_code', 'Plaats' => 'city',
            ]),
        ],
        'companies' => [
            'label' => 'Bedrijven',
            'description' => 'Fictieve bedrijven, contactpersonen en zakelijke e-mailadressen.',
            'columns' => $columns([
                'ID' => 'id', 'Bedrijfsnaam' => 'company_name', 'Rechtsvorm' => 'legal_form',
                'Contactpersoon' => 'contact_name', 'E-mail' => 'company_email',
                'Website' => 'website', 'Adres' => 'address', 'Postcode' => 'postal_code', 'Plaats' => 'city',
            ]),
        ],
        'cars' => [
            'label' => 'Auto’s',
            'description' => 'Passende combinaties van automerk, model, bouwjaar en brandstof.',
            'columns' => [
                ...$columns([
                    'ID' => 'id', 'Merk' => 'car_make', 'Model' => 'car_model', 'Bouwjaar' => 'car_year',
                    'Kleur' => 'car_color', 'Brandstof' => 'car_fuel', 'Kilometerstand' => 'car_mileage',
                ]),
                ['name' => 'Prijs', 'type' => 'price', 'min' => 5000, 'max' => 75000, 'decimals' => 2],
            ],
        ],
        'computers' => [
            'label' => 'Computers',
            'description' => 'Computers met samenhangende hardware en besturingssystemen.',
            'columns' => [
                ...$columns([
                    'ID' => 'id', 'Merk' => 'computer_brand', 'Model' => 'computer_model',
                    'Processor' => 'computer_cpu', 'RAM (GB)' => 'computer_ram',
                    'Opslag (GB)' => 'computer_storage', 'Besturingssysteem' => 'computer_os',
                    'Serienummer' => 'serial_number',
                ]),
                ['name' => 'Prijs', 'type' => 'price', 'min' => 400, 'max' => 3500, 'decimals' => 2],
            ],
        ],
        'custom' => [
            'label' => 'Zelf samenstellen',
            'description' => 'Combineer kolomtypen uit alle onderwerpen in je eigen tabel.',
            'columns' => $columns(['ID' => 'id']),
        ],
    ],
];
