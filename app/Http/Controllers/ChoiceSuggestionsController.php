<?php

namespace App\Http\Controllers;

use App\Exceptions\ChoiceSuggestionException;
use App\Services\ChoiceSuggestions;
use App\Services\OpenAiChoiceSuggestions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ChoiceSuggestionsController extends Controller
{
    public function __invoke(Request $request, ChoiceSuggestions $suggestions, OpenAiChoiceSuggestions $ai): JsonResponse
    {
        $apiKey = $request->input('api_key');
        // Keep credentials out of normal request input and validation flashes.
        $request->request->remove('api_key');
        $request->json()->remove('api_key');
        $validator = Validator::make([...$request->all(), 'api_key' => $apiKey], [
            'name' => ['required', 'string', 'max:80', 'not_regex:/[\x00-\x1F\x7F]/u'],
            'provider' => ['nullable', Rule::in(['local', 'openai'])],
            'api_key' => [Rule::requiredIf($request->input('provider') === 'openai'), 'nullable', 'string', 'min:16', 'max:512', 'regex:/^[A-Za-z0-9_-]+$/D'],
            'model' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9][A-Za-z0-9._:-]*$/D'],
            'count' => ['nullable', 'integer', 'between:1,100'],
            'context' => ['nullable', 'string', 'max:300', 'not_regex:/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u'],
        ], [
            'name.required' => 'Vul eerst een kolomnaam in, bijvoorbeeld Studie.',
            'name.string' => 'De kolomnaam moet tekst zijn.',
            'name.max' => 'De kolomnaam mag maximaal 80 tekens bevatten.',
            'name.not_regex' => 'De kolomnaam bevat ongeldige besturingstekens.',
            'provider.in' => 'Kies een beschikbare bron voor het voorstel.',
            'api_key.required' => 'Vul je OpenAI API-sleutel in.',
            'api_key.*' => 'Vul een geldige API-sleutel in zonder spaties.',
            'model.*' => 'Vul een geldige modelnaam in.',
            'count.*' => 'Kies een heel aantal van 1 t/m 100 waarden.',
            'context.*' => 'Gebruik een toelichting van maximaal 300 tekens.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $input = $validator->validated();

        if (($input['provider'] ?? 'local') === 'openai') {
            try {
                $result = $ai->forName(
                    $input['name'], $input['api_key'], $input['model'] ?? OpenAiChoiceSuggestions::DEFAULT_MODEL,
                    (int) ($input['count'] ?? 20), $input['context'] ?? '',
                );
            } catch (ChoiceSuggestionException $exception) {
                return response()->json(['message' => $exception->getMessage(), 'source' => 'openai'], $exception->statusCode, ['Cache-Control' => 'no-store']);
            }

            return response()->json($result, 200, ['Cache-Control' => 'no-store']);
        }

        $result = $suggestions->forName($input['name']);
        if ($result === null) {
            return response()->json([
                'message' => 'Voor deze kolomnaam is nog geen lijst beschikbaar. Kies een duidelijk onderwerp, zoals Studie, Beroep, Kleur of Sport, of vul zelf waarden in bij Eigen keuzelijst.',
            ], 422);
        }

        return response()->json($result, 200, ['Cache-Control' => 'no-store']);
    }
}
