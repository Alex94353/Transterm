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
            'roles.permissions',
        ]);

        $this->authorize('view', $user);

        return response()->json([
            'user' => $user,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $this->authorize('update', $user);

        $validated = $request->validate([
            'username' => ['sometimes', 'string', 'max:255', 'unique:users,username,' . $user->id],
            'email' => ['sometimes', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'name' => ['sometimes', 'string', 'max:255'],
            'surname' => ['nullable', 'string', 'max:255'],
            'language' => ['nullable', 'string', 'max:50'],
            'visible' => ['sometimes', 'boolean'],
            'bio' => ['nullable', 'string'],
            'about' => ['nullable', 'string'],
        ]);

        $userData = $validated;
        unset($userData['bio'], $userData['about']);

        if ($userData !== []) {
            $user->update($userData);
        }

        if (array_key_exists('bio', $validated) || array_key_exists('about', $validated)) {
            $about = array_key_exists('bio', $validated)
                ? $validated['bio']
                : $validated['about'];

            $user->profile()->updateOrCreate(
                ['user_id' => $user->id],
                ['about' => $about]
            );
        }

        return response()->json([
            'message' => 'Profile updated successfully.',
            'user' => $user->fresh()->load([
                'profile.country',
                'roles.permissions',
            ]),
        ]);
    }
}
