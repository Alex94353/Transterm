<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\GlossaryCollection;
use App\Http\Resources\GlossaryResource;
use App\Models\Glossary;
use App\Support\ApiListCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class GlossaryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Glossary::class);

        $query = Glossary::query()
            ->withCount('terms')
            ->with([
                'field:id,name,code,field_group_id',
                'translations:id,glossary_id,language_id,title,description',
                'languagePair:id,source_language_id,target_language_id',
                'languagePair.sourceLanguage:id,name,code',
                'languagePair.targetLanguage:id,name,code',
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

        $viewer = $request->user();
        $query->where(function ($q) use ($viewer) {
            $q->where(function ($publicQuery) {
                $publicQuery
                    ->where('approved', true)
                    ->where('is_public', true);
            });

            if ($viewer) {
                $q->orWhere('owner_id', $viewer->id);
            }
        });

        $buildPayload = function () use ($query, $request) {
            $collection = new GlossaryCollection(
                $query->orderByDesc('id')
                    ->paginate($this->resolvePerPage($request))
                    ->withQueryString()
            );

            return $collection->response()->getData(true);
        };

        if (! ApiListCache::enabled()) {
            return response()->json($buildPayload());
        }

        $payload = Cache::remember(
            ApiListCache::glossariesKey($request),
            now()->addSeconds(ApiListCache::ttlSeconds()),
            $buildPayload
        );

        return response()->json($payload);
    }

    public function show(Glossary $glossary): GlossaryResource
    {
        $this->authorize('view', $glossary);

        $glossary->load([
            'owner:id,name,surname,email',
            'field:id,name,code,field_group_id',
            'field.fieldGroup:id,name,code',
            'translations:id,glossary_id,language_id,title,description',
            'translations.language:id,name,code',
            'languagePair:id,source_language_id,target_language_id',
            'languagePair.sourceLanguage:id,name,code',
            'languagePair.targetLanguage:id,name,code',
        ]);

        return new GlossaryResource($glossary);
    }

    private function resolvePerPage(Request $request): int
    {
        $perPage = $request->integer('per_page', 20);

        return min(max($perPage, 5), 30);
    }
}
