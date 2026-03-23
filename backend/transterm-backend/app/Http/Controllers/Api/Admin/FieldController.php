<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\FieldCollection;
use App\Http\Resources\FieldResource;
use App\Models\Field;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FieldController extends Controller
{
    public function index(Request $request): FieldCollection
    {
        $query = Field::query()
            ->withCount([
                'glossaries',
                'terms',
            ])
            ->with([
                'fieldGroup',
            ]);

        if ($request->filled('id')) {
            $query->where('id', $request->integer('id'));
        }

        if ($request->filled('field_group_id')) {
            $query->where('field_group_id', $request->integer('field_group_id'));
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

        return new FieldCollection(
            $query->orderBy('name')
                ->paginate($request->integer('per_page', 10))
                ->withQueryString()
        );
    }

    public function show(Field $field): FieldResource
    {
        $field->load([
            'fieldGroup',
        ])->loadCount([
            'glossaries',
            'terms',
        ]);

        return new FieldResource($field);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'field_group_id' => ['required', 'integer', 'exists:field_groups,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:255'],
        ]);

        $field = Field::create([
            'field_group_id' => $validated['field_group_id'],
            'name' => $validated['name'],
            'code' => $validated['code'],
        ]);

        $field->load([
            'fieldGroup',
        ])->loadCount([
            'glossaries',
            'terms',
        ]);

        return (new FieldResource($field))
            ->additional([
                'message' => 'Field created successfully.',
            ])
            ->response()
            ->setStatusCode(201);
    }

    public function update(Request $request, Field $field): JsonResponse
    {
        $validated = $request->validate([
            'field_group_id' => ['sometimes', 'integer', 'exists:field_groups,id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'code' => ['sometimes', 'string', 'max:255'],
        ]);

        $field->update($validated);

        $field->load([
            'fieldGroup',
        ])->loadCount([
            'glossaries',
            'terms',
        ]);

        return (new FieldResource($field))
            ->additional([
                'message' => 'Field updated successfully.',
            ])
            ->response();
    }

    public function destroy(Field $field): JsonResponse
    {
        $field->delete();

        return response()->json([
            'message' => 'Field deleted successfully.',
        ]);
    }
}
