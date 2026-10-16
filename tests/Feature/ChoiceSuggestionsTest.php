<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ChoiceSuggestionsTest extends TestCase
{
    #[DataProvider('columnNames')]
    public function test_known_column_names_produce_local_values(string $name, string $topic, string $expected): void
    {
        Http::preventStrayRequests();
        $this->postJson('/datasets/choices/suggest', ['name' => $name])
            ->assertOk()->assertJsonPath('topic', $topic)->assertJsonPath('source', 'local')
            ->assertJsonFragment([$expected]);
        Http::assertNothingSent();
    }

    public static function columnNames(): array
    {
        return [
            'study' => ['Studie', 'studies', 'Informatica'],
            'normalised name' => ['  GEVOLGDE_OPLEIDING  ', 'studies', 'Verpleegkunde'],
            'field of study' => ['Field of study', 'studies', 'Psychologie'],
            'education level' => ['Opleidingsniveau', 'education_levels', 'Hbo'],
            'specific status' => ['Invoice status', 'invoice_statuses', 'Betaald'],
            'compound status' => ['Factuurstatus', 'invoice_statuses', 'Openstaand'],
            'programming' => ['Programmeertaal medewerker', 'programming_languages', 'PHP'],
            'sport' => ['Favoriete sport', 'sports', 'Tennis'],
        ];
    }

    public function test_unknown_names_are_explained_instead_of_getting_an_unrelated_list(): void
    {
        Http::preventStrayRequests();
        foreach (['Studiejaar', 'Landingsbaan', 'Mijn volledig eigen onderwerp'] as $name) {
            $this->postJson('/datasets/choices/suggest', ['name' => $name])
                ->assertUnprocessable()->assertJsonStructure(['message'])->assertJsonMissingPath('values');
        }
        Http::assertNothingSent();
    }

    public function test_invalid_names_are_rejected(): void
    {
        foreach (['', ['Studie'], str_repeat('a', 81), "Stu\x00die"] as $name) {
            $this->postJson('/datasets/choices/suggest', ['name' => $name])
                ->assertUnprocessable()->assertJsonValidationErrors('name');
        }
    }

    public function test_all_local_lists_fit_the_existing_choice_column_limits(): void
    {
        foreach (config('choice_suggestions') as $topic) {
            $this->assertNotEmpty($topic['aliases']);
            $this->assertGreaterThanOrEqual(1, count($topic['values']));
            $this->assertLessThanOrEqual(100, count($topic['values']));
            $this->assertCount(count($topic['values']), array_unique(array_map('mb_strtolower', $topic['values'])));
            foreach ($topic['values'] as $value) {
                $this->assertNotSame('', trim($value));
                $this->assertLessThanOrEqual(120, mb_strlen($value));
            }
        }
    }

    public function test_proposed_values_can_be_used_as_a_regular_choice_column_in_exports(): void
    {
        $values = $this->postJson('/datasets/choices/suggest', ['name' => 'Studie'])->assertOk()->json('values');
        $response = $this->postJson('/datasets/download', [
            'dataset' => 'custom', 'count' => 40, 'seed' => 42, 'format' => 'json',
            'columns' => [['name' => 'Studie', 'type' => 'choice', 'choices' => implode("\n", $values)]],
        ])->assertOk()->assertJsonCount(40, 'data');

        foreach ($response->json('data') as $record) {
            $this->assertContains($record['Studie'], $values);
        }
    }
}
