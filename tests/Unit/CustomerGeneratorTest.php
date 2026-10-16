<?php

namespace Tests\Unit;

use App\Services\CustomerGenerator;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CustomerGeneratorTest extends TestCase
{
    private CustomerGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->generator = new CustomerGenerator;
    }

    public function test_generation_is_deterministic_for_the_same_seed(): void
    {
        $first = $this->generator->generate(25, 'mixed', 123456, 40);
        $second = $this->generator->generate(25, 'mixed', 123456, 40);

        $this->assertSame($first, $second);
        $this->assertNotSame($first, $this->generator->generate(25, 'mixed', 123457, 40));
    }

    #[DataProvider('customerTypeProvider')]
    public function test_it_generates_complete_synthetic_records(string $requestedType, string $expectedType): void
    {
        $records = $this->generator->generate(20, $requestedType, 42);
        $headers = $this->generator->headers();

        $this->assertCount(20, $records);
        $this->assertCount(20, array_unique(array_column($records, 'customer_number')));
        $this->assertCount(20, array_unique(array_column($records, 'email')));

        foreach ($records as $record) {
            $this->assertSame($headers, array_keys($record));
            $this->assertSame($expectedType, $record['customer_type']);
            $this->assertTrue($record['synthetic']);
            $this->assertFalse($record['deliverable']);
            $this->assertSame('NL', $record['country_code']);
            $this->assertMatchesRegularExpression('/^[1-9][0-9]{3} [A-Z]{2}$/', $record['postal_code']);
            $this->assertMatchesRegularExpression('/^[^@\\s]+@[^@\\s]+\\.test$/', $record['email']);
            $this->assertDoesNotMatchRegularExpression('/^\\s*[=+\\-@]/u', (string) $record['email']);
        }
    }

    public static function customerTypeProvider(): array
    {
        return [
            'private API value' => ['private', 'private'],
            'business API value' => ['business', 'business'],
            'Dutch private value' => ['particulier', 'private'],
            'Dutch business value' => ['zakelijk', 'business'],
        ];
    }

    public function test_private_and_business_fields_are_semantically_separated(): void
    {
        $private = $this->generator->generate(1, 'private', 10)[0];
        $business = $this->generator->generate(1, 'business', 10)[0];

        $this->assertNotEmpty($private['full_name']);
        $this->assertNotEmpty($private['date_of_birth']);
        $this->assertNull($private['company_name']);
        $this->assertNull($private['synthetic_registration_reference']);

        $this->assertNull($business['full_name']);
        $this->assertNull($business['date_of_birth']);
        $this->assertNotEmpty($business['company_name']);
        $this->assertNotEmpty($business['contact_full_name']);
        $this->assertStringStartsWith('SYN-REG-', $business['synthetic_registration_reference']);
        $this->assertStringEndsWith('.test', $business['website']);
    }

    public function test_emails_look_natural_without_hash_suffixes_and_remain_safe(): void
    {
        $privateRecords = $this->generator->generate(250, 'private', 42);
        $businessRecords = $this->generator->generate(250, 'business', 42);

        foreach ([...$privateRecords, ...$businessRecords] as $record) {
            $this->assertDoesNotMatchRegularExpression('/\.[a-f0-9]{8}@/', $record['email']);
            $this->assertStringEndsWith('.test', $record['email']);
        }

        $this->assertCount(250, array_unique(array_column($privateRecords, 'email')));
        $this->assertCount(250, array_unique(array_column($businessRecords, 'email')));
    }

    public function test_mixed_percentage_is_applied_with_documented_rounding(): void
    {
        $records = $this->generator->generate(11, 'mixed', 99, 50);

        $this->assertCount(6, array_filter($records, fn (array $record): bool => $record['customer_type'] === 'private'));
        $this->assertCount(5, array_filter($records, fn (array $record): bool => $record['customer_type'] === 'business'));
    }

    public function test_zero_count_returns_an_empty_batch(): void
    {
        $this->assertSame([], $this->generator->generate(0, 'private', 1));
    }

    #[DataProvider('invalidOptionsProvider')]
    public function test_invalid_direct_service_options_are_rejected(int $count, string $type, int $percentage): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->generator->generate($count, $type, 1, $percentage);
    }

    public static function invalidOptionsProvider(): array
    {
        return [
            'negative count' => [-1, 'private', 60],
            'unknown type' => [1, 'consumer', 60],
            'percentage below zero' => [1, 'mixed', -1],
            'percentage above one hundred' => [1, 'mixed', 101],
        ];
    }
}
