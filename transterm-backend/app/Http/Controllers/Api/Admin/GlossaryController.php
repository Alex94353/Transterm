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
        $this->authorize('viewAny', Glossary::class);

        $query = Glossary::query()
            ->withCount([
                'terms',
            ])
            ->with([
                'languagePair:id,source_language_id,target_language_id',
                'languagePair.sourceLanguage:id,name,code',
                'languagePair.targetLanguage:id,name,code',
                'field:id,name,code,field_group_id',
                'field.fieldGroup:id,name,code',
                'translations:id,glossary_id,language_id,title,description',
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
                        $searchLower = mb_strtolower($search);
                        $searchEscaped = addcslashes($searchLower, '%_\\');

                        $prefixPattern = $searchEscaped.'%';
                        $wordPrefixPattern = '% '.$searchEscaped.'%';
                        $containsPattern = '%'.$searchEscaped.'%';

            $query->whereHas('translations', function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });

                        $query->orderByRaw(
                                "CASE
                                        WHEN EXISTS (
                                                SELECT 1
                                                FROM glossary_translations gt
                                                WHERE gt.glossary_id = glossaries.id
                                                    AND (
                                                        LOWER(gt.title) LIKE ?
                                                        OR LOWER(gt.description) LIKE ?
                                                    )
                                        ) THEN 0
                                        WHEN EXISTS (
                                                SELECT 1
                                                FROM glossary_translations gt
                                                WHERE gt.glossary_id = glossaries.id
                                                    AND (
                                                        LOWER(gt.title) LIKE ?
                                                        OR LOWER(gt.description) LIKE ?
                                                    )
                                        ) THEN 1
                                        ELSE 2
                                END",
                                [$prefixPattern, $prefixPattern, $wordPrefixPattern, $wordPrefixPattern]
                        )->orderByRaw(
                                "CASE
                                        WHEN EXISTS (
                                                SELECT 1
                                                FROM glossary_translations gt
                                                WHERE gt.glossary_id = glossaries.id
                                                    AND (
                                                        LOWER(gt.title) LIKE ?
                                                        OR LOWER(gt.description) LIKE ?
                                                    )
                                        ) THEN 0
                                        ELSE 1
                                END",
                                [$containsPattern, $containsPattern]
                        );
        }

        $idOrder = strtolower((string) $request->input('id_order', 'desc'));
        $idOrder = in_array($idOrder, ['asc', 'desc'], true) ? $idOrder : 'desc';

        return new GlossaryCollection(
            $query->orderBy('id', $idOrder)
                ->paginate($request->integer('per_page', 10))
                ->withQueryString()
        );
    }

    public function show(Glossary $glossary): GlossaryResource
    {
        $this->authorize('view', $glossary);

        $glossary->load([
            'languagePair:id,source_language_id,target_language_id',
            'languagePair.sourceLanguage:id,name,code',
            'languagePair.targetLanguage:id,name,code',
            'field:id,name,code,field_group_id',
            'field.fieldGroup:id,name,code',
            'owner:id,name,surname,email',
            'translations:id,glossary_id,language_id,title,description',
            'translations.language:id,name,code',
        ])->loadCount([
            'terms',
        ]);

        return new GlossaryResource($glossary);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Glossary::class);

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
            'languagePair:id,source_language_id,target_language_id',
            'languagePair.sourceLanguage:id,name,code',
            'languagePair.targetLanguage:id,name,code',
            'field:id,name,code,field_group_id',
            'field.fieldGroup:id,name,code',
            'owner:id,name,surname,email',
            'translations:id,glossary_id,language_id,title,description',
            'translations.language:id,name,code',
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
        $this->authorize('update', $glossary);

        $validated = $request->validate([
            'language_pair_id' => ['sometimes', 'integer', 'exists:language_pairs,id'],
            'field_id' => ['sometimes', 'integer', 'exists:fields,id'],
            'approved' => ['sometimes', 'boolean'],
            'is_public' => ['sometimes', 'boolean'],
        ]);

        $glossary->update($validated);

        $glossary->load([
            'languagePair:id,source_language_id,target_language_id',
            'languagePair.sourceLanguage:id,name,code',
            'languagePair.targetLanguage:id,name,code',
            'field:id,name,code,field_group_id',
            'field.fieldGroup:id,name,code',
            'owner:id,name,surname,email',
            'translations:id,glossary_id,language_id,title,description',
            'translations.language:id,name,code',
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
        $this->authorize('delete', $glossary);

        $glossary->delete();

        return response()->json([
            'message' => 'Glossary deleted successfully.',
        ]);
    }
}
