<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateCustomersRequest;
use App\Services\CustomerGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerGeneratorController extends Controller
{
    public function __construct(private readonly CustomerGenerator $generator) {}

    public function index(): View
    {
        return view('generator', [
            'records' => [],
            'options' => $this->defaults(),
        ]);
    }

    public function preview(GenerateCustomersRequest $request): View
    {
        $options = $this->withSeed($request->options());
        $records = $this->generate($options);

        return view('generator', compact('records', 'options'));
    }

    public function json(GenerateCustomersRequest $request): JsonResponse
    {
        $options = $this->withSeed($request->options());
        $records = $this->generate($options);
        $filename = $this->filename('json', $options['seed']);

        return response()->json([
            'meta' => [
                'count' => count($records),
                'customer_type' => $options['customer_type'],
                'private_percentage' => $options['private_percentage'],
                'seed' => $options['seed'],
                'synthetic' => true,
            ],
            'data' => $records,
        ], 200, [
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'X-Content-Type-Options' => 'nosniff',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function csv(GenerateCustomersRequest $request): StreamedResponse
    {
        $options = $this->withSeed($request->options());
        $records = $this->generate($options);
        $headers = $this->generator->headers();
        $filename = $this->filename('csv', $options['seed']);

        return response()->streamDownload(function () use ($headers, $records): void {
            $output = fopen('php://output', 'wb');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, $headers, ';', '"', '');

            foreach ($records as $record) {
                $row = array_map(
                    fn (string $header): string => $this->safeCsvCell($record[$header] ?? ''),
                    $headers,
                );
                fputcsv($output, $row, ';', '"', '');
            }

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /** @param array{customer_type:string,count:int,seed:int,private_percentage:int} $options */
    private function generate(array $options): array
    {
        return $this->generator->generate(
            $options['count'],
            $options['customer_type'],
            $options['seed'],
            $options['private_percentage'],
        );
    }

    /** @param array{customer_type:string,count:int,seed:int|null,private_percentage:int} $options */
    private function withSeed(array $options): array
    {
        $options['seed'] ??= random_int(0, 2147483647);

        return $options;
    }

    private function safeCsvCell(mixed $value): string
    {
        $cell = is_bool($value) ? ($value ? 'true' : 'false') : (string) ($value ?? '');

        if (preg_match('/^[\s]*[=+\-@]/u', $cell) === 1 || str_starts_with($cell, "\t") || str_starts_with($cell, "\r")) {
            return "'".$cell;
        }

        return $cell;
    }

    private function filename(string $extension, int $seed): string
    {
        return 'nepklanten_'.now()->format('Y-m-d_His').'_seed-'.$seed.'.'.$extension;
    }

    /** @return array{customer_type:string,count:int,seed:null,private_percentage:int} */
    private function defaults(): array
    {
        return [
            'customer_type' => 'mixed',
            'count' => 100,
            'seed' => null,
            'private_percentage' => 60,
        ];
    }
}
