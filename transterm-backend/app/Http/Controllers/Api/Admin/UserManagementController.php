<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserManagementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::query()
            ->withCount([
                'ownedGlossaries',
                'createdTerms',
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
            'ownedGlossaries',
            'createdTerms',
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
        if ((int) $request->user()->id === (int) $user->id && (
            $request->has('activated') || $request->has('role_id')
        )) {
            return response()->json([
                'message' => 'You cannot change your own activation status or role.',
            ], 422);
        }

        $validated = $request->validate([
            'role_id' => ['sometimes', 'integer', 'exists:roles,id'],
            'username' => ['sometimes', 'string', 'max:255', 'unique:users,username,' . $user->id],
            'email' => ['sometimes', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'name' => ['sometimes', 'string', 'max:255'],
            'surname' => ['nullable', 'string', 'max:255'],
            'language' => ['nullable', 'string', 'max:50'],
            'activated' => ['sometimes', 'boolean'],
            'visible' => ['sometimes', 'boolean'],
            'country_id' => ['nullable', 'integer', 'exists:countries,id'],
        ]);

        $roleId = $validated['role_id'] ?? null;
        unset($validated['role_id']);

        $user->update($validated);

        if ($roleId !== null) {
            $user->syncRoles([$roleId]);
        }

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
        $user->update([
            'banned' => false,
            'ban_reason' => null,
        ]);

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
            'ownedGlossaries',
            'createdTerms',
        ]);

        $ownedGlossariesCount = (int) ($user->owned_glossaries_count ?? 0);
        $createdTermsCount = (int) ($user->created_terms_count ?? 0);

        if ($ownedGlossariesCount > 0 || $createdTermsCount > 0) {
            return response()->json([
                'message' => "Cannot delete user with owned content (glossaries: {$ownedGlossariesCount}, terms: {$createdTermsCount}). Reassign or delete the content first.",
            ], 422);
        }

        $user->tokens()->delete();
        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully.',
        ]);
    }
}
