<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\LanguageCollection;
use App\Http\Resources\LanguageResource;
use App\Models\Language;
use Illuminate\Http\Request;

class LanguageController extends Controller
{
    public function index(Request $request): LanguageCollection
    {
        $query = Language::query();

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

        return new LanguageCollection(
            $query->orderBy('name')
                ->paginate($request->integer('per_page', 10))
                ->withQueryString()
        );
    }

    public function show(Language $language): LanguageResource
    {
        return new LanguageResource($language);
    }
}
