<?php

namespace Tests\Feature;

use App\Services\DatasetCatalog;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DatasetGeneratorEndpointsTest extends TestCase
{
    public function test_column_editor_keeps_type_keys_and_selects_the_preset_types(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('value="first_name" selected', false)
            ->assertSee('value="email" selected', false)
            ->assertSee('value="computer_cpu"', false);
    }

    private function input(array $overrides = []): array
    {
        return array_replace([
            'dataset' => 'computers', 'count' => 12, 'seed' => 12345, 'format' => 'xlsx',
            'columns' => $this->app->make(DatasetCatalog::class)->presets()['computers']['columns'],
        ], $overrides);
    }

    public function test_preview_and_json_download_contain_identical_data_and_requested_columns(): void
    {
        $input = $this->input();
        $preview = $this->post('/datasets/preview', $input)->assertOk()->assertViewIs('dataset-generator');
        $json = $this->post('/datasets/download', array_replace($input, ['format' => 'json']))
            ->assertOk()->assertJsonPath('meta.synthetic', true)->assertJsonCount(12, 'data');
        $this->assertSame($preview->viewData('records'), $json->json('data'));
        $this->assertSame(array_column($input['columns'], 'name'), array_keys($json->json('data.0')));
        $preview->assertSee('Genereren en downloaden')->assertSee('RAM (GB)');
    }

    public function test_an_omitted_seed_is_reported_and_reproduces_the_download(): void
    {
        $input = $this->input(['format' => 'json']);
        unset($input['seed']);
        $first = $this->post('/datasets/download', $input)->assertOk();
        $seed = $first->json('meta.seed');
        $this->assertIsInt($seed);
        $second = $this->post('/datasets/download', array_replace($input, ['seed' => $seed]))->assertOk();
        $this->assertSame($first->json('data'), $second->json('data'));
    }

    public function test_exact_decimal_boundaries_are_accepted_without_rounding_outside_the_range(): void
    {
        $this->post('/datasets/download', $this->input(['format' => 'json', 'columns' => [
            ['name' => 'Klein', 'type' => 'decimal', 'min' => '0.07', 'max' => '0.07', 'decimals' => 2],
            ['name' => 'Negatief', 'type' => 'decimal', 'min' => '-0.071', 'max' => '-0.069', 'decimals' => 2],
            ['name' => 'Groot', 'type' => 'decimal', 'min' => '999999999.999999', 'max' => '999999999.999999', 'decimals' => 6],
        ]]))->assertOk()->assertJsonPath('data.0.Klein', 0.07)
            ->assertJsonPath('data.0.Negatief', -0.07)
            ->assertJsonPath('data.0.Groot', 999999999.999999);
    }

    public function test_csv_roundtrips_quotes_semicolons_unicode_and_multiline_and_escapes_formula_headers_and_values(): void
    {
        $input = $this->input(['dataset' => 'custom', 'count' => 1, 'format' => 'csv', 'columns' => [
            ['name' => '=Header', 'type' => 'text', 'value' => '=1+1'],
            ['name' => 'Omschrijving', 'type' => 'text', 'value' => "Café; \"voorbeeld\"\nTweede regel"],
            ['name' => 'Negatief', 'type' => 'integer', 'min' => -12, 'max' => -12],
        ]]);
        $response = $this->post('/datasets/download', $input)->assertOk()->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $content = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        $stream = fopen('php://memory', 'w+b');
        fwrite($stream, substr($content, 3));
        rewind($stream);
        try {
            $this->assertSame(["'=Header", 'Omschrijving', 'Negatief'], fgetcsv($stream, separator: ';', escape: ''));
            $this->assertSame(["'=1+1", "Café; \"voorbeeld\"\nTweede regel", '-12'], fgetcsv($stream, separator: ';', escape: ''));
            $this->assertFalse(fgetcsv($stream, separator: ';', escape: ''));
        } finally {
            fclose($stream);
        }
    }

    public function test_xlsx_roundtrip_preserves_data_types_formats_headers_and_literal_text(): void
    {
        $input = $this->input(['dataset' => 'custom', 'count' => 2, 'columns' => [
            ['name' => 'ID', 'type' => 'id'],
            ['name' => '=Kop', 'type' => 'text', 'value' => '=1+1'],
            ['name' => 'Code', 'type' => 'text', 'value' => '00123'],
            ['name' => 'Bedrag', 'type' => 'price', 'min' => -12.5, 'max' => -12.5, 'decimals' => 2],
            ['name' => 'Datum', 'type' => 'date', 'from' => '2024-02-29', 'to' => '2024-02-29'],
            ['name' => 'Percentage', 'type' => 'percentage', 'min' => 10, 'max' => 10],
            ['name' => 'Actief', 'type' => 'boolean'],
            ['name' => 'Tijd', 'type' => 'time'],
        ]]);
        $response = $this->post('/datasets/download', $input)->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->assertHeader('Content-Disposition', 'attachment; filename=nepdata_custom_seed-12345.xlsx');
        $path = tempnam(sys_get_temp_dir(), 'dataset-test-');
        file_put_contents($path, $response->streamedContent());
        $workbook = null;
        try {
            $workbook = IOFactory::load($path);
            $sheet = $workbook->getActiveSheet();
            $this->assertSame('Testdata', $sheet->getTitle());
            $this->assertSame(3, $sheet->getHighestRow());
            $this->assertSame('A2', $sheet->getFreezePane());
            $this->assertSame('A1:H3', $sheet->getAutoFilter()->getRange());
            $this->assertSame('=Kop', $sheet->getCell('B1')->getValue());
            $this->assertSame(DataType::TYPE_STRING, $sheet->getCell('B1')->getDataType());
            $this->assertSame('=1+1', $sheet->getCell('B2')->getValue());
            $this->assertSame(DataType::TYPE_STRING, $sheet->getCell('B2')->getDataType());
            $this->assertSame('00123', $sheet->getCell('C2')->getValue());
            $this->assertEquals(-12.5, $sheet->getCell('D2')->getValue());
            $this->assertSame(DataType::TYPE_NUMERIC, $sheet->getCell('D2')->getDataType());
            $this->assertSame('29-02-2024', $sheet->getCell('E2')->getFormattedValue());
            $this->assertEquals(0.1, $sheet->getCell('F2')->getValue());
            $this->assertSame('10.00%', $sheet->getCell('F2')->getFormattedValue());
            $this->assertSame(DataType::TYPE_BOOL, $sheet->getCell('G2')->getDataType());
            $this->assertMatchesRegularExpression('/^\d{2}:\d{2}:\d{2}$/', $sheet->getCell('H2')->getFormattedValue());
        } finally {
            $workbook?->disconnectWorksheets();
            unlink($path);
        }
    }

    #[DataProvider('invalidInputProvider')]
    public function test_invalid_input_is_rejected_with_an_explanation(array $override, string $field): void
    {
        $this->from('/')->post('/datasets/download', $this->input($override))
            ->assertRedirect('/')->assertSessionHasErrors($field);
        // Malformed input must also be safe to redisplay after validation.
        $this->get('/')->assertOk();
    }

    public static function invalidInputProvider(): array
    {
        return [
            'unknown subject' => [['dataset' => 'spaceships'], 'dataset'],
            'malformed subject' => [['dataset' => ['computers']], 'dataset'],
            'zero rows' => [['count' => 0], 'count'],
            'too many rows' => [['count' => 1001], 'count'],
            'invalid seed' => [['seed' => -1], 'seed'],
            'invalid format' => [['format' => 'exe'], 'format'],
            'empty columns' => [['columns' => []], 'columns'],
            'malformed column' => [['columns' => ['invalid']], 'columns.0'],
            'unknown type' => [['columns' => [['name' => 'Naam', 'type' => 'magic']]], 'columns.0.type'],
            'empty name' => [['columns' => [['name' => '  ', 'type' => 'id']]], 'columns.0.name'],
            'duplicate names' => [['columns' => [['name' => 'Naam', 'type' => 'id'], ['name' => ' naam ', 'type' => 'text']]], 'columns.1.name'],
            'reversed range' => [['columns' => [['name' => 'Getal', 'type' => 'integer', 'min' => 10, 'max' => 2]]], 'columns.0.max'],
            'fractional integer' => [['columns' => [['name' => 'Getal', 'type' => 'integer', 'min' => 1.5]]], 'columns.0.min'],
            'impossible precision' => [['columns' => [['name' => 'Getal', 'type' => 'decimal', 'min' => 0.001, 'max' => 0.002, 'decimals' => 2]]], 'columns.0.decimals'],
            'tiny impossible precision' => [['columns' => [['name' => 'Getal', 'type' => 'decimal', 'min' => '1e-9', 'max' => '2e-9', 'decimals' => 6]]], 'columns.0.decimals'],
            'reversed dates' => [['columns' => [['name' => 'Datum', 'type' => 'date', 'from' => '2025-02-01', 'to' => '2024-01-01']]], 'columns.0.to'],
            'invalid date' => [['columns' => [['name' => 'Datum', 'type' => 'date', 'from' => '2025-02-29']]], 'columns.0.from'],
            'empty choices' => [['columns' => [['name' => 'Keuze', 'type' => 'choice', 'choices' => "\n\n"]]], 'columns.0.choices'],
            'too many columns' => [['columns' => array_fill(0, 51, ['name' => 'ID', 'type' => 'id'])], 'columns'],
        ];
    }
}
