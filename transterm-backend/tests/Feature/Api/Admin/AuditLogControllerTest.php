<?php

namespace Tests\Feature\Api\Admin;

use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuditLogControllerTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;
    protected string $seeder = DatabaseSeeder::class;

    private function actingAsAdmin(): User
    {
        $admin = User::factory()->create([
            'email' => 'audit_admin@local.test',
            'username' => 'audit_admin',
            'activated' => true,
            'banned' => false,
            'email_verified_at' => now(),
        ]);
        $admin->assignRole('Admin');
        Sanctum::actingAs($admin);

        return $admin;
    }

    public function test_critical_operations_are_written_to_audit_log_and_listed(): void
    {
        $admin = $this->actingAsAdmin();

        $target = User::factory()->create([
            'email' => 'audit_target@student.ukf.sk',
            'username' => 'audit_target',
            'activated' => true,
            'banned' => false,
            'email_verified_at' => now(),
        ]);
        $target->assignRole('User');

        $this->patchJson("/api/admin/users/{$target->id}/base-role", [
            'base_role' => 'Student',
        ])->assertOk();

        $this->patchJson("/api/admin/users/{$target->id}/editor/grant")
            ->assertOk();

        $this->patchJson("/api/admin/users/{$target->id}/editor/revoke")
            ->assertOk();

        $this->patchJson("/api/admin/users/{$target->id}/ban", [
            'ban_reason' => 'Audit reason',
        ])->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'target_user_id' => $target->id,
            'action' => 'admin.user.base-role.updated',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'target_user_id' => $target->id,
            'action' => 'admin.user.editor.granted',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'target_user_id' => $target->id,
            'action' => 'admin.user.editor.revoked',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'target_user_id' => $target->id,
            'action' => 'admin.user.banned',
        ]);

        $response = $this->getJson('/api/admin/audit-logs?action=admin.user.editor&actor_id=' . $admin->id . '&per_page=50')
            ->assertOk();

        $actions = collect($response->json('data'))->pluck('action');
        $this->assertTrue($actions->contains('admin.user.editor.granted'));
        $this->assertTrue($actions->contains('admin.user.editor.revoked'));

        $this->assertGreaterThanOrEqual(4, AuditLog::query()->count());
    }

    public function test_teacher_critical_operations_are_written_to_audit_log_and_listed(): void
    {
        $admin = $this->actingAsAdmin();

        $target = User::factory()->create([
            'email' => 'audit_teacher_target@ukf.sk',
            'username' => 'audit_teacher_target',
            'activated' => true,
            'banned' => false,
            'email_verified_at' => now(),
        ]);
        $target->assignRole('User');

        $this->patchJson("/api/admin/users/{$target->id}/base-role", [
            'base_role' => 'Teacher',
        ])->assertOk();

        $this->patchJson("/api/admin/users/{$target->id}/editor/grant")
            ->assertOk();

        $this->patchJson("/api/admin/users/{$target->id}/editor/revoke")
            ->assertOk();

        $this->patchJson("/api/admin/users/{$target->id}/ban", [
            'ban_reason' => 'Audit reason',
        ])->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'target_user_id' => $target->id,
            'action' => 'admin.user.base-role.updated',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'target_user_id' => $target->id,
            'action' => 'admin.user.editor.granted',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'target_user_id' => $target->id,
            'action' => 'admin.user.editor.revoked',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'target_user_id' => $target->id,
            'action' => 'admin.user.banned',
        ]);

        $response = $this->getJson('/api/admin/audit-logs?action=admin.user.editor&actor_id=' . $admin->id . '&per_page=50')
            ->assertOk();

        $actions = collect($response->json('data'))->pluck('action');
        $this->assertTrue($actions->contains('admin.user.editor.granted'));
        $this->assertTrue($actions->contains('admin.user.editor.revoked'));

        $this->assertGreaterThanOrEqual(4, AuditLog::query()->count());
    }

    public function test_non_admin_cannot_access_audit_logs_endpoint(): void
    {
        $student = User::factory()->create([
            'email' => 'audit_student@student.ukf.sk',
            'username' => 'audit_student',
            'activated' => true,
            'banned' => false,
            'email_verified_at' => now(),
        ]);
        $student->assignRole('Student');

        Sanctum::actingAs($student);

        $this->getJson('/api/admin/audit-logs')
            ->assertForbidden();
    }
}
