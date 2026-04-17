<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserManagementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::query()
            ->withCount([
                'ownedGlossaries as owned_glossaries_count' => function ($q) {
                    $q->withTrashed();
                },
                'createdTerms as created_terms_count' => function ($q) {
                    $q->withTrashed();
                },
            ])
            ->with([
                'roles:id,name',
            ]);

        if ($request->filled('id')) {
            $query->where('id', $request->integer('id'));
        }

        if ($request->filled('role_id')) {
            $roleId = $request->integer('role_id');
            $query->whereHas('roles', function ($q) use ($roleId) {
                $q->where('roles.id', $roleId);
            });
        }

        if ($request->filled('activated')) {
            $query->where(
                'activated',
                filter_var($request->input('activated'), FILTER_VALIDATE_BOOLEAN)
            );
        }

        if ($request->filled('banned')) {
            $query->where(
                'banned',
                filter_var($request->input('banned'), FILTER_VALIDATE_BOOLEAN)
            );
        }

        if ($request->filled('visible')) {
            $query->where(
                'visible',
                filter_var($request->input('visible'), FILTER_VALIDATE_BOOLEAN)
            );
        }

        if ($request->filled('country_id')) {
            $query->where('country_id', $request->integer('country_id'));
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $searchLower = mb_strtolower($search);
            $searchEscaped = addcslashes($searchLower, '%_\\');

            $prefixPattern = $searchEscaped.'%';
            $wordPrefixPattern = '% '.$searchEscaped.'%';
            $containsPattern = '%'.$searchEscaped.'%';

            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('surname', 'like', "%{$search}%");
            });

            $query->orderByRaw(
                "CASE
                    WHEN LOWER(username) LIKE ? OR LOWER(email) LIKE ? OR LOWER(name) LIKE ? OR LOWER(surname) LIKE ? THEN 0
                    WHEN LOWER(name) LIKE ? OR LOWER(surname) LIKE ? THEN 1
                    ELSE 2
                END",
                [$prefixPattern, $prefixPattern, $prefixPattern, $prefixPattern, $wordPrefixPattern, $wordPrefixPattern]
            )->orderByRaw(
                "CASE
                    WHEN LOWER(username) LIKE ? OR LOWER(email) LIKE ? OR LOWER(name) LIKE ? OR LOWER(surname) LIKE ? THEN 0
                    ELSE 1
                END",
                [$containsPattern, $containsPattern, $containsPattern, $containsPattern]
            );
        }

        $idOrder = strtolower((string) $request->input('id_order', 'desc'));
        $idOrder = in_array($idOrder, ['asc', 'desc'], true) ? $idOrder : 'desc';

        $users = $query->orderBy('id', $idOrder)
            ->paginate($request->integer('per_page', 10))
            ->withQueryString();

        return response()->json($users);
    }

    public function show(User $user): JsonResponse
    {
        $user->loadCount([
            'ownedGlossaries as owned_glossaries_count' => function ($q) {
                $q->withTrashed();
            },
            'createdTerms as created_terms_count' => function ($q) {
                $q->withTrashed();
            },
        ])->load([
            'roles:id,name',
            'profile',
            'country',
        ]);

        return response()->json([
            'user' => $user,
        ]);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        if ((int) $request->user()->id === (int) $user->id && $request->has('activated')) {
            return response()->json([
                'message' => 'You cannot change your own activation status.',
            ], 422);
        }

        $validated = $request->validate([
            'username' => ['sometimes', 'string', 'max:255', 'unique:users,username,' . $user->id],
            'email' => ['sometimes', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'name' => ['sometimes', 'string', 'max:255'],
            'surname' => ['nullable', 'string', 'max:255'],
            'language' => ['nullable', 'string', 'max:50'],
            'activated' => ['sometimes', 'boolean'],
            'visible' => ['sometimes', 'boolean'],
            'country_id' => ['nullable', 'integer', 'exists:countries,id'],
        ]);

        if (array_key_exists('email', $validated)) {
            $validated['email'] = User::normalizeEmail((string) $validated['email']);
        }

        $user->update($validated);
        $baseRoleTransition = $this->synchronizeBaseRoleWithEmail($user);

        $auditMetadata = [
            'changed_fields' => array_keys($validated),
        ];
        if ($baseRoleTransition !== null) {
            $auditMetadata['base_role_adjusted'] = $baseRoleTransition;
        }

        AuditLogger::log(
            $request,
            $request->user(),
            'admin.user.updated',
            $user,
            $user,
            $auditMetadata
        );

        $user->load([
            'roles:id,name',
            'profile',
            'country',
        ]);

        return response()->json([
            'message' => 'User updated successfully.',
            'user' => $user,
        ]);
    }

    public function setBaseRole(Request $request, User $user): JsonResponse
    {
        if ((int) $request->user()->id === (int) $user->id) {
            return response()->json([
                'message' => 'You cannot change your own base role.',
            ], 422);
        }

        $validated = $request->validate([
            'base_role' => ['required', 'string', 'in:User,Student,Teacher'],
        ]);

        $previousBaseRole = $this->resolveBaseRoleName($user);
        $baseRole = $validated['base_role'];

        if (! $user->supportsBaseRole($baseRole)) {
            return response()->json([
                'message' => 'Selected base role is not allowed for this email domain.',
            ], 422);
        }

        $user->removeRole('User');
        $user->removeRole('Student');
        $user->removeRole('Teacher');
        $user->assignRole($baseRole);

        if ($baseRole === 'User' && $user->hasRole('Editor')) {
            $user->removeRole('Editor');
        }

        AuditLogger::log(
            $request,
            $request->user(),
            'admin.user.base-role.updated',
            $user,
            $user,
            [
                'old_base_role' => $previousBaseRole,
                'new_base_role' => $baseRole,
            ]
        );

        return response()->json([
            'message' => 'Base role updated successfully.',
            'user' => $user->fresh([
                'roles:id,name',
                'profile',
                'country',
            ]),
        ]);
    }

    public function grantEditor(User $user): JsonResponse
    {
        $hasEligibleBaseRole = ($user->hasRole('Student') && $user->supportsStudentRoleByEmail())
            || ($user->hasRole('Teacher') && $user->supportsTeacherRoleByEmail());

        if (! $hasEligibleBaseRole) {
            return response()->json([
                'message' => 'Editor role can be assigned only to Student or Teacher accounts.',
            ], 422);
        }

        if ($user->hasRole('Editor')) {
            return response()->json([
                'message' => 'User already has Editor role.',
            ], 422);
        }

        $user->assignRole('Editor');
        AuditLogger::log(
            request(),
            request()->user(),
            'admin.user.editor.granted',
            $user,
            $user,
            [
                'base_role' => $this->resolveBaseRoleName($user),
            ]
        );

        return response()->json([
            'message' => 'Editor role granted successfully.',
            'user' => $user->fresh([
                'roles:id,name',
                'profile',
                'country',
            ]),
        ]);
    }

    public function revokeEditor(User $user): JsonResponse
    {
        if (! $user->hasRole('Editor')) {
            return response()->json([
                'message' => 'User does not have Editor role.',
            ], 422);
        }

        $user->removeRole('Editor');
        AuditLogger::log(
            request(),
            request()->user(),
            'admin.user.editor.revoked',
            $user,
            $user,
            [
                'base_role' => $this->resolveBaseRoleName($user),
            ]
        );

        return response()->json([
            'message' => 'Editor role revoked successfully.',
            'user' => $user->fresh([
                'roles:id,name',
                'profile',
                'country',
            ]),
        ]);
    }

    public function ban(Request $request, User $user): JsonResponse
    {
        if ((int) $request->user()->id === (int) $user->id) {
            return response()->json([
                'message' => 'You cannot ban your own account.',
            ], 422);
        }

        $validated = $request->validate([
            'ban_reason' => ['nullable', 'string', 'max:255'],
        ]);

        $user->update([
            'banned' => true,
            'ban_reason' => $validated['ban_reason'] ?? null,
        ]);
        AuditLogger::log(
            $request,
            $request->user(),
            'admin.user.banned',
            $user,
            $user,
            [
                'ban_reason' => $validated['ban_reason'] ?? null,
            ]
        );

        return response()->json([
            'message' => 'User banned successfully.',
            'user' => $user->fresh([
                'roles:id,name',
                'profile',
                'country',
            ]),
        ]);
    }

    public function unban(User $user): JsonResponse
    {
        $previousBanReason = $user->ban_reason;
        $user->update([
            'banned' => false,
            'ban_reason' => null,
        ]);
        AuditLogger::log(
            request(),
            request()->user(),
            'admin.user.unbanned',
            $user,
            $user,
            [
                'previous_ban_reason' => $previousBanReason,
            ]
        );

        return response()->json([
            'message' => 'User unbanned successfully.',
            'user' => $user->fresh([
                'roles:id,name',
                'profile',
                'country',
            ]),
        ]);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        if ((int) $request->user()->id === (int) $user->id) {
            return response()->json([
                'message' => 'You cannot delete your own account.',
            ], 422);
        }

        $user->loadCount([
            'ownedGlossaries as owned_glossaries_count' => function ($q) {
                $q->withTrashed();
            },
            'createdTerms as created_terms_count' => function ($q) {
                $q->withTrashed();
            },
        ]);

        $ownedGlossariesCount = (int) ($user->owned_glossaries_count ?? 0);
        $createdTermsCount = (int) ($user->created_terms_count ?? 0);

        if ($ownedGlossariesCount > 0 || $createdTermsCount > 0) {
            return response()->json([
                'message' => "Cannot delete user with owned content (glossaries: {$ownedGlossariesCount}, terms: {$createdTermsCount}). Reassign or delete the content first.",
            ], 422);
        }

        AuditLogger::log(
            $request,
            $request->user(),
            'admin.user.deleted',
            $user,
            $user,
            [
                'roles' => $user->roles()->pluck('name')->values()->all(),
            ]
        );

        $user->tokens()->delete();
        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully.',
        ]);
    }

    private function resolveBaseRoleName(User $user): ?string
    {
        $roleNames = $user->roles()->pluck('name');

        foreach (User::BASE_ROLE_NAMES as $baseRoleName) {
            if ($roleNames->contains($baseRoleName)) {
                return $baseRoleName;
            }
        }

        return null;
    }

    /**
     * @return array{old_base_role: string|null, new_base_role: string}|null
     */
    private function synchronizeBaseRoleWithEmail(User $user): ?array
    {
        $previousBaseRole = $this->resolveBaseRoleName($user);
        $resolvedBaseRole = User::resolveBaseRoleByEmail((string) $user->email);

        if ($previousBaseRole === $resolvedBaseRole) {
            return null;
        }

        foreach (User::BASE_ROLE_NAMES as $baseRoleName) {
            if ($user->hasRole($baseRoleName)) {
                $user->removeRole($baseRoleName);
            }
        }

        $user->assignRole($resolvedBaseRole);

        if ($resolvedBaseRole === 'User' && $user->hasRole('Editor')) {
            $user->removeRole('Editor');
        }

        return [
            'old_base_role' => $previousBaseRole,
            'new_base_role' => $resolvedBaseRole,
        ];
    }
}
