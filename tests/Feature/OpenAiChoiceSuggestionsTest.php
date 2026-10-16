<?php

namespace Tests\Feature;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class OpenAiChoiceSuggestionsTest extends TestCase
{
    private const TEST_KEY = 'sk-test-only-not-a-real-api-key-123456';

    private function input(array $overrides = []): array
    {
        return array_replace([
            'name' => 'Type ruimteschip', 'provider' => 'openai', 'api_key' => self::TEST_KEY,
            'model' => 'gpt-4.1-mini', 'count' => 3, 'context' => 'Sciencefiction, geen merken',
        ], $overrides);
    }

    private function completed(array $proposal): array
    {
        return [
            'status' => 'completed',
            'output' => [
                ['type' => 'reasoning'],
                ['type' => 'message', 'content' => [['type' => 'output_text', 'text' => json_encode($proposal)]]],
            ],
        ];
    }

    public function test_ai_options_are_available_in_a_separate_form(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('id="suggestion-dialog"', false)
            ->assertSee('id="suggestion-options-form"', false)
            ->assertSee('id="suggestion-api-key" type="password"', false)
            ->assertSee('OpenAI — eigen API-sleutel');
    }

    public function test_ai_request_returns_a_validated_proposal_without_persisting_credentials(): void
    {
        Http::preventStrayRequests();
        Http::fake(['https://api.openai.com/v1/responses' => Http::response($this->completed([
            'label' => 'Ruimteschepen', 'values' => ['Verkenner', 'Vrachtschip', 'Onderzoeksschip'],
        ]))]);

        $response = $this->postJson('/datasets/choices/suggest', $this->input())
            ->assertOk()->assertHeader('Cache-Control', 'no-store, private')
            ->assertJsonPath('source', 'openai')->assertJsonPath('values.0', 'Verkenner');

        Http::assertSent(function (Request $request): bool {
            $userInput = json_decode($request['input'][1]['content'], true);

            return $request->url() === 'https://api.openai.com/v1/responses'
                && $request->hasHeader('Authorization', 'Bearer '.self::TEST_KEY)
                && $request['model'] === 'gpt-4.1-mini'
                && $request['store'] === false
                && $request['text']['format']['type'] === 'json_schema'
                && $request['text']['format']['schema']['properties']['values']['maxItems'] === 3
                && $userInput['column_name'] === 'Type ruimteschip'
                && $userInput['context'] === 'Sciencefiction, geen merken'
                && ! str_contains(json_encode($request->data()), self::TEST_KEY);
        });
        Http::assertSentCount(1);
        $this->assertStringNotContainsString(self::TEST_KEY, $response->getContent());
        $this->assertStringNotContainsString(self::TEST_KEY, serialize($this->app['session.store']->all()));
    }

    public function test_ai_proposals_can_be_edited_and_used_in_regular_exports_without_credentials(): void
    {
        Http::fake(['https://api.openai.com/v1/responses' => Http::response($this->completed([
            'label' => 'Ruimteschepen', 'values' => ['Verkenner', 'Vrachtschip'],
        ]))]);
        $values = $this->postJson('/datasets/choices/suggest', $this->input())->assertOk()->json('values');
        $values[] = 'Zelf toegevoegd';

        $response = $this->postJson('/datasets/download', [
            'dataset' => 'custom', 'count' => 20, 'seed' => 42, 'format' => 'json',
            'columns' => [['name' => 'Ruimteschip', 'type' => 'choice', 'choices' => implode("\n", $values)]],
        ])->assertOk();
        foreach ($response->json('data') as $record) {
            $this->assertContains($record['Ruimteschip'], $values);
        }
        $this->assertStringNotContainsString(self::TEST_KEY, $response->getContent());
        Http::assertSentCount(1);
    }

    #[DataProvider('invalidInput')]
    public function test_invalid_ai_settings_are_rejected_before_any_external_call(array $overrides, string $field): void
    {
        Http::preventStrayRequests();
        $response = $this->postJson('/datasets/choices/suggest', $this->input($overrides))
            ->assertUnprocessable()->assertJsonValidationErrors($field);
        $this->assertStringNotContainsString(self::TEST_KEY, $response->getContent());
        $this->assertStringNotContainsString(self::TEST_KEY, serialize($this->app['session.store']->all()));
        Http::assertNothingSent();
    }

    public static function invalidInput(): array
    {
        return [
            'missing key' => [['api_key' => null], 'api_key'],
            'malformed key' => [['api_key' => "fake\nheader"], 'api_key'],
            'invalid name' => [['name' => ''], 'name'],
            'unknown provider' => [['provider' => 'unknown'], 'provider'],
            'too few values' => [['count' => 0], 'count'],
            'too many values' => [['count' => 101], 'count'],
            'fractional count' => [['count' => 1.5], 'count'],
            'invalid model' => [['model' => 'model / invalid'], 'model'],
            'too much context' => [['context' => str_repeat('a', 301)], 'context'],
        ];
    }

    #[DataProvider('providerFailures')]
    public function test_provider_errors_are_handled_without_exposing_response_details(int $providerStatus, int $expectedStatus): void
    {
        Http::fake(['https://api.openai.com/v1/responses' => Http::response([
            'error' => ['message' => 'Secret diagnostic: '.self::TEST_KEY],
        ], $providerStatus)]);

        $response = $this->postJson('/datasets/choices/suggest', $this->input())
            ->assertStatus($expectedStatus)->assertJsonPath('source', 'openai')->assertJsonStructure(['message']);
        $this->assertStringNotContainsString(self::TEST_KEY, $response->getContent());
        $this->assertStringNotContainsString('Secret diagnostic', $response->getContent());
        Http::assertSentCount(1);
    }

    public static function providerFailures(): array
    {
        return [
            'invalid credentials' => [401, 422], 'forbidden' => [403, 422],
            'invalid model' => [404, 422], 'unsupported schema' => [400, 422],
            'quota or rate limit' => [429, 429], 'provider error' => [500, 503],
            'redirect rejected' => [302, 503],
        ];
    }

    public function test_a_connection_failure_does_not_expose_the_exception_or_key(): void
    {
        Http::fake(fn () => throw new ConnectionException('Sensitive transport diagnostic: '.self::TEST_KEY));
        $response = $this->postJson('/datasets/choices/suggest', $this->input())->assertStatus(504);
        $this->assertStringNotContainsString(self::TEST_KEY, $response->getContent());
        $this->assertStringNotContainsString('Sensitive transport diagnostic', $response->getContent());
    }

    public function test_refusals_and_incomplete_or_malformed_answers_have_clear_errors(): void
    {
        $responses = [
            ['status' => 'incomplete', 'output' => []],
            ['status' => 'completed', 'output' => [['type' => 'message', 'content' => [['type' => 'refusal']]]]],
            ['status' => 'completed', 'output' => [['type' => 'message', 'content' => [['type' => 'output_text', 'text' => 'not json']]]]],
            $this->completed(['label' => 'Onbruikbaar', 'values' => []]),
            $this->completed(['label' => 'Onbruikbaar', 'values' => [str_repeat('a', 121)]]),
            $this->completed(['label' => 'Onbruikbaar', 'values' => ["Twee\nregels"]]),
            $this->completed(['label' => 'Te veel', 'values' => ['A', 'B', 'C', 'D']]),
        ];
        $sequence = Http::sequence();
        foreach ($responses as $response) {
            $sequence->push($response);
        }
        Http::fake(['https://api.openai.com/v1/responses' => $sequence]);
        foreach ($responses as $index => $unused) {
            $this->postJson('/datasets/choices/suggest', $this->input())
                ->assertStatus($index === 1 ? 422 : 502)->assertJsonStructure(['message']);
        }
        Http::assertSentCount(count($responses));
    }

    public function test_whitespace_and_duplicate_values_are_normalised(): void
    {
        Http::fake(['https://api.openai.com/v1/responses' => Http::response($this->completed([
            'label' => ' Kleuren ', 'values' => [' Groen ', 'groen', 'Blauw'],
        ]))]);
        $this->postJson('/datasets/choices/suggest', $this->input())->assertOk()
            ->assertJsonPath('label', 'Kleuren')->assertJsonPath('values', ['Groen', 'Blauw']);
    }
}
