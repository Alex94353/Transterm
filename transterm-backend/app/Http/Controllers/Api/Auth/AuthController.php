<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
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

        $this->assignInitialBaseRole($user, $normalizedEmail);
        $user->sendEmailVerificationNotification();

        return response()->json([
            'message' => 'Registration successful. Please verify your email to activate your account.',
            'user' => $user,
            'verification_email_sent' => true,
            'can_access_management' => $this->canAccessManagement($user),
        ], 201);
    }

    public function resendVerificationEmail(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
        ]);

        $normalizedEmail = mb_strtolower(trim((string) $validated['email']));

        $user = User::query()
            ->where('email', $normalizedEmail)
            ->first();

        if ($user && (! $user->hasVerifiedEmail() || ! $user->activated)) {
            $user->sendEmailVerificationNotification();
        }

        return response()->json([
            'message' => 'If the account exists and is not activated, a verification email has been sent.',
        ]);
    }

    public function verifyEmail(Request $request, string $id, string $hash): JsonResponse|RedirectResponse
    {
        $user = User::query()->find($id);

        if (! $user || ! hash_equals((string) $hash, sha1((string) $user->getEmailForVerification()))) {
            return $this->verificationResponse($request, 'invalid');
        }

        $emailVerifiedNow = false;
        $activatedNow = false;

        if (! $user->hasVerifiedEmail()) {
            $emailVerifiedNow = $user->markEmailAsVerified();
        }

        if (! $user->activated) {
            $user->forceFill(['activated' => true])->save();
            $activatedNow = true;
        }

        if ($emailVerifiedNow) {
            event(new Verified($user));
        }

        $status = ($emailVerifiedNow || $activatedNow) ? 'success' : 'already';

        return $this->verificationResponse($request, $status);
    }

    private function assignInitialBaseRole(User $user, string $email): void
    {
        $resolvedRoleName = $this->resolveAssignableBaseRoleName($email);

        if ($resolvedRoleName === null) {
            return;
        }

        $user->syncRoles([$resolvedRoleName]);
    }

    private function ensureBaseRoleIntegrity(User $user): void
    {
        $resolvedRoleName = $this->resolveAssignableBaseRoleName((string) $user->email);
        if ($resolvedRoleName === null) {
            return;
        }

        $currentBaseRoles = $user->roles()
            ->whereIn('name', User::BASE_ROLE_NAMES)
            ->pluck('name')
            ->values();

        $hasExactlyOneExpectedRole = $currentBaseRoles->count() === 1
            && $currentBaseRoles->first() === $resolvedRoleName;

        if (! $hasExactlyOneExpectedRole) {
            foreach (User::BASE_ROLE_NAMES as $baseRoleName) {
                if ($user->hasRole($baseRoleName)) {
                    $user->removeRole($baseRoleName);
                }
            }

            $user->assignRole($resolvedRoleName);
        }

        if ($resolvedRoleName === 'User' && $user->hasRole('Editor')) {
            $user->removeRole('Editor');
        }
    }

    private function resolveAssignableBaseRoleName(string $email): ?string
    {
        $preferredRoleName = User::resolveBaseRoleByEmail($email);
        $candidateRoleNames = array_values(array_unique([$preferredRoleName, 'User']));

        foreach ($candidateRoleNames as $roleName) {
            $exists = Role::query()
                ->where('name', $roleName)
                ->where('guard_name', 'web')
                ->exists();

            if ($exists) {
                return $roleName;
            }
        }

        return null;
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $loginInput = trim((string) $validated['login']);
        $normalizedLogin = mb_strtolower($loginInput);

        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$normalizedLogin])
            ->orWhere('username', $loginInput)
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

        $this->ensureBaseRoleIntegrity($user);
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
        $user = $request->user();
        $this->ensureBaseRoleIntegrity($user);

        $user->load([
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
        return $user->can('admin.access')
            || $user->can('editor.access');
    }

    private function verificationResponse(Request $request, string $status): JsonResponse|RedirectResponse
    {
        $messages = [
            'success' => 'Your account has been successfully activated.',
            'already' => 'Your account is already activated.',
            'invalid' => 'The verification link is invalid or has expired.',
        ];

        $message = $messages[$status] ?? $messages['invalid'];

        if ($request->expectsJson()) {
            return response()->json(
                [
                    'message' => $message,
                    'status' => $status,
                ],
                $status === 'invalid' ? 422 : 200
            );
        }

        $target = $this->buildFrontendVerificationUrl($status);

        return redirect()->away($target);
    }

    private function buildFrontendVerificationUrl(string $status): string
    {
        $frontendBaseUrl = rtrim((string) config('app.frontend_url', config('app.url')), '/');
        $query = http_build_query(['verification' => $status]);

        return "{$frontendBaseUrl}/login?{$query}";
    }
}
