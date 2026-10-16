<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateDatasetRequest;
use App\Services\DatasetCatalog;
use App\Services\DatasetExporter;
use App\Services\DatasetGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DatasetGeneratorController extends Controller
{
    public function __construct(
        private readonly DatasetCatalog $catalog,
        private readonly DatasetGenerator $generator,
        private readonly DatasetExporter $exporter,
    ) {}

    public function index(): View
    {
        return $this->view($this->catalog->defaults(), []);
    }

    public function preview(GenerateDatasetRequest $request): View
    {
        $options = $request->options();

        return $this->view($options, $this->generate($options));
    }

    public function download(GenerateDatasetRequest $request): StreamedResponse|JsonResponse
    {
        $options = $request->options();
        $records = $this->generate($options);
        $filename = 'nepdata_'.$options['dataset'].'_seed-'.$options['seed'].'.'.$options['format'];

        if ($options['format'] === 'json') {
            return response()->json([
                'meta' => [
                    'dataset' => $options['dataset'], 'count' => count($records),
                    'seed' => $options['seed'], 'synthetic' => true, 'columns' => $options['columns'],
                ],
                'data' => $records,
            ], 200, [
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'no-store',
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $format = $options['format'];

        return response()->streamDownload(function () use ($format, $options, $records): void {
            $this->exporter->{$format}($options['columns'], $records);
        }, $filename, [
            'Content-Type' => $format === 'csv'
                ? 'text/csv; charset=UTF-8'
                : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'no-store',
        ]);
    }

    private function generate(array $options): array
    {
        return $this->generator->generate($options['columns'], $options['count'], $options['seed'], $options['dataset']);
    }

    private function view(array $options, array $records): View
    {
        return view('dataset-generator', [
            'options' => $options, 'records' => $records,
            'presets' => $this->catalog->presets(), 'types' => $this->catalog->types(),
        ]);
    }
}
