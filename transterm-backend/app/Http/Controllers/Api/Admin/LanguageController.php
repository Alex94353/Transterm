<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\LanguageCollection;
use App\Http\Resources\LanguageResource;
use App\Models\Language;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LanguageController extends Controller
{
    public function index(Request $request): LanguageCollection
    {
        $this->authorize('viewAny', Language::class);

        $query = Language::query()
            ->withCount([
                'sourcePairs',
                'targetPairs',
                'glossaryTranslations',
                'termTranslations',
            ]);

        if ($request->filled('id')) {
            $query->where('id', $request->integer('id'));
        }

        if ($request->filled('code')) {
            $query->where('code', 'like', '%' . trim((string) $request->input('code')) . '%');
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $searchLower = mb_strtolower($search);
            $searchEscaped = addcslashes($searchLower, '%_\\');

            $prefixPattern = $searchEscaped.'%';
            $wordPrefixPattern = '% '.$searchEscaped.'%';
            $containsPattern = '%'.$searchEscaped.'%';

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });

            
            $query->orderByRaw(
                "CASE
                    WHEN LOWER(name) LIKE ? OR LOWER(code) LIKE ? THEN 0
                    WHEN LOWER(name) LIKE ? THEN 1
                    ELSE 2
                END",
                [$prefixPattern, $prefixPattern, $wordPrefixPattern]
            )->orderByRaw(
                "CASE
                    WHEN LOWER(name) LIKE ? OR LOWER(code) LIKE ? THEN 0
                    ELSE 1
                END",
                [$containsPattern, $containsPattern]
            );
        }

        $idOrder = strtolower((string) $request->input('id_order', 'desc'));
        $idOrder = in_array($idOrder, ['asc', 'desc'], true) ? $idOrder : 'desc';

        return new LanguageCollection(
            $query->orderBy('id', $idOrder)
                ->paginate($request->integer('per_page', 10))
                ->withQueryString()
        );
    }

    public function show(Language $language): LanguageResource
    {
        $this->authorize('view', $language);

        $language->loadCount([
            'sourcePairs',
            'targetPairs',
            'glossaryTranslations',
            'termTranslations',
        ]);

        return new LanguageResource($language);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Language::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:10', 'unique:languages,code'],
            'flag_path' => ['nullable', 'string', 'max:255'],
        ]);

        $language = Language::create($validated);

        return (new LanguageResource($language))
            ->additional([
                'message' => 'Language created successfully.',
            ])
            ->response()
            ->setStatusCode(201);
    }

    public function update(Request $request, Language $language): JsonResponse
    {
        $this->authorize('update', $language);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'code' => ['sometimes', 'string', 'max:10', 'unique:languages,code,' . $language->id],
            'flag_path' => ['nullable', 'string', 'max:255'],
        ]);

        $language->update($validated);

        return (new LanguageResource($language))
            ->additional([
                'message' => 'Language updated successfully.',
            ])
            ->response();
    }

    public function destroy(Language $language): JsonResponse
    {
        $this->authorize('delete', $language);

        $language->loadCount([
            'sourcePairs',
            'targetPairs',
            'glossaryTranslations',
            'termTranslations',
        ]);

        $sourcePairsCount = (int) ($language->source_pairs_count ?? 0);
        $targetPairsCount = (int) ($language->target_pairs_count ?? 0);
        $glossaryTranslationsCount = (int) ($language->glossary_translations_count ?? 0);
        $termTranslationsCount = (int) ($language->term_translations_count ?? 0);

        if (
            $sourcePairsCount > 0 ||
            $targetPairsCount > 0 ||
            $glossaryTranslationsCount > 0 ||
            $termTranslationsCount > 0
        ) {
            return response()->json([
                'message' => "Cannot delete language in use (source pairs: {$sourcePairsCount}, target pairs: {$targetPairsCount}, glossary translations: {$glossaryTranslationsCount}, term translations: {$termTranslationsCount}).",
            ], 422);
        }

        $language->delete();

        return response()->json([
            'message' => 'Language deleted successfully.',
        ]);
    }
}
