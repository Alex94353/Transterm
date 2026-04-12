<?php

namespace Tests\Feature\Api\Auth;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthLoginSessionTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;
    protected string $seeder = DatabaseSeeder::class;

    public function test_login_accepts_email_and_returns_access_token(): void
    {
        $user = User::factory()->create([
            'email' => 'login_email@student.ukf.sk',
            'username' => 'login_email_user',
            'activated' => true,
            'banned' => false,
            'email_verified_at' => now(),
        ]);
        $user->assignRole('Student');

        $this->postJson('/api/auth/login', [
            'login' => ' LOGIN_EMAIL@STUDENT.UKF.SK ',
            'password' => 'password',
        ])->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('can_access_management', false);
    }

    public function test_login_accepts_username(): void
    {
        $user = User::factory()->create([
            'email' => 'login_username@student.ukf.sk',
            'username' => 'login_by_username',
            'activated' => true,
            'banned' => false,
            'email_verified_at' => now(),
        ]);
        $user->assignRole('Student');

        $this->postJson('/api/auth/login', [
            'login' => 'login_by_username',
            'password' => 'password',
        ])->assertOk()
            ->assertJsonPath('user.id', $user->id);
    }

    public function test_login_rejects_inactive_user(): void
    {
        User::factory()->create([
            'email' => 'inactive_login@student.ukf.sk',
            'username' => 'inactive_login_user',
            'activated' => false,
            'banned' => false,
            'email_verified_at' => now(),
        ]);

        $this->postJson('/api/auth/login', [
            'login' => 'inactive_login@student.ukf.sk',
            'password' => 'password',
        ])->assertStatus(422)
            ->assertJsonValidationErrors('login')
            ->assertJsonPath('errors.login.0', 'Your account is not activated.');
    }

    public function test_login_rejects_banned_user(): void
    {
        User::factory()->create([
            'email' => 'banned_login@student.ukf.sk',
            'username' => 'banned_login_user',
            'activated' => true,
            'banned' => true,
            'email_verified_at' => now(),
        ]);

        $this->postJson('/api/auth/login', [
            'login' => 'banned_login@student.ukf.sk',
            'password' => 'password',
        ])->assertStatus(422)
            ->assertJsonValidationErrors('login')
            ->assertJsonPath('errors.login.0', 'Your account is banned.');
    }

    public function test_login_rejects_non_academic_non_admin_account(): void
    {
        $user = User::factory()->create([
            'email' => 'local_user@local.test',
            'username' => 'local_user_login',
            'activated' => true,
            'banned' => false,
            'email_verified_at' => now(),
        ]);
        $user->assignRole('Student');

        $this->postJson('/api/auth/login', [
            'login' => 'local_user@local.test',
            'password' => 'password',
        ])->assertStatus(422)
            ->assertJsonValidationErrors('login')
            ->assertJsonPath('errors.login.0', 'Only @student.ukf.sk and @ukf.sk accounts can sign in.');
    }

    public function test_login_allows_non_academic_admin_account(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin_local@local.test',
            'username' => 'admin_local_login',
            'activated' => true,
            'banned' => false,
            'email_verified_at' => now(),
        ]);
        $admin->assignRole('Admin');

        $this->postJson('/api/auth/login', [
            'login' => 'admin_local@local.test',
            'password' => 'password',
        ])->assertOk()
            ->assertJsonPath('user.id', $admin->id)
            ->assertJsonPath('can_access_management', true);
    }

    public function test_me_endpoint_returns_authenticated_user_with_management_flag(): void
    {
        $editor = User::factory()->create([
            'email' => 'me_editor@ukf.sk',
            'username' => 'me_editor_user',
            'activated' => true,
            'banned' => false,
            'email_verified_at' => now(),
        ]);
        $editor->assignRole('Editor');
        Sanctum::actingAs($editor);

        $this->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('user.id', $editor->id)
            ->assertJsonPath('can_access_management', true);
    }

    public function test_logout_revokes_current_access_token_only(): void
    {
        $user = User::factory()->create([
            'email' => 'logout_user@student.ukf.sk',
            'username' => 'logout_user',
            'activated' => true,
            'banned' => false,
            'email_verified_at' => now(),
        ]);
        $user->assignRole('Student');

        $firstToken = $user->createToken('first')->plainTextToken;
        $secondToken = $user->createToken('second')->plainTextToken;
        $firstTokenId = (int) Str::before($firstToken, '|');
        $secondTokenId = (int) Str::before($secondToken, '|');

        $this->assertDatabaseCount('personal_access_tokens', 2);

        $this->withToken($firstToken)
            ->postJson('/api/auth/logout')
            ->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $firstTokenId]);
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $secondTokenId]);

        $this->withToken($secondToken)
            ->getJson('/api/auth/me')
            ->assertOk();
    }
}
