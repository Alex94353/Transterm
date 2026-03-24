<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Permission::query()
            ->with([
                'role.parent',
            ]);

        if ($request->filled('id')) {
            $query->where('id', $request->integer('id'));
        }

        if ($request->filled('role_id')) {
            $query->where('role_id', $request->integer('role_id'));
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where('data', 'like', "%{$search}%");
        }

        $permissions = $query->orderByDesc('id')
            ->paginate($request->integer('per_page', 10))
            ->withQueryString();

        return response()->json($permissions);
    }

    public function show(Permission $permission): JsonResponse
    {
        $permission->load([
            'role.parent',
        ]);

        return response()->json([
            'permission' => $permission,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'data' => ['nullable', 'array'],
        ]);

        $permission = Permission::create([
            'role_id' => $validated['role_id'],
            'data' => $validated['data'] ?? null,
        ]);

        $permission->load([
            'role.parent',
        ]);

        return response()->json([
            'message' => 'Permission created successfully.',
            'permission' => $permission,
        ], 201);
    }

    public function update(Request $request, Permission $permission): JsonResponse
    {
        $validated = $request->validate([
            'role_id' => ['sometimes', 'integer', 'exists:roles,id'],
            'data' => ['nullable', 'array'],
        ]);

        $permission->update($validated);

        $permission->load([
            'role.parent',
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
