<?php

namespace Tests\Feature\Api\User;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;
    protected string $seeder = DatabaseSeeder::class;

    public function test_show_returns_authenticated_user_profile_payload(): void
    {
        $user = User::factory()->create([
            'email' => 'profile_show@student.ukf.sk',
            'username' => 'profile_show_user',
            'name' => 'Profile',
            'surname' => 'Show',
            'activated' => true,
            'banned' => false,
            'email_verified_at' => now(),
        ]);
        $user->assignRole('Student');
        Sanctum::actingAs($user);

        $this->getJson('/api/user/profile')
            ->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.email', 'profile_show@student.ukf.sk')
            ->assertJsonPath('user.username', 'profile_show_user');
    }

    public function test_update_changes_user_fields_and_profile_about_with_bio_alias(): void
    {
        $user = User::factory()->create([
            'email' => 'profile_update@student.ukf.sk',
            'username' => 'profile_update_user',
            'name' => 'Profile',
            'surname' => 'Update',
            'language' => 'slovak',
            'visible' => false,
            'activated' => true,
            'banned' => false,
            'email_verified_at' => now(),
        ]);
        $user->assignRole('User');
        Sanctum::actingAs($user);

        $this->putJson('/api/user/profile', [
            'name' => 'Updated Name',
            'surname' => 'Updated Surname',
            'language' => 'english',
            'visible' => true,
            'bio' => 'Terminology specialist profile text',
        ])->assertOk()
            ->assertJsonPath('message', 'Profile updated successfully.')
            ->assertJsonPath('user.name', 'Updated Name')
            ->assertJsonPath('user.surname', 'Updated Surname')
            ->assertJsonPath('user.language', 'english')
            ->assertJsonPath('user.visible', true)
            ->assertJsonPath('user.profile.about', 'Terminology specialist profile text');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'surname' => 'Updated Surname',
            'language' => 'english',
            'visible' => 1,
        ]);
        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $user->id,
            'about' => 'Terminology specialist profile text',
        ]);
    }

    public function test_update_accepts_about_field_and_keeps_other_user_data_unchanged_when_not_sent(): void
    {
        $user = User::factory()->create([
            'email' => 'profile_about@student.ukf.sk',
            'username' => 'profile_about_user',
            'name' => 'Name Before',
            'surname' => 'Surname Before',
            'activated' => true,
            'banned' => false,
            'email_verified_at' => now(),
        ]);
        $user->assignRole('Student');
        Sanctum::actingAs($user);

        $this->putJson('/api/user/profile', [
            'about' => 'About field update path',
        ])->assertOk()
            ->assertJsonPath('user.name', 'Name Before')
            ->assertJsonPath('user.profile.about', 'About field update path');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Name Before',
            'surname' => 'Surname Before',
        ]);
        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $user->id,
            'about' => 'About field update path',
        ]);
    }
}
