<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\LanguageCollection;
use App\Http\Resources\LanguageResource;
use App\Models\Language;
use Illuminate\Http\JsonResponse;
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
            $query->where('code', 'like', '%' . trim((string) $request->input('code')) . '%');
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

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:10', 'unique:languages,code'],
            'flag_path' => ['nullable', 'string', 'max:255'],
        ]);

        $language = Language::create($validated);

        return (new LanguageResource($language))
            ->additional([
                'message' => 'Language created successfully.',
            ])
            ->response()
            ->setStatusCode(201);
    }

    public function update(Request $request, Language $language): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'code' => ['sometimes', 'string', 'max:10', 'unique:languages,code,' . $language->id],
            'flag_path' => ['nullable', 'string', 'max:255'],
        ]);

        $language->update($validated);

        return (new LanguageResource($language))
            ->additional([
                'message' => 'Language updated successfully.',
            ])
            ->response();
    }

    public function destroy(Language $language): JsonResponse
    {
        $language->delete();

        return response()->json([
            'message' => 'Language deleted successfully.',
        ]);
    }
}
