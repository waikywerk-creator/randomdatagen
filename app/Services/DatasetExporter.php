<?php

namespace App\Services;

use DateTimeImmutable;
use DateTimeZone;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class DatasetExporter
{
    public function csv(array $columns, array $records): void
    {
        $output = fopen('php://output', 'wb');
        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, array_map(fn ($column) => $this->safeCsvCell($column['name']), $columns), ';', '"', '');

        foreach ($records as $record) {
            fputcsv($output, array_map(fn ($column) => $this->safeCsvCell($record[$column['name']]), $columns), ';', '"', '');
        }

        fclose($output);
    }

    public function xlsx(array $columns, array $records): void
    {
        $spreadsheet = new Spreadsheet;
        try {
            $spreadsheet->getProperties()->setCreator('Nepdata-generator')->setTitle('Synthetische testdata');
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Testdata');
            $spreadsheet->getDefaultStyle()->getFont()->setName('Calibri')->setSize(11);
            $lastColumn = Coordinate::stringFromColumnIndex(count($columns));
            $lastRow = count($records) + 1;

            foreach ($columns as $index => $column) {
                $letter = Coordinate::stringFromColumnIndex($index + 1);
                $sheet->setCellValueExplicit($letter.'1', $column['name'], DataType::TYPE_STRING);
                $width = mb_strlen($column['name']);

                foreach ($records as $rowIndex => $record) {
                    $value = $record[$column['name']];
                    $width = max($width, mb_strlen(is_bool($value) ? ($value ? 'TRUE' : 'FALSE') : (string) $value));
                    [$typedValue, $type] = $this->excelValue($value, $column['type']);
                    $sheet->setCellValueExplicit($letter.($rowIndex + 2), $typedValue, $type);
                }

                $sheet->getColumnDimension($letter)->setWidth(min(60, max(15, $width + 3)));
                $sheet->getStyle($letter.'2:'.$letter.$lastRow)->getNumberFormat()->setFormatCode($this->numberFormat($column));
            }

            $sheet->getStyle('A1:'.$lastColumn.'1')->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '176B50']],
            ]);
            $sheet->getRowDimension(1)->setRowHeight(26);
            $sheet->freezePane('A2');
            $sheet->setAutoFilter('A1:'.$lastColumn.$lastRow);
            (new Xlsx($spreadsheet))->save('php://output');
        } finally {
            $spreadsheet->disconnectWorksheets();
        }
    }

    private function excelValue(mixed $value, string $type): array
    {
        if ($value === null) {
            return [null, DataType::TYPE_NULL];
        }
        if (in_array($type, ['date', 'datetime', 'date_of_birth'], true)) {
            return [Date::PHPToExcel(new DateTimeImmutable($value, new DateTimeZone('UTC'))), DataType::TYPE_NUMERIC];
        }
        if ($type === 'time') {
            [$hours, $minutes, $seconds] = array_map('intval', explode(':', $value));

            return [($hours * 3600 + $minutes * 60 + $seconds) / 86400, DataType::TYPE_NUMERIC];
        }
        if ($type === 'percentage') {
            return [$value / 100, DataType::TYPE_NUMERIC];
        }
        if (is_bool($value)) {
            return [$value, DataType::TYPE_BOOL];
        }
        if (is_int($value) || is_float($value)) {
            return [$value, DataType::TYPE_NUMERIC];
        }

        // Explicit strings preserve identifiers, leading zeros and formula-like text.
        return [(string) $value, DataType::TYPE_STRING];
    }

    private function numberFormat(array $column): string
    {
        $decimals = (int) ($column['decimals'] ?? 2);
        $suffix = $decimals > 0 ? '.'.str_repeat('0', $decimals) : '';

        return match ($column['type']) {
            'date', 'date_of_birth' => 'dd-mm-yyyy',
            'datetime' => 'dd-mm-yyyy hh:mm:ss',
            'time' => 'hh:mm:ss',
            'price' => '"€" #,##0'.$suffix,
            'percentage' => '0'.$suffix.'%',
            'decimal' => '#,##0'.$suffix,
            'integer', 'car_mileage', 'computer_ram', 'computer_storage' => '#,##0',
            default => 'General',
        };
    }

    private function safeCsvCell(mixed $value): string
    {
        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }
        $cell = is_bool($value) ? ($value ? 'true' : 'false') : (string) ($value ?? '');

        return preg_match('/^[\s]*[=+\-@]/u', $cell) === 1 || str_starts_with($cell, "\t") || str_starts_with($cell, "\r")
            ? "'".$cell : $cell;
    }
}
