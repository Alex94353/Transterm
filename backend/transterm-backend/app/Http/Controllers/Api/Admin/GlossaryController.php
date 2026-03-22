<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\GlossaryCollection;
use App\Http\Resources\GlossaryResource;
use App\Models\Glossary;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GlossaryController extends Controller
{
    public function index(Request $request): GlossaryCollection
    {
        $query = Glossary::query()
            ->withCount([
                'terms',
            ])
            ->with([
                'languagePair.sourceLanguage',
                'languagePair.targetLanguage',
                'field.fieldGroup',
                'owner.profile',
                'translations.language',
            ]);

        if ($request->filled('id')) {
            $query->where('id', $request->integer('id'));
        }

        if ($request->filled('language_pair_id')) {
            $query->where('language_pair_id', $request->integer('language_pair_id'));
        }

        if ($request->filled('field_id')) {
            $query->where('field_id', $request->integer('field_id'));
        }

        if ($request->filled('owner_id')) {
            $query->where('owner_id', $request->integer('owner_id'));
        }

        if ($request->filled('approved')) {
            $query->where(
                'approved',
                filter_var($request->input('approved'), FILTER_VALIDATE_BOOLEAN)
            );
        }

        if ($request->filled('is_public')) {
            $query->where(
                'is_public',
                filter_var($request->input('is_public'), FILTER_VALIDATE_BOOLEAN)
            );
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));

            $query->whereHas('translations', function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return new GlossaryCollection(
            $query->orderByDesc('id')
                ->paginate($request->integer('per_page', 10))
                ->withQueryString()
        );
    }

    public function show(Glossary $glossary): GlossaryResource
    {
        $glossary->load([
            'languagePair.sourceLanguage',
            'languagePair.targetLanguage',
            'field.fieldGroup',
            'owner.profile',
            'translations.language',
            'terms.translations.language',
        ])->loadCount([
            'terms',
        ]);

        return new GlossaryResource($glossary);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'language_pair_id' => ['required', 'integer', 'exists:language_pairs,id'],
            'field_id' => ['required', 'integer', 'exists:fields,id'],
            'approved' => ['sometimes', 'boolean'],
            'is_public' => ['sometimes', 'boolean'],
        ]);

        $glossary = Glossary::create([
            'language_pair_id' => $validated['language_pair_id'],
            'field_id' => $validated['field_id'],
            'owner_id' => $request->user()->id,
            'approved' => $validated['approved'] ?? false,
            'is_public' => $validated['is_public'] ?? false,
        ]);

        $glossary->load([
            'languagePair.sourceLanguage',
            'languagePair.targetLanguage',
            'field.fieldGroup',
            'owner.profile',
            'translations.language',
        ])->loadCount([
            'terms',
        ]);

        return (new GlossaryResource($glossary))
            ->additional([
                'message' => 'Glossary created successfully.',
            ])
            ->response()
            ->setStatusCode(201);
    }

    public function update(Request $request, Glossary $glossary): JsonResponse
    {
        $validated = $request->validate([
            'language_pair_id' => ['sometimes', 'integer', 'exists:language_pairs,id'],
            'field_id' => ['sometimes', 'integer', 'exists:fields,id'],
            'approved' => ['sometimes', 'boolean'],
            'is_public' => ['sometimes', 'boolean'],
        ]);

        $glossary->update($validated);

        $glossary->load([
            'languagePair.sourceLanguage',
            'languagePair.targetLanguage',
            'field.fieldGroup',
            'owner.profile',
            'translations.language',
        ])->loadCount([
            'terms',
        ]);

        return (new GlossaryResource($glossary))
            ->additional([
                'message' => 'Glossary updated successfully.',
            ])
            ->response();
    }

    public function destroy(Glossary $glossary): JsonResponse
    {
        $glossary->delete();

        return response()->json([
            'message' => 'Glossary deleted successfully.',
        ]);
    }
}
