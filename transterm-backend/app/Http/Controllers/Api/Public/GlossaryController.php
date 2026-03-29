<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\GlossaryCollection;
use App\Http\Resources\GlossaryResource;
use App\Models\Glossary;
use Illuminate\Http\Request;

class GlossaryController extends Controller
{
    public function index(Request $request): GlossaryCollection
    {
        $this->authorize('viewAny', Glossary::class);

        $query = Glossary::query()
            ->withCount('terms')
            ->with([
                'owner',
                'field',
                'translations.language',
                'languagePair.sourceLanguage',
                'languagePair.targetLanguage',
            ]);

        if ($request->filled('field_id')) {
            $query->where('field_id', $request->integer('field_id'));
        }

        if ($request->filled('owner_id')) {
            $query->where('owner_id', $request->integer('owner_id'));
        }

        if ($request->filled('language_pair_id')) {
            $query->where('language_pair_id', $request->integer('language_pair_id'));
        }

        if ($request->filled('approved')) {
            $query->where('approved', filter_var($request->input('approved'), FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->filled('is_public')) {
            $query->where('is_public', filter_var($request->input('is_public'), FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));

            $query->whereHas('translations', function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%");
            });
        }

        return new GlossaryCollection(
            $query->orderByDesc('id')
                ->paginate($request->integer('per_page', 10))
                ->withQueryString()
        );
    }

    public function show(Glossary $glossary): GlossaryResource
    {
        $this->authorize('view', $glossary);

        $glossary->load([
            'owner.profile',
            'field.fieldGroup',
            'translations.language',
            'languagePair.sourceLanguage',
            'languagePair.targetLanguage',
            'terms.translations.language',
        ]);

        return new GlossaryResource($glossary);
    }
}
