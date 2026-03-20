<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReferenceCollection;
use App\Http\Resources\ReferenceResource;
use App\Models\Reference;
use Illuminate\Http\Request;

class ReferenceController extends Controller
{
    public function index(Request $request): ReferenceCollection
    {
        $query = Reference::query()
            ->withCount('termReferences')
            ->with([
                'user',
            ]);

        if ($request->filled('id')) {
            $query->where('id', $request->integer('id'));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('language')) {
            $query->where('language', $request->input('language'));
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));

            $query->where('source', 'like', "%{$search}%");
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
            'user',
            'termReferences.termTranslation.language',
        ])->loadCount([
            'termReferences',
        ]);

        return new ReferenceResource($reference);
    }
}
