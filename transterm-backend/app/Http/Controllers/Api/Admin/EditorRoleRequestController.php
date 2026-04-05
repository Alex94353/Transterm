<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\EditorRoleRequest;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EditorRoleRequestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = EditorRoleRequest::query()
            ->with([
                'user:id,name,username,email,activated,banned',
                'user.roles:id,name',
                'reviewer:id,name,username,email',
            ]);

        if ($request->filled('status')) {
            $status = strtolower((string) $request->input('status'));
            if (in_array($status, ['pending', 'approved', 'rejected'], true)) {
                $query->where('status', $status);
            }
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $idOrder = strtolower((string) $request->input('id_order', 'desc'));
        $idOrder = in_array($idOrder, ['asc', 'desc'], true) ? $idOrder : 'desc';

        $requests = $query->orderBy('id', $idOrder)
            ->paginate($request->integer('per_page', 10))
            ->withQueryString();

        return response()->json($requests);
    }

    public function approve(Request $request, EditorRoleRequest $editorRoleRequest): JsonResponse
    {
        if ($editorRoleRequest->status !== 'pending') {
            return response()->json([
                'message' => 'Only pending requests can be approved.',
            ], 422);
        }

        $user = $editorRoleRequest->user;
        if (! $user) {
            return response()->json([
                'message' => 'Request owner was not found.',
            ], 422);
        }

        $editorRole = Role::query()
            ->where('name', 'Editor')
            ->where('guard_name', 'web')
            ->first();

        if (! $editorRole) {
            return response()->json([
                'message' => 'Editor role does not exist. Run migrations first.',
            ], 422);
        }

        if (! $user->hasRole('Editor')) {
            $user->assignRole($editorRole);
        }

        $editorRoleRequest->update([
            'status' => 'approved',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'review_note' => null,
        ]);

        // Only one active request is needed; close duplicates after approval.
        EditorRoleRequest::query()
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->where('id', '!=', $editorRoleRequest->id)
            ->update([
                'status' => 'rejected',
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'review_note' => 'Closed automatically after approval.',
            ]);

        return response()->json([
            'message' => 'Editor role granted successfully.',
            'request' => $editorRoleRequest->fresh([
                'user:id,name,username,email,activated,banned',
                'user.roles:id,name',
                'reviewer:id,name,username,email',
            ]),
        ]);
    }

    public function reject(Request $request, EditorRoleRequest $editorRoleRequest): JsonResponse
    {
        if ($editorRoleRequest->status !== 'pending') {
            return response()->json([
                'message' => 'Only pending requests can be rejected.',
            ], 422);
        }

        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $editorRoleRequest->update([
            'status' => 'rejected',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'review_note' => $validated['note'] ?? null,
        ]);

        return response()->json([
            'message' => 'Editor role request rejected.',
            'request' => $editorRoleRequest->fresh([
                'user:id,name,username,email,activated,banned',
                'user.roles:id,name',
                'reviewer:id,name,username,email',
            ]),
        ]);
    }
}
