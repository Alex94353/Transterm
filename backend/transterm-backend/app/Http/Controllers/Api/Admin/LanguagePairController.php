<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\LanguagePairCollection;
use App\Http\Resources\LanguagePairResource;
use App\Models\LanguagePair;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LanguagePairController extends Controller
{
    public function index(Request $request): LanguagePairCollection
    {
        $query = LanguagePair::query()
            ->withCount([
                'glossaries',
            ])
            ->with([
                'sourceLanguage',
                'targetLanguage',
            ]);

        if ($request->filled('id')) {
            $query->where('id', $request->integer('id'));
        }

        if ($request->filled('source_language_id')) {
            $query->where('source_language_id', $request->integer('source_language_id'));
        }

        if ($request->filled('target_language_id')) {
            $query->where('target_language_id', $request->integer('target_language_id'));
        }

        return new LanguagePairCollection(
            $query->orderBy('id')
                ->paginate($request->integer('per_page', 10))
                ->withQueryString()
        );
    }

    public function show(LanguagePair $languagePair): LanguagePairResource
    {
        $languagePair->load([
            'sourceLanguage',
            'targetLanguage',
        ])->loadCount([
            'glossaries',
        ]);

        return new LanguagePairResource($languagePair);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'source_language_id' => ['required', 'integer', 'exists:languages,id', 'different:target_language_id'],
            'target_language_id' => ['required', 'integer', 'exists:languages,id', 'different:source_language_id'],
        ]);

        $exists = LanguagePair::query()
            ->where('source_language_id', $validated['source_language_id'])
            ->where('target_language_id', $validated['target_language_id'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'This language pair already exists.',
            ], 422);
        }

        $languagePair = LanguagePair::create([
            'source_language_id' => $validated['source_language_id'],
            'target_language_id' => $validated['target_language_id'],
        ]);

        $languagePair->load([
            'sourceLanguage',
            'targetLanguage',
        ])->loadCount([
            'glossaries',
        ]);

        return (new LanguagePairResource($languagePair))
            ->additional([
                'message' => 'Language pair created successfully.',
            ])
            ->response()
            ->setStatusCode(201);
    }

    public function update(Request $request, LanguagePair $languagePair): JsonResponse
    {
        $validated = $request->validate([
            'source_language_id' => ['sometimes', 'integer', 'exists:languages,id'],
            'target_language_id' => ['sometimes', 'integer', 'exists:languages,id'],
        ]);

        $sourceLanguageId = $validated['source_language_id'] ?? $languagePair->source_language_id;
        $targetLanguageId = $validated['target_language_id'] ?? $languagePair->target_language_id;

        if ((int) $sourceLanguageId === (int) $targetLanguageId) {
            return response()->json([
                'message' => 'Source and target languages must be different.',
            ], 422);
        }

        $exists = LanguagePair::query()
            ->where('source_language_id', $sourceLanguageId)
            ->where('target_language_id', $targetLanguageId)
            ->where('id', '!=', $languagePair->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'This language pair already exists.',
            ], 422);
        }

        $languagePair->update($validated);

        $languagePair->load([
            'sourceLanguage',
            'targetLanguage',
        ])->loadCount([
            'glossaries',
        ]);

        return (new LanguagePairResource($languagePair))
            ->additional([
                'message' => 'Language pair updated successfully.',
            ])
            ->response();
    }

    public function destroy(LanguagePair $languagePair): JsonResponse
    {
        $languagePair->delete();

        return response()->json([
            'message' => 'Language pair deleted successfully.',
        ]);
    }
}
