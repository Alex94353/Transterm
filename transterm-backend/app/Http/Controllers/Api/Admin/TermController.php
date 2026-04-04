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
        $this->authorize('viewAny', Term::class);

        $query = Term::query()
            ->with([
                'glossary:id,language_pair_id,field_id,owner_id,approved,is_public,created_at,updated_at',
                'glossary.translations:id,glossary_id,language_id,title,description',
                'field:id,name,code,field_group_id',
                'translations:id,term_id,language_id,title,definition,plural,context,synonym,notes',
                'translations.language:id,name,code',
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
            $searchLower = mb_strtolower($search);
            $searchEscaped = addcslashes($searchLower, '%_\\');

            $prefixPattern = $searchEscaped.'%';
            $wordPrefixPattern = '% '.$searchEscaped.'%';
            $containsPattern = '%'.$searchEscaped.'%';

            $query->whereHas('translations', function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('plural', 'like', "%{$search}%")
                    ->orWhere('definition', 'like', "%{$search}%")
                    ->orWhere('context', 'like', "%{$search}%")
                    ->orWhere('synonym', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });

            $query->orderByRaw(
                "CASE
                    WHEN EXISTS (
                        SELECT 1
                        FROM term_translations tt
                        WHERE tt.term_id = terms.id
                          AND (
                            LOWER(tt.title) LIKE ?
                            OR LOWER(tt.plural) LIKE ?
                            OR LOWER(tt.definition) LIKE ?
                            OR LOWER(tt.context) LIKE ?
                            OR LOWER(tt.synonym) LIKE ?
                            OR LOWER(tt.notes) LIKE ?
                          )
                    ) THEN 0
                    WHEN EXISTS (
                        SELECT 1
                        FROM term_translations tt
                        WHERE tt.term_id = terms.id
                          AND (
                            LOWER(tt.title) LIKE ?
                            OR LOWER(tt.definition) LIKE ?
                          )
                    ) THEN 1
                    ELSE 2
                END",
                [
                    $prefixPattern,
                    $prefixPattern,
                    $prefixPattern,
                    $prefixPattern,
                    $prefixPattern,
                    $prefixPattern,
                    $wordPrefixPattern,
                    $wordPrefixPattern,
                ]
            )->orderByRaw(
                "CASE
                    WHEN EXISTS (
                        SELECT 1
                        FROM term_translations tt
                        WHERE tt.term_id = terms.id
                          AND (
                            LOWER(tt.title) LIKE ?
                            OR LOWER(tt.plural) LIKE ?
                            OR LOWER(tt.definition) LIKE ?
                            OR LOWER(tt.context) LIKE ?
                            OR LOWER(tt.synonym) LIKE ?
                            OR LOWER(tt.notes) LIKE ?
                          )
                    ) THEN 0
                    ELSE 1
                END",
                [
                    $containsPattern,
                    $containsPattern,
                    $containsPattern,
                    $containsPattern,
                    $containsPattern,
                    $containsPattern,
                ]
            );
        }

        $idOrder = strtolower((string) $request->input('id_order', 'desc'));
        $idOrder = in_array($idOrder, ['asc', 'desc'], true) ? $idOrder : 'desc';

        return new TermCollection(
            $query->orderBy('id', $idOrder)
                ->paginate($request->integer('per_page', 10))
                ->withQueryString()
        );
    }

    public function show(Term $term): TermResource
    {
        $this->authorize('view', $term);

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
        $this->authorize('create', Term::class);

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
        $this->authorize('update', $term);

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
        $this->authorize('delete', $term);

        $term->delete();

        return response()->json([
            'message' => 'Term deleted successfully.',
        ]);
    }
}
