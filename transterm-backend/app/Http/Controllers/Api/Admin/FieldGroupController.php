<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\FieldGroupCollection;
use App\Http\Resources\FieldGroupResource;
use App\Models\FieldGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FieldGroupController extends Controller
{
    public function index(Request $request): FieldGroupCollection
    {
        $query = FieldGroup::query()
            ->withCount([
                'fields',
            ]);

        if ($request->filled('id')) {
            $query->where('id', $request->integer('id'));
        }

        if ($request->filled('code')) {
            $query->where('code', 'like', '%' . trim((string) $request->input('code')) . '%');
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $idOrder = strtolower((string) $request->input('id_order', 'desc'));
        $idOrder = in_array($idOrder, ['asc', 'desc'], true) ? $idOrder : 'desc';

        return new FieldGroupCollection(
            $query->orderBy('id', $idOrder)
                ->paginate($request->integer('per_page', 10))
                ->withQueryString()
        );
    }

    public function show(FieldGroup $fieldGroup): FieldGroupResource
    {
        $fieldGroup->loadCount([
            'fields',
        ]);

        return new FieldGroupResource($fieldGroup);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:255'],
        ]);

        $fieldGroup = FieldGroup::create([
            'name' => $validated['name'],
            'code' => $validated['code'],
        ]);

        $fieldGroup->loadCount([
            'fields',
        ]);

        return (new FieldGroupResource($fieldGroup))
            ->additional([
                'message' => 'Field group created successfully.',
            ])
            ->response()
            ->setStatusCode(201);
    }

    public function update(Request $request, FieldGroup $fieldGroup): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'code' => ['sometimes', 'string', 'max:255'],
        ]);

        $fieldGroup->update($validated);

        $fieldGroup->loadCount([
            'fields',
        ]);

        return (new FieldGroupResource($fieldGroup))
            ->additional([
                'message' => 'Field group updated successfully.',
            ])
            ->response();
    }

    public function destroy(FieldGroup $fieldGroup): JsonResponse
    {
        if ($fieldGroup->fields()->exists()) {
            return response()->json([
                'message' => 'Cannot delete field group with existing fields. Move or delete fields first.',
            ], 422);
        }

        $fieldGroup->delete();

        return response()->json([
            'message' => 'Field group deleted successfully.',
        ]);
    }
}
