<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TermCollection;
use App\Http\Resources\TermResource;
use App\Models\Term;
use Illuminate\Http\Request;

class TermController extends Controller
{
    public function index(Request $request): TermCollection
    {
        $query = Term::query()
            ->withCount('comments')
            ->withCount('translations')
            ->with([
                'glossary',
                'field',
                'creator',
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
            $search = trim((string) $request->input('search'));

            $query->whereHas('translations', function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%");
            });
        }

        return new TermCollection(
            $query->orderByDesc('id')
                ->paginate($request->integer('per_page', 10))
                ->withQueryString()
        );
    }

    public function show(Term $term): TermResource
    {
        $term->load([
            'glossary',
            'field',
            'creator',
            'translations.language',
            'translations.termReferences.reference',
            'comments.user',
        ])->loadCount([
            'comments',
            'translations',
        ]);

        return new TermResource($term);
    }
}
