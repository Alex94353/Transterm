<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->load([
            'profile.country',
            'role.permissions',
        ]);

        return response()->json([
            'user' => $user,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'username' => ['sometimes', 'string', 'max:255', 'unique:users,username,' . $user->id],
            'email' => ['sometimes', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'name' => ['sometimes', 'string', 'max:255'],
            'surname' => ['nullable', 'string', 'max:255'],
            'language' => ['nullable', 'string', 'max:50'],
            'visible' => ['sometimes', 'boolean'],
        ]);

        $user->update($validated);

        return response()->json([
            'message' => 'Profile updated successfully.',
            'user' => $user->fresh()->load([
                'profile.country',
                'role.permissions',
            ]),
        ]);
    }
}
