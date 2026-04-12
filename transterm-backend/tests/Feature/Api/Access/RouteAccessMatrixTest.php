<?php

namespace Tests\Feature\Api\Access;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RouteAccessMatrixTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;
    protected string $seeder = DatabaseSeeder::class;

    public function test_guest_cannot_access_user_editor_or_admin_routes(): void
    {
        $this->getJson('/api/user/profile')->assertUnauthorized();
        $this->getJson('/api/editor/terms')->assertUnauthorized();
        $this->getJson('/api/admin/users')->assertUnauthorized();
    }

    public function test_student_can_access_user_routes_but_not_editor_or_admin(): void
    {
        $student = User::factory()->create([
            'email' => 'matrix_student@student.ukf.sk',
            'username' => 'matrix_student',
            'activated' => true,
            'banned' => false,
            'email_verified_at' => now(),
        ]);
        $student->assignRole('Student');
        Sanctum::actingAs($student);

        $this->getJson('/api/user/profile')->assertOk();
        $this->getJson('/api/editor/terms')->assertForbidden();
        $this->getJson('/api/admin/users')->assertForbidden();
    }

    public function test_editor_can_access_editor_routes_but_not_admin_routes(): void
    {
        $editor = User::factory()->create([
            'email' => 'matrix_editor@ukf.sk',
            'username' => 'matrix_editor',
            'activated' => true,
            'banned' => false,
            'email_verified_at' => now(),
        ]);
        $editor->assignRole('Editor');
        Sanctum::actingAs($editor);

        $this->getJson('/api/user/profile')->assertOk();
        $this->getJson('/api/editor/terms')->assertOk();
        $this->getJson('/api/admin/users')->assertForbidden();
    }

    public function test_admin_can_access_user_editor_and_admin_routes(): void
    {
        $admin = User::factory()->create([
            'email' => 'matrix_admin@local.test',
            'username' => 'matrix_admin',
            'activated' => true,
            'banned' => false,
            'email_verified_at' => now(),
        ]);
        $admin->assignRole('Admin');
        Sanctum::actingAs($admin);

        $this->getJson('/api/user/profile')->assertOk();
        $this->getJson('/api/editor/terms')->assertOk();
        $this->getJson('/api/admin/users')->assertOk();
    }

    public function test_inactive_editor_is_blocked_before_editor_permission_check(): void
    {
        $inactiveEditor = User::factory()->create([
            'email' => 'matrix_inactive_editor@ukf.sk',
            'username' => 'matrix_inactive_editor',
            'activated' => false,
            'banned' => false,
            'email_verified_at' => now(),
        ]);
        $inactiveEditor->assignRole('Editor');
        Sanctum::actingAs($inactiveEditor);

        $this->getJson('/api/editor/terms')
            ->assertForbidden()
            ->assertJsonPath('message', 'Your account is not activated.');
    }

    public function test_banned_admin_is_blocked_before_admin_permission_check(): void
    {
        $bannedAdmin = User::factory()->create([
            'email' => 'matrix_banned_admin@local.test',
            'username' => 'matrix_banned_admin',
            'activated' => true,
            'banned' => true,
            'email_verified_at' => now(),
        ]);
        $bannedAdmin->assignRole('Admin');
        Sanctum::actingAs($bannedAdmin);

        $this->getJson('/api/admin/users')
            ->assertForbidden()
            ->assertJsonPath('message', 'Your account is banned.');
    }
}
