<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Resources\GlossaryResource;
use App\Models\Glossary;
use App\Support\ApiListCache;
use App\Support\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GlossaryApprovalController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $teacher = $request->user();

        if (! $teacher->can('admin.access') && ! $teacher->supportsTeacherRoleByEmail()) {
            return response()->json([
                'message' => 'Teacher tools are available only for @ukf.sk teacher accounts.',
            ], 403);
        }

        $teacherCanApproveOwnGlossaries = $teacher->hasRole('Teacher') && $teacher->supportsTeacherRoleByEmail();

        $query = Glossary::query()
            ->with([
                'owner:id,name,surname,email',
                'owner.roles:id,name',
                'translations:id,glossary_id,title',
            ])
            ->where(function ($q) use ($teacher, $teacherCanApproveOwnGlossaries) {
                $q->whereHas('owner', function ($ownerQuery) {
                    $ownerQuery->whereRaw('LOWER(email) LIKE ?', ['%@student.ukf.sk'])
                        ->whereHas('roles', function ($roleQuery) {
                            $roleQuery->where('name', 'Student');
                        });
                });

                if ($teacherCanApproveOwnGlossaries) {
                    $q->orWhere('owner_id', $teacher->id);
                }
            });

        $status = strtolower((string) $request->input('status', 'pending'));
        if ($status === 'pending') {
            $query->where('approved', false);
        } elseif ($status === 'approved') {
            $query->where('approved', true);
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $searchTerms = preg_split('/\s+/', $search) ?: [];

            $query->where(function ($q) use ($search, $searchTerms) {
                $q->whereHas('translations', function ($translationQuery) use ($search) {
                    $translationQuery->where('title', 'like', '%' . $search . '%');
                })->orWhereHas('owner', function ($ownerQuery) use ($search, $searchTerms) {
                    $ownerQuery->where('name', 'like', '%' . $search . '%')
                        ->orWhere('surname', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%');

                    if (count($searchTerms) > 1) {
                        $ownerQuery->orWhere(function ($fullNameQuery) use ($searchTerms) {
                            foreach ($searchTerms as $term) {
                                $fullNameQuery->where(function ($termQuery) use ($term) {
                                    $termQuery->where('name', 'like', '%' . $term . '%')
                                        ->orWhere('surname', 'like', '%' . $term . '%');
                                });
                            }
                        });
                    }
                });
            });
        }

        $paginator = $query
            ->orderByDesc('id')
            ->paginate($this->resolvePerPage($request))
            ->withQueryString();

        $paginator->through(fn (Glossary $glossary) => $this->toListItem($glossary));

        return response()->json($paginator);
    }

    public function show(Request $request, Glossary $glossary): JsonResponse
    {
        $this->authorize('approve', $glossary);

        $glossary->load([
            'owner:id,name,surname,email',
            'owner.roles:id,name',
            'translations:id,glossary_id,language_id,title,description',
            'translations.language:id,name,code',
            'languagePair:id,source_language_id,target_language_id',
            'languagePair.sourceLanguage:id,name,code',
            'languagePair.targetLanguage:id,name,code',
            'field:id,name,code',
        ])->loadCount([
            'terms',
        ]);

        $owner = $glossary->owner;
        $ownerFullName = trim(implode(' ', array_filter([
            $owner?->name,
            $owner?->surname,
        ])));
        $ownerRole = $owner?->roles
            ? collect(['Student', 'Teacher', 'User'])->first(
                fn (string $roleName) => $owner->roles->pluck('name')->contains($roleName)
            )
            : null;

        return response()->json([
            'glossary' => [
                'id' => $glossary->id,
                'approved' => (bool) $glossary->approved,
                'status' => $glossary->approved ? 'approved' : 'pending',
                'created_at' => $glossary->created_at,
                'updated_at' => $glossary->updated_at,
                'approved_at' => $glossary->approved_at,
                'terms_count' => $glossary->terms_count,
                'owner' => [
                    'id' => $owner?->id,
                    'name' => $owner?->name,
                    'surname' => $owner?->surname,
                    'full_name' => $ownerFullName !== '' ? $ownerFullName : null,
                    'email' => $owner?->email,
                    'base_role' => $ownerRole,
                ],
                'field' => [
                    'id' => $glossary->field?->id,
                    'name' => $glossary->field?->name,
                    'code' => $glossary->field?->code,
                ],
                'language_pair' => [
                    'id' => $glossary->languagePair?->id,
                    'source_language' => $glossary->languagePair?->sourceLanguage
                        ? [
                            'id' => $glossary->languagePair->sourceLanguage->id,
                            'name' => $glossary->languagePair->sourceLanguage->name,
                            'code' => $glossary->languagePair->sourceLanguage->code,
                        ]
                        : null,
                    'target_language' => $glossary->languagePair?->targetLanguage
                        ? [
                            'id' => $glossary->languagePair->targetLanguage->id,
                            'name' => $glossary->languagePair->targetLanguage->name,
                            'code' => $glossary->languagePair->targetLanguage->code,
                        ]
                        : null,
                ],
                'translations' => $glossary->translations->map(function ($translation) {
                    return [
                        'id' => $translation->id,
                        'language' => $translation->language
                            ? [
                                'id' => $translation->language->id,
                                'name' => $translation->language->name,
                                'code' => $translation->language->code,
                            ]
                            : null,
                        'title' => $translation->title,
                        'description' => $translation->description,
                    ];
                })->values(),
            ],
        ]);
    }

    public function approve(Request $request, Glossary $glossary): JsonResponse
    {
        $this->authorize('approve', $glossary);

        if ($glossary->approved) {
            return response()->json([
                'message' => 'Glossary is already approved.',
            ], 422);
        }

        $glossary->update([
            'approved' => true,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);
        AuditLogger::log(
            $request,
            $request->user(),
            'teacher.glossary.approved',
            $glossary,
            $glossary->owner,
            [
                'owner_id' => $glossary->owner_id,
            ]
        );

        $glossary->load([
            'languagePair:id,source_language_id,target_language_id',
            'languagePair.sourceLanguage:id,name,code',
            'languagePair.targetLanguage:id,name,code',
            'field:id,name,code,field_group_id',
            'field.fieldGroup:id,name,code',
            'owner:id,name,surname,email',
            'translations:id,glossary_id,language_id,title,description',
            'translations.language:id,name,code',
        ])->loadCount([
            'terms',
        ]);

        ApiListCache::bumpGlossaryAndTermVersions();

        return (new GlossaryResource($glossary))
            ->additional([
                'message' => 'Glossary approved successfully.',
            ])
            ->response();
    }

    private function resolvePerPage(Request $request): int
    {
        $perPage = $request->integer('per_page', 10);

        return min(max($perPage, 5), 20);
    }

    /**
     * @return array<string, mixed>
     */
    private function toListItem(Glossary $glossary): array
    {
        $owner = $glossary->owner;
        $ownerFullName = trim(implode(' ', array_filter([
            $owner?->name,
            $owner?->surname,
        ])));

        $title = $glossary->translations
            ->sortBy('id')
            ->pluck('title')
            ->map(static fn ($value) => is_string($value) ? trim($value) : null)
            ->first(static fn (?string $value) => $value !== null && $value !== '');

        return [
            'id' => $glossary->id,
            'title' => $title,
            'status' => $glossary->approved ? 'approved' : 'pending',
            'approved' => (bool) $glossary->approved,
            'created_at' => $glossary->created_at,
            'owner' => [
                'id' => $owner?->id,
                'full_name' => $ownerFullName !== '' ? $ownerFullName : null,
                'name' => $owner?->name,
                'surname' => $owner?->surname,
                'email' => $owner?->email,
            ],
        ];
    }
}
