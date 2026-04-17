<?php

namespace Tests\Feature\Api\Editor;

use App\Models\Glossary;
use App\Models\Term;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Api\Concerns\BuildsDomainFixtures;
use Tests\TestCase;

class EditorAccessGuardTest extends TestCase
{
    use RefreshDatabase;
    use BuildsDomainFixtures;

    protected bool $seed = true;
    protected string $seeder = DatabaseSeeder::class;

    public function test_editor_api_requires_editor_or_admin_permission(): void
    {
        $student = User::factory()->create([
            'email' => 'student@test.ukf.sk',
            'username' => 'student_user',
            'activated' => true,
            'email_verified_at' => now(),
        ]);
        $student->assignRole('Student');

        Sanctum::actingAs($student);
        $this->getJson('/api/editor/terms')->assertForbidden();
    }

    public function test_editor_role_can_access_editor_api(): void
    {
        $editor = User::factory()->create([
            'email' => 'editor@ukf.sk',
            'username' => 'editor_user',
            'activated' => true,
            'email_verified_at' => now(),
        ]);
        $editor->assignRole('Editor');

        Sanctum::actingAs($editor);
        $this->getJson('/api/editor/terms')->assertOk();
    }

    public function test_editor_cannot_set_approved_flag_on_glossary_create(): void
    {
        $editor = User::factory()->create([
            'email' => 'editor-create@ukf.sk',
            'username' => 'editor_create',
            'activated' => true,
            'email_verified_at' => now(),
        ]);
        $editor->assignRole('Editor');
        Sanctum::actingAs($editor);

        $fixtures = $this->createLanguagePairAndField();

        $response = $this->postJson('/api/editor/glossaries', [
            'language_pair_id' => $fixtures['pair']->id,
            'field_id' => $fixtures['field']->id,
            'approved' => true,
            'is_public' => true,
            'translation_language_id' => $fixtures['source']->id,
            'title' => 'Editor Created Glossary',
            'description' => 'Editor payload',
        ])->assertCreated();

        $glossaryId = $response->json('data.id');
        $this->assertNotNull($glossaryId);

        $this->assertDatabaseHas('glossaries', [
            'id' => $glossaryId,
            'approved' => 0,
            'is_public' => 1,
            'owner_id' => $editor->id,
        ]);
    }

    public function test_editor_cannot_set_approved_flag_on_glossary_update(): void
    {
        $editor = User::factory()->create([
            'email' => 'editor-update@ukf.sk',
            'username' => 'editor_update',
            'activated' => true,
            'email_verified_at' => now(),
        ]);
        $editor->assignRole('Editor');
        Sanctum::actingAs($editor);

        $fixtures = $this->createLanguagePairAndField();

        $glossary = Glossary::create([
            'owner_id' => $editor->id,
            'language_pair_id' => $fixtures['pair']->id,
            'field_id' => $fixtures['field']->id,
            'approved' => false,
            'is_public' => false,
        ]);

        $this->putJson("/api/editor/glossaries/{$glossary->id}", [
            'approved' => true,
            'is_public' => true,
            'translation_language_id' => $fixtures['source']->id,
            'title' => 'Updated by editor',
            'description' => 'Updated text',
        ])->assertOk();

        $this->assertDatabaseHas('glossaries', [
            'id' => $glossary->id,
            'approved' => 0,
            'is_public' => 1,
        ]);
    }

    public function test_admin_can_set_approved_flag_on_editor_glossary_update(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@local.test',
            'username' => 'admin_user',
            'activated' => true,
            'email_verified_at' => now(),
        ]);
        $admin->assignRole('Admin');
        Sanctum::actingAs($admin);

        $fixtures = $this->createLanguagePairAndField();

        $glossary = Glossary::create([
            'owner_id' => $admin->id,
            'language_pair_id' => $fixtures['pair']->id,
            'field_id' => $fixtures['field']->id,
            'approved' => false,
            'is_public' => false,
        ]);

        $this->putJson("/api/editor/glossaries/{$glossary->id}", [
            'approved' => true,
            'is_public' => true,
            'translation_language_id' => $fixtures['source']->id,
            'title' => 'Approved by admin',
            'description' => 'Approved text',
        ])->assertOk();

        $this->assertDatabaseHas('glossaries', [
            'id' => $glossary->id,
            'approved' => 1,
            'is_public' => 1,
        ]);
    }

    public function test_editor_term_delete_is_soft_delete(): void
    {
        $editor = User::factory()->create([
            'email' => 'editor-soft-term@ukf.sk',
            'username' => 'editor_soft_term',
            'activated' => true,
            'email_verified_at' => now(),
        ]);
        $editor->assignRole('Editor');
        Sanctum::actingAs($editor);

        $fixtures = $this->createLanguagePairAndField();
        $glossary = Glossary::create([
            'owner_id' => $editor->id,
            'language_pair_id' => $fixtures['pair']->id,
            'field_id' => $fixtures['field']->id,
            'approved' => false,
            'is_public' => false,
        ]);
        $term = Term::create([
            'glossary_id' => $glossary->id,
            'field_id' => $fixtures['field']->id,
            'created_by' => $editor->id,
        ]);

        $this->deleteJson("/api/editor/terms/{$term->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Term deleted successfully.');

        $this->assertSoftDeleted('terms', [
            'id' => $term->id,
        ]);
    }

    public function test_editor_glossary_delete_is_soft_delete_when_no_active_terms(): void
    {
        $editor = User::factory()->create([
            'email' => 'editor-soft-glossary@ukf.sk',
            'username' => 'editor_soft_glossary',
            'activated' => true,
            'email_verified_at' => now(),
        ]);
        $editor->assignRole('Editor');
        Sanctum::actingAs($editor);

        $fixtures = $this->createLanguagePairAndField();
        $glossary = Glossary::create([
            'owner_id' => $editor->id,
            'language_pair_id' => $fixtures['pair']->id,
            'field_id' => $fixtures['field']->id,
            'approved' => false,
            'is_public' => false,
        ]);

        $this->deleteJson("/api/editor/glossaries/{$glossary->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Glossary deleted successfully.');

        $this->assertSoftDeleted('glossaries', [
            'id' => $glossary->id,
        ]);
    }

    public function test_editor_glossary_delete_is_blocked_when_active_terms_exist(): void
    {
        $editor = User::factory()->create([
            'email' => 'editor-blocked-glossary@ukf.sk',
            'username' => 'editor_blocked_glossary',
            'activated' => true,
            'email_verified_at' => now(),
        ]);
        $editor->assignRole('Editor');
        Sanctum::actingAs($editor);

        $fixtures = $this->createLanguagePairAndField();
        $glossary = Glossary::create([
            'owner_id' => $editor->id,
            'language_pair_id' => $fixtures['pair']->id,
            'field_id' => $fixtures['field']->id,
            'approved' => false,
            'is_public' => false,
        ]);
        Term::create([
            'glossary_id' => $glossary->id,
            'field_id' => $fixtures['field']->id,
            'created_by' => $editor->id,
        ]);

        $this->deleteJson("/api/editor/glossaries/{$glossary->id}")
            ->assertStatus(422);

        $this->assertDatabaseHas('glossaries', [
            'id' => $glossary->id,
            'deleted_at' => null,
        ]);
    }
}
