<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Role::class);

        $query = Role::query()
            ->with([
                'parent',
            ])
            ->withCount([
                'children',
                'users',
                'permissions',
            ]);

        if ($request->filled('id')) {
            $query->where('id', $request->integer('id'));
        }

        if ($request->filled('parent_id')) {
            $query->where('parent_id', $request->integer('parent_id'));
        }

        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . trim((string) $request->input('name')) . '%');
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where('name', 'like', "%{$search}%");
        }

        $roles = $query->orderBy('name')
            ->paginate($request->integer('per_page', 10))
            ->withQueryString();

        return response()->json($roles);
    }

    public function show(Role $role): JsonResponse
    {
        $this->authorize('view', $role);

        $role->load([
            'parent',
            'children',
            'permissions',
        ])->loadCount([
            'users',
        ]);

        return response()->json([
            'role' => $role,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Role::class);

        $validated = $request->validate([
            'parent_id' => ['nullable', 'integer', 'exists:roles,id'],
            'name' => ['required', 'string', 'max:30'],
            'guard_name' => ['sometimes', 'string', 'max:255'],
        ]);

        $role = Role::create([
            'parent_id' => $validated['parent_id'] ?? null,
            'name' => $validated['name'],
            'guard_name' => $validated['guard_name'] ?? 'web',
        ]);

        $role->load([
            'parent',
            'children',
            'permissions',
        ])->loadCount([
            'users',
        ]);

        return response()->json([
            'message' => 'Role created successfully.',
            'role' => $role,
        ], 201);
    }

    public function update(Request $request, Role $role): JsonResponse
    {
        $this->authorize('update', $role);

        $validated = $request->validate([
            'parent_id' => ['nullable', 'integer', 'exists:roles,id'],
            'name' => ['sometimes', 'string', 'max:30'],
            'guard_name' => ['sometimes', 'string', 'max:255'],
        ]);

        if (isset($validated['parent_id']) && (int) $validated['parent_id'] === (int) $role->id) {
            return response()->json([
                'message' => 'Role cannot be parent of itself.',
            ], 422);
        }

        $role->update($validated);

        $role->load([
            'parent',
            'children',
            'permissions',
        ])->loadCount([
            'users',
        ]);

        return response()->json([
            'message' => 'Role updated successfully.',
            'role' => $role,
        ]);
    }

    public function destroy(Role $role): JsonResponse
    {
        $this->authorize('delete', $role);

        if ($role->users()->exists()) {
            return response()->json([
                'message' => 'Role cannot be deleted because it has assigned users.',
            ], 422);
        }

        $role->delete();

        return response()->json([
            'message' => 'Role deleted successfully.',
        ]);
    }
}
