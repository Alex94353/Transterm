<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\TermCollection;
use App\Http\Resources\TermResource;
use App\Models\Term;
use Illuminate\Http\Request;

class TermController extends Controller
{
    public function index(Request $request): TermCollection
    {
        $this->authorize('viewAny', Term::class);

        $query = Term::query()
            ->with([
                'glossary:id,language_pair_id,field_id,owner_id,approved,is_public,created_at,updated_at',
                'glossary.translations:id,glossary_id,language_id,title,description',
                'field:id,name,code,field_group_id',
                'translations:id,term_id,language_id,title,definition,plural,context,synonym,notes',
                'translations.language:id,name,code',
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
            $query->where('created_by', $request->integer('created_by'));
        }

        if ($request->filled('approved')) {
            $approved = filter_var($request->input('approved'), FILTER_VALIDATE_BOOLEAN);

            $query->whereHas('glossary', function ($q) use ($approved) {
                $q->where('approved', $approved);
            });
        }

        if ($request->filled('is_public')) {
            $isPublic = filter_var($request->input('is_public'), FILTER_VALIDATE_BOOLEAN);

            $query->whereHas('glossary', function ($q) use ($isPublic) {
                $q->where('is_public', $isPublic);
            });
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));

            $query->whereHas('translations', function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('plural', 'like', "%{$search}%")
                    ->orWhere('definition', 'like', "%{$search}%")
                    ->orWhere('context', 'like', "%{$search}%")
                    ->orWhere('synonym', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        $viewer = $request->user();
        $query->where(function ($q) use ($viewer) {
            $q->whereHas('glossary', function ($publicQuery) {
                $publicQuery
                    ->where('approved', true)
                    ->where('is_public', true);
            });

            if ($viewer) {
                $q->orWhere('created_by', $viewer->id);
            }
        });

        return new TermCollection(
            $query->orderByDesc('id')
                ->paginate($request->integer('per_page', 10))
                ->withQueryString()
        );
    }

    public function show(Term $term): TermResource
    {
        $this->authorize('view', $term);

        $term->load([
            'glossary.translations.language',
            'glossary.languagePair.sourceLanguage',
            'glossary.languagePair.targetLanguage',
            'field.fieldGroup',
            'creator.profile',
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
