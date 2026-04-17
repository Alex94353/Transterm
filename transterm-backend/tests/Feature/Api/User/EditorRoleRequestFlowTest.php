<?php

namespace Tests\Feature\Api\User;

use App\Models\EditorRoleRequest;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EditorRoleRequestFlowTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;
    protected string $seeder = DatabaseSeeder::class;

    public function test_user_can_create_request_and_fetch_latest_one(): void
    {
        $user = User::factory()->create([
            'email' => 'editor_request_user@student.ukf.sk',
            'username' => 'editor_request_user',
            'activated' => true,
            'banned' => false,
            'email_verified_at' => now(),
        ]);
        $user->assignRole('Student');
        Sanctum::actingAs($user);

        EditorRoleRequest::query()->create([
            'user_id' => $user->id,
            'requested_role' => 'Editor',
            'requester_role_name' => 'Student',
            'status' => 'rejected',
            'request_message' => 'old request',
        ]);

        $createResponse = $this->postJson('/api/user/editor-role-requests', [
            'message' => 'I can help moderate terminology quality.',
        ])->assertCreated()
            ->assertJsonPath('message', 'Editor role request submitted successfully.')
            ->assertJsonPath('request.status', 'pending')
            ->assertJsonPath('request.requested_role', 'Editor')
            ->assertJsonPath('request.requester_role_name', 'Student');

        $latestRequestId = $createResponse->json('request.id');

        $this->getJson('/api/user/editor-role-requests/latest')
            ->assertOk()
            ->assertJsonPath('request.id', $latestRequestId)
            ->assertJsonPath('request.status', 'pending');
    }

    public function test_student_cannot_create_second_pending_request(): void
    {
        $user = User::factory()->create([
            'email' => 'duplicate_request@student.ukf.sk',
            'username' => 'duplicate_request_user',
            'activated' => true,
            'banned' => false,
            'email_verified_at' => now(),
        ]);
        $user->assignRole('Student');
        Sanctum::actingAs($user);

        EditorRoleRequest::query()->create([
            'user_id' => $user->id,
            'requested_role' => 'Editor',
            'requester_role_name' => 'Student',
            'status' => 'pending',
            'request_message' => 'already waiting',
        ]);

        $this->postJson('/api/user/editor-role-requests', [
            'message' => 'another try',
        ])->assertStatus(422)
            ->assertJsonPath('message', 'You already have a pending editor role request.');
    }

    public function test_admin_or_editor_cannot_request_editor_role(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin_request_block@local.test',
            'username' => 'admin_request_block',
            'activated' => true,
            'banned' => false,
            'email_verified_at' => now(),
        ]);
        $admin->assignRole('Admin');
        Sanctum::actingAs($admin);

        $this->postJson('/api/user/editor-role-requests', [
            'message' => 'should be blocked',
        ])->assertStatus(422)
            ->assertJsonPath('message', 'Your account already has management access.');
    }

    public function test_only_student_or_teacher_roles_can_request_editor_role(): void
    {
        $externalUser = User::factory()->create([
            'email' => 'external_request@gmail.com',
            'username' => 'external_request_user',
            'activated' => true,
            'banned' => false,
            'email_verified_at' => now(),
        ]);
        $externalUser->assignRole('User');
        Sanctum::actingAs($externalUser);

        $this->postJson('/api/user/editor-role-requests', [
            'message' => 'external user request',
        ])->assertStatus(422)
            ->assertJsonPath('message', 'Only Student or Teacher accounts can request Editor role.');
    }

    public function test_teacher_can_request_editor_role(): void
    {
        $teacher = User::factory()->create([
            'email' => 'teacher_editor_request@ukf.sk',
            'username' => 'teacher_editor_request',
            'activated' => true,
            'banned' => false,
            'email_verified_at' => now(),
        ]);
        $teacher->assignRole('Teacher');
        Sanctum::actingAs($teacher);

        $this->postJson('/api/user/editor-role-requests', [
            'message' => 'teacher request',
        ])->assertCreated()
            ->assertJsonPath('request.requester_role_name', 'Teacher')
            ->assertJsonPath('request.status', 'pending');
    }

    public function test_student_role_with_non_student_domain_cannot_request_editor_role(): void
    {
        $student = User::factory()->create([
            'email' => 'invalid_student_domain@teacher.sk',
            'username' => 'invalid_student_domain',
            'activated' => true,
            'banned' => false,
            'email_verified_at' => now(),
        ]);
        $student->assignRole('Student');
        Sanctum::actingAs($student);

        $this->postJson('/api/user/editor-role-requests', [
            'message' => 'should be blocked by domain policy',
        ])->assertStatus(422)
            ->assertJsonPath('message', 'Only Student or Teacher accounts can request Editor role.');
    }

    public function test_custom_roles_cannot_request_editor_role(): void
    {
        $reviewerRole = Role::query()->firstOrCreate([
            'name' => 'Reviewer',
            'guard_name' => 'web',
        ]);

        $reviewer = User::factory()->create([
            'email' => 'reviewer_request_block@student.ukf.sk',
            'username' => 'reviewer_request_block',
            'activated' => true,
            'banned' => false,
            'email_verified_at' => now(),
        ]);
        $reviewer->assignRole($reviewerRole);
        Sanctum::actingAs($reviewer);

        $this->postJson('/api/user/editor-role-requests', [
            'message' => 'request from unsupported role',
        ])->assertStatus(422)
            ->assertJsonPath('message', 'Only Student or Teacher accounts can request Editor role.');
    }
}
