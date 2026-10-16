<?php

namespace Tests\Feature;

use App\Services\CustomerGenerator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CustomerGeneratorEndpointsTest extends TestCase
{
    private const VALID_INPUT = [
        'customer_type' => 'mixed',
        'count' => 12,
        'seed' => 314159,
        'private_percentage' => 50,
    ];

    public function test_generator_page_is_available_with_safe_defaults(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertViewIs('dataset-generator')
            ->assertViewHas('records', [])
            ->assertViewHas('options', fn (array $options): bool => $options['dataset'] === 'people'
                && $options['count'] === 100 && $options['seed'] === null
                && $options['format'] === 'xlsx' && count($options['columns']) > 0);
    }

    public function test_preview_contains_the_requested_deterministic_records(): void
    {
        $expected = (new CustomerGenerator)->generate(12, 'mixed', 314159, 50);

        $this->post('/preview', self::VALID_INPUT)
            ->assertOk()
            ->assertViewIs('generator')
            ->assertViewHas('records', $expected)
            ->assertViewHas('options', self::VALID_INPUT);
    }

    public function test_json_export_has_metadata_headers_and_the_expected_schema(): void
    {
        $response = $this->post('/export/json', self::VALID_INPUT);

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/json')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Content-Disposition')
            ->assertJsonPath('meta.count', 12)
            ->assertJsonPath('meta.customer_type', 'mixed')
            ->assertJsonPath('meta.private_percentage', 50)
            ->assertJsonPath('meta.seed', 314159)
            ->assertJsonPath('meta.synthetic', true)
            ->assertJsonCount(12, 'data')
            ->assertJsonStructure([
                'meta' => ['count', 'customer_type', 'private_percentage', 'seed', 'synthetic'],
                'data' => [[
                    'customer_number', 'customer_type', 'synthetic', 'first_name', 'infix',
                    'last_name', 'full_name', 'date_of_birth', 'company_name', 'legal_form',
                    'synthetic_registration_reference', 'contact_first_name', 'contact_infix',
                    'contact_last_name', 'contact_full_name', 'email', 'phone', 'website',
                    'street', 'house_number', 'house_number_addition', 'postal_code', 'city',
                    'province', 'country_code', 'deliverable',
                ]],
            ]);

        $this->assertMatchesRegularExpression(
            '/^attachment; filename="nepklanten_\\d{4}-\\d{2}-\\d{2}_\\d{6}_seed-314159\\.json"$/',
            (string) $response->headers->get('Content-Disposition'),
        );
        $this->assertSame(JSON_ERROR_NONE, json_last_error());
    }

    public function test_csv_export_is_utf8_semicolon_delimited_and_matches_the_generator(): void
    {
        $response = $this->post('/export/csv', self::VALID_INPUT);

        $response->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        $content = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);

        $rows = $this->parseCsv(substr($content, 3));
        $generator = new CustomerGenerator;

        $this->assertCount(13, $rows);
        $this->assertSame($generator->headers(), $rows[0]);
        foreach (array_column(array_slice($rows, 1), 0) as $customerNumber) {
            $this->assertMatchesRegularExpression('/^SYN-[PB]-[0-9]{6}$/', $customerNumber);
        }

        foreach (array_slice($rows, 1) as $row) {
            $this->assertCount(count($rows[0]), $row);
            foreach ($row as $cell) {
                $this->assertDoesNotMatchRegularExpression('/^\\s*[=+\\-@]/u', $cell);
                $this->assertFalse(str_starts_with($cell, "\t"));
                $this->assertFalse(str_starts_with($cell, "\r"));
            }
        }
    }

    public function test_export_is_identical_for_the_same_seed(): void
    {
        $first = $this->post('/export/json', self::VALID_INPUT)->json('data');
        $second = $this->post('/export/json', self::VALID_INPUT)->json('data');

        $this->assertSame($first, $second);
    }

    public function test_csv_export_neutralises_spreadsheet_formula_prefixes(): void
    {
        $unsafeValues = [
            '=1+1',
            '+SUM(A1:A2)',
            '-10+20',
            '@command',
            "\t=1+1",
            "\r-1+1",
            '   =HYPERLINK("https://example.test")',
        ];

        $generator = new class($unsafeValues) extends CustomerGenerator
        {
            public function __construct(private array $values) {}

            public function headers(): array
            {
                return ['email'];
            }

            public function generate(int $count, string $customerType, int $seed, int $privatePercentage = 60): array
            {
                return array_map(fn (string $value): array => ['email' => $value], $this->values);
            }
        };

        $this->app->instance(CustomerGenerator::class, $generator);

        $content = $this->post('/export/csv', [
            'customer_type' => 'private',
            'count' => count($unsafeValues),
            'seed' => 1,
        ])->assertOk()->streamedContent();

        $rows = $this->parseCsv(substr($content, 3));
        $exportedValues = array_column(array_slice($rows, 1), 0);

        foreach ($exportedValues as $value) {
            $this->assertStringStartsWith("'", $value);
        }
    }

    public function test_an_omitted_seed_is_generated_and_reported(): void
    {
        $input = self::VALID_INPUT;
        unset($input['seed']);

        $response = $this->post('/export/json', $input)->assertOk();
        $seed = $response->json('meta.seed');

        $this->assertIsInt($seed);
        $this->assertGreaterThanOrEqual(0, $seed);
        $this->assertLessThanOrEqual(2147483647, $seed);
    }

    #[DataProvider('invalidInputProvider')]
    public function test_invalid_input_is_rejected(string $field, mixed $value): void
    {
        $input = self::VALID_INPUT;
        $input[$field] = $value;

        $this->from('/')->post('/preview', $input)
            ->assertRedirect('/')
            ->assertSessionHasErrors($field);
    }

    public static function invalidInputProvider(): array
    {
        return [
            'unknown customer type' => ['customer_type', 'consumer'],
            'zero count' => ['count', 0],
            'negative count' => ['count', -1],
            'count above maximum' => ['count', 1001],
            'fractional count' => ['count', 1.5],
            'negative seed' => ['seed', -1],
            'seed above maximum' => ['seed', 2147483648],
            'percentage below zero' => ['private_percentage', -1],
            'percentage above one hundred' => ['private_percentage', 101],
        ];
    }

    public function test_private_percentage_is_required_for_mixed_batches(): void
    {
        $input = self::VALID_INPUT;
        unset($input['private_percentage']);

        $this->post('/preview', $input)->assertSessionHasErrors('private_percentage');
    }

    /** @return list<list<string>> */
    private function parseCsv(string $content): array
    {
        $stream = fopen('php://memory', 'r+b');
        fwrite($stream, $content);
        rewind($stream);

        $rows = [];
        while (($row = fgetcsv($stream, escape: '', separator: ';')) !== false) {
            $rows[] = $row;
        }

        fclose($stream);

        return $rows;
    }
}
