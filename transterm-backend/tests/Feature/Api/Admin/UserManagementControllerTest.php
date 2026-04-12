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

    public function test_update_blocks_self_role_or_activation_changes(): void
    {
        $admin = $this->actingAsAdmin();
        $editorRoleId = (int) Role::query()->where('name', 'Editor')->value('id');

        $this->putJson("/api/admin/users/{$admin->id}", [
            'activated' => false,
        ])->assertStatus(422)
            ->assertJsonPath('message', 'You cannot change your own activation status or role.');

        $this->putJson("/api/admin/users/{$admin->id}", [
            'role_id' => $editorRoleId,
        ])->assertStatus(422)
            ->assertJsonPath('message', 'You cannot change your own activation status or role.');
    }

    public function test_update_other_user_changes_fields_and_role(): void
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

        $editorRoleId = (int) Role::query()->where('name', 'Editor')->value('id');

        $this->putJson("/api/admin/users/{$target->id}", [
            'name' => 'After',
            'surname' => 'Updated',
            'activated' => false,
            'visible' => true,
            'role_id' => $editorRoleId,
        ])->assertOk()
            ->assertJsonPath('message', 'User updated successfully.')
            ->assertJsonPath('user.name', 'After')
            ->assertJsonPath('user.activated', false)
            ->assertJsonPath('user.visible', true);

        $target->refresh();
        $this->assertTrue($target->hasRole('Editor'));
        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'name' => 'After',
            'surname' => 'Updated',
            'activated' => 0,
            'visible' => 1,
        ]);
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
