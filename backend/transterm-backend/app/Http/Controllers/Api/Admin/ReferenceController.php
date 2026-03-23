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
        $query = Reference::query()
            ->with([
                'user.country',
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

            $query->where(function ($q) use ($search) {
                $q->where('source', 'like', "%{$search}%")
                    ->orWhere('type', 'like', "%{$search}%")
                    ->orWhere('language', 'like', "%{$search}%");
            });
        }

        return new ReferenceCollection(
            $query->orderByDesc('id')
                ->paginate($request->integer('per_page', 10))
                ->withQueryString()
        );
    }

    public function show(Reference $reference): ReferenceResource
    {
        $reference->load([
            'user.country',
        ]);

        return new ReferenceResource($reference);
    }

    public function store(Request $request): JsonResponse
    {
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
        $reference->delete();

        return response()->json([
            'message' => 'Reference deleted successfully.',
        ]);
    }
}
