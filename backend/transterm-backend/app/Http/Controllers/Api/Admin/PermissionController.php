<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Permission::query()
            ->with([
                'roles.parent',
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

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where('name', 'like', "%{$search}%");
        }

        $permissions = $query->orderByDesc('id')
            ->paginate($request->integer('per_page', 10))
            ->withQueryString();

        return response()->json($permissions);
    }

    public function show(Permission $permission): JsonResponse
    {
        $permission->load([
            'roles.parent',
        ]);

        return response()->json([
            'permission' => $permission,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'guard_name' => ['sometimes', 'string', 'max:255'],
            'role_ids' => ['sometimes', 'array'],
            'role_ids.*' => ['integer', 'exists:roles,id'],
        ]);

        $guardName = $validated['guard_name'] ?? 'web';

        $exists = Permission::query()
            ->where('name', $validated['name'])
            ->where('guard_name', $guardName)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Permission with this name and guard already exists.',
            ], 422);
        }

        $permission = Permission::create([
            'name' => $validated['name'],
            'guard_name' => $guardName,
        ]);

        if (! empty($validated['role_ids'])) {
            $roles = Role::query()
                ->whereIn('id', $validated['role_ids'])
                ->get();

            $permission->syncRoles($roles);
        }

        $permission->load([
            'roles.parent',
        ]);

        return response()->json([
            'message' => 'Permission created successfully.',
            'permission' => $permission,
        ], 201);
    }

    public function update(Request $request, Permission $permission): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'guard_name' => ['sometimes', 'string', 'max:255'],
            'role_ids' => ['sometimes', 'array'],
            'role_ids.*' => ['integer', 'exists:roles,id'],
        ]);

        $nextName = $validated['name'] ?? $permission->name;
        $nextGuardName = $validated['guard_name'] ?? $permission->guard_name;

        $exists = Permission::query()
            ->where('name', $nextName)
            ->where('guard_name', $nextGuardName)
            ->where('id', '!=', $permission->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Permission with this name and guard already exists.',
            ], 422);
        }

        $permission->update([
            'name' => $nextName,
            'guard_name' => $nextGuardName,
        ]);

        if (array_key_exists('role_ids', $validated)) {
            $roles = Role::query()
                ->whereIn('id', $validated['role_ids'] ?? [])
                ->get();

            $permission->syncRoles($roles);
        }

        $permission->load([
            'roles.parent',
        ]);

        return response()->json([
            'message' => 'Permission updated successfully.',
            'permission' => $permission,
        ]);
    }

    public function destroy(Permission $permission): JsonResponse
    {
        $permission->delete();

        return response()->json([
            'message' => 'Permission deleted successfully.',
        ]);
    }
}
