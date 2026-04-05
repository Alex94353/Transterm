<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:users,email',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! $this->isAllowedAcademicEmail((string) $value)) {
                        $fail('Only @student.ukf.sk and @ukf.sk email addresses are allowed.');
                    }
                },
            ],
            'name' => ['required', 'string', 'max:255'],
            'surname' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $normalizedEmail = mb_strtolower(trim((string) $validated['email']));

        $user = User::create([
            'username' => $validated['username'],
            'email' => $normalizedEmail,
            'name' => $validated['name'],
            'surname' => $validated['surname'] ?? null,
            'password' => Hash::make($validated['password']),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'User registered successfully.',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
            'can_access_management' => $this->canAccessManagement($user),
        ], 201);
    }

    private function isAllowedAcademicEmail(string $email): bool
    {
        $normalized = mb_strtolower(trim($email));

        return str_ends_with($normalized, '@student.ukf.sk')
            || str_ends_with($normalized, '@ukf.sk');
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()
            ->where('email', $validated['login'])
            ->orWhere('username', $validated['login'])
            ->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (! $user->activated) {
            throw ValidationException::withMessages([
                'login' => ['Your account is not activated.'],
            ]);
        }

        if ($user->banned) {
            throw ValidationException::withMessages([
                'login' => ['Your account is banned.'],
            ]);
        }

        // Allow institutional accounts for student/teacher access.
        // Keep existing admin accounts operable even if they use a local domain.
        if (! $this->isAllowedAcademicEmail((string) $user->email) && ! $user->hasRole('Admin')) {
            throw ValidationException::withMessages([
                'login' => ['Only @student.ukf.sk and @ukf.sk accounts can sign in.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful.',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
            'can_access_management' => $this->canAccessManagement($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load([
            'profile',
            'country',
            'roles.permissions',
        ]);

        return response()->json([
            'user' => $user,
            'can_access_management' => $this->canAccessManagement($user),
        ]);
    }

    private function canAccessManagement(User $user): bool
    {
        return $user->hasRole('Admin')
            || $user->hasRole('Editor')
            || $user->can('admin.access');
    }
}
