<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\TermCollection;
use App\Http\Resources\TermResource;
use App\Models\Term;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TermController extends Controller
{
    public function index(Request $request): TermCollection
    {
        $query = Term::query()
            ->withCount([
                'comments',
                'translations',
            ])
            ->with([
                'glossary.translations.language',
                'glossary.languagePair.sourceLanguage',
                'glossary.languagePair.targetLanguage',
                'field.fieldGroup',
                'creator.profile',
                'translations.language',
                'translations.termReferences.reference',
                'comments.user',
            ]);

        if ($request->filled('id')) {
            $query->where('id', $request->integer('id'));
        }

        if ($request->filled('glossary_id')) {
            $query->where('glossary_id', $request->integer('glossary_id'));
        }

        if ($request->filled('field_id')) {
            $query->where('field_id', $request->integer('field_id'));
        }

        if ($request->filled('created_by')) {
            $query->where('created_by', $request->integer('created_by'));
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));

            $query->whereHas('translations', function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('plural', 'like', "%{$search}%")
                    ->orWhere('definition', 'like', "%{$search}%")
                    ->orWhere('context', 'like', "%{$search}%")
                    ->orWhere('synonym', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        return new TermCollection(
            $query->orderByDesc('id')
                ->paginate($request->integer('per_page', 10))
                ->withQueryString()
        );
    }

    public function show(Term $term): TermResource
    {
        $term->load([
            'glossary.translations.language',
            'glossary.languagePair.sourceLanguage',
            'glossary.languagePair.targetLanguage',
            'field.fieldGroup',
            'creator.profile',
            'translations.language',
            'translations.termReferences.reference',
            'comments.user',
        ])->loadCount([
            'comments',
            'translations',
        ]);

        return new TermResource($term);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'glossary_id' => ['required', 'integer', 'exists:glossaries,id'],
            'field_id' => ['required', 'integer', 'exists:fields,id'],
        ]);

        $term = Term::create([
            'glossary_id' => $validated['glossary_id'],
            'field_id' => $validated['field_id'],
            'created_by' => $request->user()->id,
        ]);

        $term->load([
            'glossary.translations.language',
            'glossary.languagePair.sourceLanguage',
            'glossary.languagePair.targetLanguage',
            'field.fieldGroup',
            'creator.profile',
            'translations.language',
            'translations.termReferences.reference',
            'comments.user',
        ])->loadCount([
            'comments',
            'translations',
        ]);

        return (new TermResource($term))
            ->additional([
                'message' => 'Term created successfully.',
            ])
            ->response()
            ->setStatusCode(201);
    }

    public function update(Request $request, Term $term): JsonResponse
    {
        $validated = $request->validate([
            'glossary_id' => ['sometimes', 'integer', 'exists:glossaries,id'],
            'field_id' => ['sometimes', 'integer', 'exists:fields,id'],
        ]);

        $term->update($validated);

        $term->load([
            'glossary.translations.language',
            'glossary.languagePair.sourceLanguage',
            'glossary.languagePair.targetLanguage',
            'field.fieldGroup',
            'creator.profile',
            'translations.language',
            'translations.termReferences.reference',
            'comments.user',
        ])->loadCount([
            'comments',
            'translations',
        ]);

        return (new TermResource($term))
            ->additional([
                'message' => 'Term updated successfully.',
            ])
            ->response();
    }

    public function destroy(Term $term): JsonResponse
    {
        $term->delete();

        return response()->json([
            'message' => 'Term deleted successfully.',
        ]);
    }
}
