<?php

namespace Tests\Feature\Api\Admin;

use App\Models\EditorRoleRequest;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminEditorRoleRequestControllerTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;
    protected string $seeder = DatabaseSeeder::class;

    private function actingAsAdmin(): User
    {
        $admin = User::factory()->create([
            'email' => 'editor_requests_admin@local.test',
            'username' => 'editor_requests_admin',
            'activated' => true,
            'banned' => false,
            'email_verified_at' => now(),
        ]);
        $admin->assignRole('Admin');
        Sanctum::actingAs($admin);

        return $admin;
    }

    public function test_index_supports_status_search_and_order_filters(): void
    {
        $this->actingAsAdmin();

        $anna = User::factory()->create([
            'email' => 'anna_request@student.ukf.sk',
            'username' => 'anna_request',
            'name' => 'Anna Query',
            'activated' => true,
            'banned' => false,
            'email_verified_at' => now(),
        ]);
        $anna->assignRole('Student');

        $boris = User::factory()->create([
            'email' => 'boris_request@student.ukf.sk',
            'username' => 'boris_request',
            'name' => 'Boris Query',
            'activated' => true,
            'banned' => false,
            'email_verified_at' => now(),
        ]);
        $boris->assignRole('User');

        $pending = EditorRoleRequest::query()->create([
            'user_id' => $anna->id,
            'requested_role' => 'Editor',
            'requester_role_name' => 'Student',
            'status' => 'pending',
            'request_message' => 'Please approve',
        ]);

        EditorRoleRequest::query()->create([
            'user_id' => $boris->id,
            'requested_role' => 'Editor',
            'requester_role_name' => 'User',
            'status' => 'rejected',
            'request_message' => 'Old rejected request',
        ]);

        $this->getJson('/api/admin/editor-role-requests?status=pending&search=Anna&id_order=asc&per_page=10')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.id', $pending->id)
            ->assertJsonPath('data.0.status', 'pending')
            ->assertJsonPath('data.0.user.id', $anna->id);
    }

    public function test_approve_grants_editor_role_and_closes_duplicate_pending_requests(): void
    {
        $admin = $this->actingAsAdmin();

        $requester = User::factory()->create([
            'email' => 'approve_request@student.ukf.sk',
            'username' => 'approve_request_user',
            'activated' => true,
            'banned' => false,
            'email_verified_at' => now(),
        ]);
        $requester->assignRole('Student');

        $primaryRequest = EditorRoleRequest::query()->create([
            'user_id' => $requester->id,
            'requested_role' => 'Editor',
            'requester_role_name' => 'Student',
            'status' => 'pending',
            'request_message' => 'Primary',
        ]);

        $duplicatePending = EditorRoleRequest::query()->create([
            'user_id' => $requester->id,
            'requested_role' => 'Editor',
            'requester_role_name' => 'Student',
            'status' => 'pending',
            'request_message' => 'Duplicate',
        ]);

        $this->patchJson("/api/admin/editor-role-requests/{$primaryRequest->id}/approve")
            ->assertOk()
            ->assertJsonPath('message', 'Editor role granted successfully.')
            ->assertJsonPath('request.status', 'approved')
            ->assertJsonPath('request.reviewed_by', $admin->id);

        $requester->refresh();
        $this->assertTrue($requester->hasRole('Editor'));

        $this->assertDatabaseHas('editor_role_requests', [
            'id' => $duplicatePending->id,
            'status' => 'rejected',
            'review_note' => 'Closed automatically after approval.',
        ]);
    }

    public function test_reject_sets_status_and_review_note(): void
    {
        $admin = $this->actingAsAdmin();

        $requester = User::factory()->create([
            'email' => 'reject_request@student.ukf.sk',
            'username' => 'reject_request_user',
            'activated' => true,
            'banned' => false,
            'email_verified_at' => now(),
        ]);
        $requester->assignRole('User');

        $requestRecord = EditorRoleRequest::query()->create([
            'user_id' => $requester->id,
            'requested_role' => 'Editor',
            'requester_role_name' => 'User',
            'status' => 'pending',
            'request_message' => 'Please reject me',
        ]);

        $this->patchJson("/api/admin/editor-role-requests/{$requestRecord->id}/reject", [
            'note' => 'Insufficient contribution history.',
        ])->assertOk()
            ->assertJsonPath('message', 'Editor role request rejected.')
            ->assertJsonPath('request.status', 'rejected')
            ->assertJsonPath('request.reviewed_by', $admin->id)
            ->assertJsonPath('request.review_note', 'Insufficient contribution history.');
    }

    public function test_cannot_approve_or_reject_non_pending_request(): void
    {
        $this->actingAsAdmin();

        $requester = User::factory()->create([
            'email' => 'not_pending@student.ukf.sk',
            'username' => 'not_pending_user',
            'activated' => true,
            'banned' => false,
            'email_verified_at' => now(),
        ]);
        $requester->assignRole('Student');

        $approved = EditorRoleRequest::query()->create([
            'user_id' => $requester->id,
            'requested_role' => 'Editor',
            'requester_role_name' => 'Student',
            'status' => 'approved',
            'request_message' => 'Already done',
        ]);

        $this->patchJson("/api/admin/editor-role-requests/{$approved->id}/approve")
            ->assertStatus(422)
            ->assertJsonPath('message', 'Only pending requests can be approved.');

        $this->patchJson("/api/admin/editor-role-requests/{$approved->id}/reject")
            ->assertStatus(422)
            ->assertJsonPath('message', 'Only pending requests can be rejected.');
    }

    public function test_approve_blocks_request_if_user_base_role_domain_is_invalid(): void
    {
        $this->actingAsAdmin();

        $requester = User::factory()->create([
            'email' => 'invalid_domain_requester@teacher.sk',
            'username' => 'invalid_domain_requester',
            'activated' => true,
            'banned' => false,
            'email_verified_at' => now(),
        ]);
        $requester->assignRole('Student');

        $request = EditorRoleRequest::query()->create([
            'user_id' => $requester->id,
            'requested_role' => 'Editor',
            'requester_role_name' => 'Student',
            'status' => 'pending',
            'request_message' => 'please approve',
        ]);

        $this->patchJson("/api/admin/editor-role-requests/{$request->id}/approve")
            ->assertStatus(422)
            ->assertJsonPath('message', 'Editor role can be granted only to Student or Teacher accounts.');
    }
}
