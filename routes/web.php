<?php

use App\Http\Controllers\ChoiceSuggestionsController;
use App\Http\Controllers\CustomerGeneratorController;
use App\Http\Controllers\DatasetGeneratorController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DatasetGeneratorController::class, 'index'])->name('generator.index');
Route::post('/datasets/preview', [DatasetGeneratorController::class, 'preview'])->name('datasets.preview');
Route::post('/datasets/download', [DatasetGeneratorController::class, 'download'])->name('datasets.download');
Route::post('/datasets/choices/suggest', ChoiceSuggestionsController::class)
    ->middleware('throttle:60,1')->name('datasets.choices.suggest');

// Keep existing customer preview/export requests compatible.
Route::post('/preview', [CustomerGeneratorController::class, 'preview'])->name('generator.preview');
Route::post('/export/csv', [CustomerGeneratorController::class, 'csv'])->name('generator.export.csv');
Route::post('/export/json', [CustomerGeneratorController::class, 'json'])->name('generator.export.json');
