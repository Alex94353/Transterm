<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\EditorRoleRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EditorRoleRequestController extends Controller
{
    public function latest(Request $request): JsonResponse
    {
        $latestRequest = EditorRoleRequest::query()
            ->where('user_id', $request->user()->id)
            ->latest('id')
            ->first();

        return response()->json([
            'request' => $latestRequest,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasRole('Admin') || $user->hasRole('Editor') || $user->can('admin.access')) {
            return response()->json([
                'message' => 'Your account already has management access.',
            ], 422);
        }

        $canRequest = $user->hasRole('User') || $user->hasRole('Student');
        if (! $canRequest) {
            return response()->json([
                'message' => 'Only User or Student accounts can request Editor role.',
            ], 422);
        }

        $existingPending = EditorRoleRequest::query()
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->exists();

        if ($existingPending) {
            return response()->json([
                'message' => 'You already have a pending editor role request.',
            ], 422);
        }

        $validated = $request->validate([
            'message' => ['nullable', 'string', 'max:1000'],
        ]);

        $requestRecord = EditorRoleRequest::query()->create([
            'user_id' => $user->id,
            'requested_role' => 'Editor',
            'requester_role_name' => $user->roles()->value('name'),
            'status' => 'pending',
            'request_message' => $validated['message'] ?? null,
        ]);

        return response()->json([
            'message' => 'Editor role request submitted successfully.',
            'request' => $requestRecord,
        ], 201);
    }
}

