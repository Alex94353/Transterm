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
            ->with([
                'role.permissions',
                'profile',
                'country',
            ]);

        if ($request->filled('id')) {
            $query->where('id', $request->integer('id'));
        }

        if ($request->filled('role_id')) {
            $query->where('role_id', $request->integer('role_id'));
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

            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('surname', 'like', "%{$search}%");
            });
        }

        $users = $query->orderByDesc('id')
            ->paginate($request->integer('per_page', 10))
            ->withQueryString();

        return response()->json($users);
    }

    public function show(User $user): JsonResponse
    {
        $user->load([
            'role.permissions',
            'profile',
            'country',
        ]);

        return response()->json([
            'user' => $user,
        ]);
    }

    public function update(Request $request, User $user): JsonResponse
    {
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

        $user->update($validated);

        $user->load([
            'role.permissions',
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
                'role.permissions',
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
                'role.permissions',
                'profile',
                'country',
            ]),
        ]);
    }
}
