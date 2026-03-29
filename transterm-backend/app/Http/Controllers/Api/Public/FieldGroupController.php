<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\FieldGroupCollection;
use App\Http\Resources\FieldGroupResource;
use App\Models\FieldGroup;
use Illuminate\Http\Request;

class FieldGroupController extends Controller
{
    public function index(Request $request): FieldGroupCollection
    {
        $query = FieldGroup::query()
            ->with([
                'fields',
            ]);

        if ($request->filled('id')) {
            $query->where('id', $request->integer('id'));
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

        return new FieldGroupCollection(
            $query->orderBy('name')
                ->paginate($request->integer('per_page', 10))
                ->withQueryString()
        );
    }

    public function show(FieldGroup $fieldGroup): FieldGroupResource
    {
        $fieldGroup->load([
            'fields',
        ]);

        return new FieldGroupResource($fieldGroup);
    }
}
