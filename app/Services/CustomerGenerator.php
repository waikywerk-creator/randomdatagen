<?php

namespace App\Services;

use App\Services\Support\DeterministicRandom;
use Illuminate\Support\Str;
use InvalidArgumentException;

class CustomerGenerator
{
    private const FIRST_NAMES = [
        'Anne', 'Bram', 'Daan', 'Elin', 'Fenna', 'Hugo', 'Isa', 'Jesse',
        'Lina', 'Mees', 'Nora', 'Olivier', 'Saar', 'Tess', 'Yara', 'Zep',
    ];

    private const LAST_NAMES = [
        'Bakker', 'Bos', 'Dekker', 'De Jong', 'De Vries', 'Jansen',
        'Meijer', 'Mulder', 'Smit', 'Visser', 'Van Dijk', 'Van Leeuwen',
    ];

    private const BUSINESS_WORDS = [
        'Voorbeeld Advies', 'Demo Digitaal', 'Proef & Partners', 'Fictief Bouw',
        'Testpunt Logistiek', 'Simulatie Studio', 'Model Groen', 'Concept Techniek',
    ];

    private const PRIVATE_EMAIL_DOMAINS = [
        'mailpost.test',
        'inboxmail.test',
        'thuispost.test',
    ];

    private const LEGAL_FORMS = [
        ['name' => 'B.V.', 'suffix' => 'B.V.'],
        ['name' => 'V.O.F.', 'suffix' => 'V.O.F.'],
        ['name' => 'eenmanszaak', 'suffix' => ''],
        ['name' => 'stichting', 'suffix' => 'Stichting'],
    ];

    /**
     * These combinations are deliberately fictional. They look Dutch, but should
     * never be interpreted as deliverable postal addresses.
     */
    private const ADDRESSES = [
        ['street' => 'Voorbeeldstraat', 'city' => 'Testdam', 'province' => 'Noord-Holland', 'postal_prefix' => 1234],
        ['street' => 'Demolaan', 'city' => 'Proefstad', 'province' => 'Zuid-Holland', 'postal_prefix' => 2345],
        ['street' => 'Fictieplein', 'city' => 'Mockingen', 'province' => 'Utrecht', 'postal_prefix' => 3456],
        ['street' => 'Simulatiehof', 'city' => 'Demoburg', 'province' => 'Gelderland', 'postal_prefix' => 4567],
        ['street' => 'Generatorweg', 'city' => 'Testwijk', 'province' => 'Noord-Brabant', 'postal_prefix' => 5678],
        ['street' => 'Proefsingel', 'city' => 'Voorbeeldveen', 'province' => 'Overijssel', 'postal_prefix' => 6789],
        ['street' => 'Modelkade', 'city' => 'Fictiedorp', 'province' => 'Friesland', 'postal_prefix' => 7891],
        ['street' => 'Conceptdreef', 'city' => 'Simulatiestad', 'province' => 'Drenthe', 'postal_prefix' => 8912],
    ];

    private const HEADERS = [
        'customer_number',
        'customer_type',
        'synthetic',
        'first_name',
        'infix',
        'last_name',
        'full_name',
        'date_of_birth',
        'company_name',
        'legal_form',
        'synthetic_registration_reference',
        'contact_first_name',
        'contact_infix',
        'contact_last_name',
        'contact_full_name',
        'email',
        'phone',
        'website',
        'street',
        'house_number',
        'house_number_addition',
        'postal_code',
        'city',
        'province',
        'country_code',
        'deliverable',
    ];

    /**
     * @return list<array<string, bool|int|string|null>>
     */
    public function generate(
        int $count,
        string $customerType,
        int $seed,
        int $privatePercentage = 60,
    ): array {
        if ($count < 0) {
            throw new InvalidArgumentException('Count cannot be negative.');
        }

        if ($privatePercentage < 0 || $privatePercentage > 100) {
            throw new InvalidArgumentException('Private percentage must be between 0 and 100.');
        }

        $type = $this->normaliseCustomerType($customerType);
        $random = new DeterministicRandom($seed);
        $types = $this->typesFor($count, $type, $privatePercentage, $random);
        $records = [];
        $sequences = ['private' => 0, 'business' => 0];
        $usedEmails = [];

        foreach ($types as $index => $recordType) {
            $sequences[$recordType]++;
            $records[] = $this->makeRecord(
                $recordType,
                $sequences[$recordType],
                $index + 1,
                $seed,
                $random,
                $usedEmails,
            );
        }

        return $records;
    }

    /** @return list<string> */
    public function headers(): array
    {
        return self::HEADERS;
    }

    private function normaliseCustomerType(string $customerType): string
    {
        return match (Str::lower(trim($customerType))) {
            'particulier', 'private' => 'private',
            'zakelijk', 'business' => 'business',
            'gemengd', 'mixed' => 'mixed',
            default => throw new InvalidArgumentException('Customer type must be particulier, zakelijk or gemengd.'),
        };
    }

    /**
     * @return list<string>
     */
    private function typesFor(
        int $count,
        string $type,
        int $privatePercentage,
        DeterministicRandom $random,
    ): array {
        if ($type !== 'mixed') {
            return array_fill(0, $count, $type);
        }

        $privateCount = (int) round($count * ($privatePercentage / 100), 0, PHP_ROUND_HALF_UP);
        $types = array_merge(
            array_fill(0, $privateCount, 'private'),
            array_fill(0, $count - $privateCount, 'business'),
        );

        return $random->shuffle($types);
    }

    /**
     * @return array<string, bool|int|string|null>
     */
    private function makeRecord(
        string $type,
        int $sequence,
        int $globalSequence,
        int $seed,
        DeterministicRandom $random,
        array &$usedEmails,
    ): array {
        $isPrivate = $type === 'private';
        $person = $this->person($random);
        $address = $this->address($random);
        $customerNumber = sprintf('SYN-%s-%06d', $isPrivate ? 'P' : 'B', $sequence);

        if ($isPrivate) {
            $emailLocalPart = $this->slug(implode('.', array_filter([
                $person['first_name'],
                $person['infix'],
                $person['last_name'],
            ])));
            $email = $this->uniqueEmail(
                $emailLocalPart,
                $random->pick(self::PRIVATE_EMAIL_DOMAINS),
                $usedEmails,
            );

            return $this->ordered([
                'customer_number' => $customerNumber,
                'customer_type' => 'private',
                'synthetic' => true,
                ...$person,
                'date_of_birth' => $this->dateOfBirth($random),
                'company_name' => null,
                'legal_form' => null,
                'synthetic_registration_reference' => null,
                'contact_first_name' => null,
                'contact_infix' => null,
                'contact_last_name' => null,
                'contact_full_name' => null,
                'email' => $email,
                'phone' => $this->safePhone($globalSequence),
                'website' => null,
                ...$address,
            ]);
        }

        $business = $this->business($random);
        $domain = $this->businessDomain($business['company_name']);
        $contactLocalPart = $this->slug(implode('.', array_filter([
            $person['first_name'],
            $person['infix'],
            $person['last_name'],
        ])));
        $email = $this->uniqueEmail($contactLocalPart, $domain, $usedEmails);

        return $this->ordered([
            'customer_number' => $customerNumber,
            'customer_type' => 'business',
            'synthetic' => true,
            'first_name' => null,
            'infix' => null,
            'last_name' => null,
            'full_name' => null,
            'date_of_birth' => null,
            ...$business,
            'synthetic_registration_reference' => 'SYN-REG-'.$this->uniqueToken($seed, $globalSequence),
            'contact_first_name' => $person['first_name'],
            'contact_infix' => $person['infix'],
            'contact_last_name' => $person['last_name'],
            'contact_full_name' => $person['full_name'],
            'email' => $email,
            'phone' => $this->safePhone($globalSequence),
            'website' => 'https://'.$domain,
            ...$address,
        ]);
    }

    /** @return array{first_name: string, infix: string|null, last_name: string, full_name: string} */
    private function person(DeterministicRandom $random): array
    {
        $firstName = $random->pick(self::FIRST_NAMES);
        $rawLastName = $random->pick(self::LAST_NAMES);
        $parts = explode(' ', $rawLastName);
        $infix = null;

        if (in_array(Str::lower($parts[0]), ['de', 'van'], true)) {
            $infixParts = [array_shift($parts)];
            if (isset($parts[0]) && in_array(Str::lower($parts[0]), ['de', 'der'], true)) {
                $infixParts[] = array_shift($parts);
            }
            $infix = Str::lower(implode(' ', $infixParts));
        }

        $lastName = implode(' ', $parts);

        return [
            'first_name' => $firstName,
            'infix' => $infix,
            'last_name' => $lastName,
            'full_name' => implode(' ', array_filter([$firstName, $infix, $lastName])),
        ];
    }

    /** @return array{company_name: string, legal_form: string} */
    private function business(DeterministicRandom $random): array
    {
        $baseName = $random->pick(self::BUSINESS_WORDS);
        $legalForm = $random->pick(self::LEGAL_FORMS);
        $businessName = match ($legalForm['name']) {
            'stichting' => $legalForm['suffix'].' '.$baseName,
            'eenmanszaak' => $baseName,
            default => $baseName.' '.$legalForm['suffix'],
        };

        return [
            'company_name' => $businessName,
            'legal_form' => $legalForm['name'],
        ];
    }

    /** @return array<string, bool|int|string|null> */
    private function address(DeterministicRandom $random): array
    {
        $address = $random->pick(self::ADDRESSES);
        $addition = $random->integer(1, 100) <= 15
            ? $random->pick(['A', 'B', 'bis', 'hs'])
            : null;
        $letters = chr($random->integer(0, 25) + 65).chr($random->integer(0, 25) + 65);

        return [
            'street' => $address['street'],
            'house_number' => $random->integer(1, 250),
            'house_number_addition' => $addition,
            'postal_code' => $address['postal_prefix'].' '.$letters,
            'city' => $address['city'],
            'province' => $address['province'],
            'country_code' => 'NL',
            'deliverable' => false,
        ];
    }

    private function dateOfBirth(DeterministicRandom $random): string
    {
        $start = strtotime('1945-01-01');
        $end = strtotime('2005-12-31');

        return date('Y-m-d', $random->integer($start, $end));
    }

    private function safePhone(int $sequence): string
    {
        // 00 is not a Dutch geographic/mobile area code; this can never be dialled as generated.
        return sprintf('+31 (0)00 000 %04d', $sequence % 10000);
    }

    private function uniqueToken(int $seed, int $sequence): string
    {
        return Str::lower(substr(hash('sha256', $seed.':customer:'.$sequence), 0, 8));
    }

    /** @param array<string, true> $usedEmails */
    private function uniqueEmail(string $localPart, string $domain, array &$usedEmails): string
    {
        $email = $localPart.'@'.$domain;
        $suffix = 2;

        while (isset($usedEmails[$email])) {
            $email = $localPart.$suffix.'@'.$domain;
            $suffix++;
        }

        $usedEmails[$email] = true;

        return $email;
    }

    private function slug(string $value): string
    {
        return trim((string) preg_replace('/[^a-z0-9]+/', '.', Str::lower(Str::ascii($value))), '.');
    }

    private function businessDomain(string $companyName): string
    {
        $nameWithoutLegalForm = preg_replace(
            '/^stichting\s+|\s+(?:b\.v\.|v\.o\.f\.)$/i',
            '',
            $companyName,
        );

        return $this->slug((string) $nameWithoutLegalForm).'.test';
    }

    /**
     * @param  array<string, bool|int|string|null>  $record
     * @return array<string, bool|int|string|null>
     */
    private function ordered(array $record): array
    {
        return array_replace(array_fill_keys(self::HEADERS, null), $record);
    }
}
