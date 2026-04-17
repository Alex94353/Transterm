<?php

namespace Tests\Feature\Api\Admin;

use App\Models\Glossary;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Api\Concerns\BuildsDomainFixtures;
use Tests\TestCase;

class UserManagementControllerTest extends TestCase
{
    use RefreshDatabase;
    use BuildsDomainFixtures;

    protected bool $seed = true;
    protected string $seeder = DatabaseSeeder::class;

    private function actingAsAdmin(): User
    {
        $admin = User::factory()->create([
            'email' => 'user_mgmt_admin@local.test',
            'username' => 'user_mgmt_admin',
            'activated' => true,
            'banned' => false,
            'email_verified_at' => now(),
        ]);
        $admin->assignRole('Admin');
        Sanctum::actingAs($admin);

        return $admin;
    }

    public function test_index_applies_filters_search_and_sorting(): void
    {
        $this->actingAsAdmin();

        $editor = User::factory()->create([
            'email' => 'alice_editor@ukf.sk',
            'username' => 'alice_editor',
            'name' => 'Alice',
            'surname' => 'Editor',
            'activated' => true,
            'banned' => false,
            'visible' => true,
            'email_verified_at' => now(),
        ]);
        $editor->assignRole('Editor');

        $student = User::factory()->create([
            'email' => 'bob_student@student.ukf.sk',
            'username' => 'bob_student',
            'name' => 'Bob',
            'surname' => 'Student',
            'activated' => true,
            'banned' => false,
            'visible' => true,
            'email_verified_at' => now(),
        ]);
        $student->assignRole('Student');

        $editorRoleId = (int) Role::query()->where('name', 'Editor')->value('id');

        $this->getJson("/api/admin/users?role_id={$editorRoleId}&activated=true&banned=false&visible=true&search=Alice&id_order=asc&per_page=50")
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.id', $editor->id)
            ->assertJsonPath('data.0.name', 'Alice');
    }

    public function test_show_returns_user_with_relationship_data(): void
    {
        $this->actingAsAdmin();

        $user = User::factory()->create([
            'email' => 'show_user@student.ukf.sk',
            'username' => 'show_user',
            'activated' => true,
            'banned' => false,
            'email_verified_at' => now(),
        ]);
        $user->assignRole('User');

        $this->getJson("/api/admin/users/{$user->id}")
            ->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.username', 'show_user');
    }

    public function test_update_blocks_self_activation_changes(): void
    {
        $admin = $this->actingAsAdmin();

        $this->putJson("/api/admin/users/{$admin->id}", [
            'activated' => false,
        ])->assertStatus(422)
            ->assertJsonPath('message', 'You cannot change your own activation status.');
    }

    public function test_update_other_user_changes_fields_without_role_mutation(): void
    {
        $this->actingAsAdmin();

        $target = User::factory()->create([
            'email' => 'update_target@student.ukf.sk',
            'username' => 'update_target',
            'name' => 'Before',
            'surname' => 'Update',
            'activated' => true,
            'banned' => false,
            'visible' => false,
            'email_verified_at' => now(),
        ]);
        $target->assignRole('Student');

        $this->putJson("/api/admin/users/{$target->id}", [
            'name' => 'After',
            'surname' => 'Updated',
            'activated' => false,
            'visible' => true,
        ])->assertOk()
            ->assertJsonPath('message', 'User updated successfully.')
            ->assertJsonPath('user.name', 'After')
            ->assertJsonPath('user.activated', false)
            ->assertJsonPath('user.visible', true);

        $target->refresh();
        $this->assertTrue($target->hasRole('Student'));
        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'name' => 'After',
            'surname' => 'Updated',
            'activated' => 0,
            'visible' => 1,
        ]);
    }

    public function test_update_email_normalizes_base_role_to_match_domain(): void
    {
        $this->actingAsAdmin();

        $target = User::factory()->create([
            'email' => 'normalize_target@student.ukf.sk',
            'username' => 'normalize_target',
            'activated' => true,
            'banned' => false,
            'email_verified_at' => now(),
        ]);
        $target->assignRole('Student');
        $target->assignRole('Editor');

        $this->putJson("/api/admin/users/{$target->id}", [
            'email' => 'normalize_target@gmail.com',
        ])->assertOk()
            ->assertJsonPath('user.email', 'normalize_target@gmail.com');

        $target->refresh();
        $this->assertTrue($target->hasRole('User'));
        $this->assertFalse($target->hasRole('Student'));
        $this->assertFalse($target->hasRole('Editor'));
    }

    public function test_admin_can_change_base_role_via_dedicated_endpoint(): void
    {
        $admin = $this->actingAsAdmin();

        $target = User::factory()->create([
            'email' => 'base_role_target@ukf.sk',
            'username' => 'base_role_target',
            'activated' => true,
            'banned' => false,
            'email_verified_at' => now(),
        ]);
        $target->assignRole('User');

        $this->patchJson("/api/admin/users/{$target->id}/base-role", [
            'base_role' => 'Teacher',
        ])->assertOk()
            ->assertJsonPath('message', 'Base role updated successfully.');

        $target->refresh();
        $this->assertTrue($target->hasRole('Teacher'));
        $this->assertFalse($target->hasRole('User'));

        $invalidDomainTarget = User::factory()->create([
            'email' => 'base_role_invalid@gmail.com',
            'username' => 'base_role_invalid',
            'activated' => true,
            'banned' => false,
            'email_verified_at' => now(),
        ]);
        $invalidDomainTarget->assignRole('User');

        $this->patchJson("/api/admin/users/{$invalidDomainTarget->id}/base-role", [
            'base_role' => 'Teacher',
        ])->assertStatus(422)
            ->assertJsonPath('message', 'Selected base role is not allowed for this email domain.');

        $this->patchJson("/api/admin/users/{$admin->id}/base-role", [
            'base_role' => 'User',
        ])->assertStatus(422)
            ->assertJsonPath('message', 'You cannot change your own base role.');
    }

    public function test_admin_can_grant_and_revoke_editor_via_dedicated_endpoints(): void
    {
        $this->actingAsAdmin();

        $student = User::factory()->create([
            'email' => 'grant_editor_target@student.ukf.sk',
            'username' => 'grant_editor_target',
            'activated' => true,
            'banned' => false,
            'email_verified_at' => now(),
        ]);
        $student->assignRole('Student');

        $externalUser = User::factory()->create([
            'email' => 'external_for_editor@gmail.com',
            'username' => 'external_for_editor',
            'activated' => true,
            'banned' => false,
            'email_verified_at' => now(),
        ]);
        $externalUser->assignRole('User');

        $invalidDomainStudent = User::factory()->create([
            'email' => 'invalid_domain_student@teacher.sk',
            'username' => 'invalid_domain_student_editor',
            'activated' => true,
            'banned' => false,
            'email_verified_at' => now(),
        ]);
        $invalidDomainStudent->assignRole('Student');

        $this->patchJson("/api/admin/users/{$student->id}/editor/grant")
            ->assertOk()
            ->assertJsonPath('message', 'Editor role granted successfully.');

        $student->refresh();
        $this->assertTrue($student->hasRole('Editor'));
        $this->assertTrue($student->hasRole('Student'));

        $this->patchJson("/api/admin/users/{$externalUser->id}/editor/grant")
            ->assertStatus(422)
            ->assertJsonPath('message', 'Editor role can be assigned only to Student or Teacher accounts.');

        $this->patchJson("/api/admin/users/{$invalidDomainStudent->id}/editor/grant")
            ->assertStatus(422)
            ->assertJsonPath('message', 'Editor role can be assigned only to Student or Teacher accounts.');

        $this->patchJson("/api/admin/users/{$student->id}/editor/revoke")
            ->assertOk()
            ->assertJsonPath('message', 'Editor role revoked successfully.');

        $student->refresh();
        $this->assertFalse($student->hasRole('Editor'));

        $this->patchJson("/api/admin/users/{$student->id}/editor/revoke")
            ->assertStatus(422)
            ->assertJsonPath('message', 'User does not have Editor role.');
    }

    public function test_ban_and_unban_flow_handles_self_guard_and_reason(): void
    {
        $admin = $this->actingAsAdmin();

        $target = User::factory()->create([
            'email' => 'ban_target@student.ukf.sk',
            'username' => 'ban_target',
            'activated' => true,
            'banned' => false,
            'email_verified_at' => now(),
        ]);
        $target->assignRole('User');

        $this->patchJson("/api/admin/users/{$admin->id}/ban")
            ->assertStatus(422)
            ->assertJsonPath('message', 'You cannot ban your own account.');

        $this->patchJson("/api/admin/users/{$target->id}/ban", [
            'ban_reason' => 'Repeated abuse reports',
        ])->assertOk()
            ->assertJsonPath('message', 'User banned successfully.')
            ->assertJsonPath('user.banned', true)
            ->assertJsonPath('user.ban_reason', 'Repeated abuse reports');

        $this->patchJson("/api/admin/users/{$target->id}/unban")
            ->assertOk()
            ->assertJsonPath('message', 'User unbanned successfully.')
            ->assertJsonPath('user.banned', false)
            ->assertJsonPath('user.ban_reason', null);
    }

    public function test_destroy_blocks_self_and_user_with_owned_content(): void
    {
        $admin = $this->actingAsAdmin();

        $owner = User::factory()->create([
            'email' => 'owned_content_user@student.ukf.sk',
            'username' => 'owned_content_user',
            'activated' => true,
            'banned' => false,
            'email_verified_at' => now(),
        ]);
        $owner->assignRole('Student');

        $fixtures = $this->createLanguagePairAndField();
        Glossary::query()->create([
            'owner_id' => $owner->id,
            'language_pair_id' => $fixtures['pair']->id,
            'field_id' => $fixtures['field']->id,
            'approved' => false,
            'is_public' => false,
        ]);

        $this->deleteJson("/api/admin/users/{$admin->id}")
            ->assertStatus(422)
            ->assertJsonPath('message', 'You cannot delete your own account.');

        $this->deleteJson("/api/admin/users/{$owner->id}")
            ->assertStatus(422);
    }

    public function test_destroy_blocks_user_with_soft_deleted_owned_content(): void
    {
        $this->actingAsAdmin();

        $owner = User::factory()->create([
            'email' => 'soft_deleted_owned_content_user@student.ukf.sk',
            'username' => 'soft_deleted_owned_content_user',
            'activated' => true,
            'banned' => false,
            'email_verified_at' => now(),
        ]);
        $owner->assignRole('Student');

        $fixtures = $this->createLanguagePairAndField();
        $glossary = Glossary::query()->create([
            'owner_id' => $owner->id,
            'language_pair_id' => $fixtures['pair']->id,
            'field_id' => $fixtures['field']->id,
            'approved' => false,
            'is_public' => false,
        ]);
        $glossary->delete();

        $this->deleteJson("/api/admin/users/{$owner->id}")
            ->assertStatus(422)
            ->assertJsonPath(
                'message',
                'Cannot delete user with owned content (glossaries: 1, terms: 0). Reassign or delete the content first.'
            );
    }

    public function test_destroy_deletes_user_without_owned_content_and_revokes_tokens(): void
    {
        $this->actingAsAdmin();

        $target = User::factory()->create([
            'email' => 'delete_target@student.ukf.sk',
            'username' => 'delete_target',
            'activated' => true,
            'banned' => false,
            'email_verified_at' => now(),
        ]);
        $target->assignRole('User');
        $target->createToken('first');
        $target->createToken('second');

        $this->assertDatabaseCount('personal_access_tokens', 2);

        $this->deleteJson("/api/admin/users/{$target->id}")
            ->assertOk()
            ->assertJsonPath('message', 'User deleted successfully.');

        $this->assertDatabaseMissing('users', ['id' => $target->id]);
        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $target->id]);
    }
}
