<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LanguagePairCollection;
use App\Http\Resources\LanguagePairResource;
use App\Models\LanguagePair;
use Illuminate\Http\Request;

class LanguagePairController extends Controller
{
    public function index(Request $request): LanguagePairCollection
    {
        $query = LanguagePair::query()
            ->with([
                'sourceLanguage',
                'targetLanguage',
            ]);

        if ($request->filled('id')) {
            $query->where('id', $request->integer('id'));
        }

        if ($request->filled('source_language_id')) {
            $query->where('source_language_id', $request->integer('source_language_id'));
        }

        if ($request->filled('target_language_id')) {
            $query->where('target_language_id', $request->integer('target_language_id'));
        }

        return new LanguagePairCollection(
            $query->orderBy('id')
                ->paginate($request->integer('per_page', 10))
                ->withQueryString()
        );
    }

    public function show(LanguagePair $languagePair): LanguagePairResource
    {
        $languagePair->load([
            'sourceLanguage',
            'targetLanguage',
        ]);

        return new LanguagePairResource($languagePair);
    }
}
