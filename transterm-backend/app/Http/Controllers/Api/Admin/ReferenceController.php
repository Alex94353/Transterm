<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReferenceCollection;
use App\Http\Resources\ReferenceResource;
use App\Models\Reference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReferenceController extends Controller
{
    public function index(Request $request): ReferenceCollection
    {
        $this->authorize('viewAny', Reference::class);

        $query = Reference::query()
            ->with([
                'user:id,username,email,name,surname',
            ]);

        if ($request->filled('id')) {
            $query->where('id', $request->integer('id'));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        if ($request->filled('type')) {
            $query->where('type', 'like', '%' . trim((string) $request->input('type')) . '%');
        }

        if ($request->filled('language')) {
            $query->where('language', 'like', '%' . trim((string) $request->input('language')) . '%');
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $searchLower = mb_strtolower($search);
            $searchEscaped = addcslashes($searchLower, '%_\\');

            $prefixPattern = $searchEscaped.'%';
            $wordPrefixPattern = '% '.$searchEscaped.'%';
            $containsPattern = '%'.$searchEscaped.'%';

            $query->where(function ($q) use ($search) {
                $q->where('source', 'like', "%{$search}%")
                    ->orWhere('type', 'like', "%{$search}%")
                    ->orWhere('language', 'like', "%{$search}%");
            });

            $query->orderByRaw(
                "CASE
                    WHEN LOWER(source) LIKE ? OR LOWER(type) LIKE ? OR LOWER(language) LIKE ? THEN 0
                    WHEN LOWER(source) LIKE ? THEN 1
                    ELSE 2
                END",
                [$prefixPattern, $prefixPattern, $prefixPattern, $wordPrefixPattern]
            )->orderByRaw(
                "CASE
                    WHEN LOWER(source) LIKE ? OR LOWER(type) LIKE ? OR LOWER(language) LIKE ? THEN 0
                    ELSE 1
                END",
                [$containsPattern, $containsPattern, $containsPattern]
            );
        }

        $idOrder = strtolower((string) $request->input('id_order', 'desc'));
        $idOrder = in_array($idOrder, ['asc', 'desc'], true) ? $idOrder : 'desc';

        return new ReferenceCollection(
            $query->orderBy('id', $idOrder)
                ->paginate($request->integer('per_page', 10))
                ->withQueryString()
        );
    }

    public function show(Reference $reference): ReferenceResource
    {
        $this->authorize('view', $reference);

        $reference->load([
            'user.country',
        ]);

        return new ReferenceResource($reference);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Reference::class);

        $validated = $request->validate([
            'source' => ['required', 'string'],
            'type' => ['nullable', 'string', 'max:255'],
            'language' => ['nullable', 'string', 'max:255'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $reference = Reference::create([
            'source' => $validated['source'],
            'type' => $validated['type'] ?? null,
            'language' => $validated['language'] ?? null,
            'user_id' => $validated['user_id'] ?? $request->user()->id,
        ]);

        $reference->load([
            'user.country',
        ]);

        return (new ReferenceResource($reference))
            ->additional([
                'message' => 'Reference created successfully.',
            ])
            ->response()
            ->setStatusCode(201);
    }

    public function update(Request $request, Reference $reference): JsonResponse
    {
        $this->authorize('update', $reference);

        $validated = $request->validate([
            'source' => ['sometimes', 'string'],
            'type' => ['nullable', 'string', 'max:255'],
            'language' => ['nullable', 'string', 'max:255'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $reference->update($validated);

        $reference->load([
            'user.country',
        ]);

        return (new ReferenceResource($reference))
            ->additional([
                'message' => 'Reference updated successfully.',
            ])
            ->response();
    }

    public function destroy(Reference $reference): JsonResponse
    {
        $this->authorize('delete', $reference);

        $reference->delete();

        return response()->json([
            'message' => 'Reference deleted successfully.',
        ]);
    }
}
