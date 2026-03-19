<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\FieldCollection;
use App\Http\Resources\FieldResource;
use App\Models\Field;
use Illuminate\Http\Request;

class FieldController extends Controller
{
    public function index(Request $request): FieldCollection
    {
        $query = Field::query()
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
            $query->where('code', $request->input('code'));
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
        ]);

        return new FieldResource($field);
    }
}
