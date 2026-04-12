<?php

namespace Tests\Feature\Api\Admin;

use App\Models\Field;
use App\Models\FieldGroup;
use App\Models\Glossary;
use App\Models\GlossaryTranslation;
use App\Models\Language;
use App\Models\LanguagePair;
use App\Models\Term;
use App\Models\TermTranslation;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminLanguageControllerTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;
    protected string $seeder = DatabaseSeeder::class;

    public function test_index_applies_filters_search_and_sorting(): void
    {
        $this->actingAsAdmin();

        $english = Language::query()->create([
            'name' => 'English',
            'code' => 'en',
        ]);
        $german = Language::query()->create([
            'name' => 'German',
            'code' => 'de',
        ]);

        $this->getJson('/api/admin/languages?code=en&search=lish&id_order=asc&per_page=50')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $english->id)
            ->assertJsonPath('data.0.code', 'en');

        $this->getJson("/api/admin/languages?id={$german->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $german->id);

        $this->getJson('/api/admin/languages?id_order=invalid&per_page=50')
            ->assertOk()
            ->assertJsonPath('data.0.id', max($english->id, $german->id));
    }

    public function test_show_returns_language_with_relationship_counts(): void
    {
        $this->actingAsAdmin();

        $language = Language::query()->create([
            'name' => 'English',
            'code' => 'en',
        ]);
        $target = Language::query()->create([
            'name' => 'Slovak',
            'code' => 'sk',
        ]);
        $third = Language::query()->create([
            'name' => 'German',
            'code' => 'de',
        ]);

        $pairAsSource = LanguagePair::query()->create([
            'source_language_id' => $language->id,
            'target_language_id' => $target->id,
        ]);
        LanguagePair::query()->create([
            'source_language_id' => $third->id,
            'target_language_id' => $language->id,
        ]);

        $group = FieldGroup::query()->create([
            'name' => 'Computing',
            'code' => 'COMP',
        ]);
        $field = Field::query()->create([
            'field_group_id' => $group->id,
            'name' => 'Software',
            'code' => 'SW',
        ]);
        $owner = $this->createActiveUser('language_show_owner');

        $glossary = Glossary::query()->create([
            'owner_id' => $owner->id,
            'language_pair_id' => $pairAsSource->id,
            'field_id' => $field->id,
            'approved' => true,
            'is_public' => true,
        ]);
        GlossaryTranslation::query()->create([
            'glossary_id' => $glossary->id,
            'language_id' => $language->id,
            'title' => 'Glossary title',
        ]);

        $term = Term::query()->create([
            'glossary_id' => $glossary->id,
            'field_id' => $field->id,
            'created_by' => $owner->id,
        ]);
        TermTranslation::query()->create([
            'term_id' => $term->id,
            'language_id' => $language->id,
            'title' => 'Term title',
        ]);

        $this->getJson("/api/admin/languages/{$language->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $language->id)
            ->assertJsonPath('data.source_pairs_count', 1)
            ->assertJsonPath('data.target_pairs_count', 1)
            ->assertJsonPath('data.glossary_translations_count', 1)
            ->assertJsonPath('data.term_translations_count', 1);
    }

    public function test_store_update_and_validation_flow(): void
    {
        $this->actingAsAdmin();

        $createResponse = $this->postJson('/api/admin/languages', [
            'name' => 'French',
            'code' => 'fr',
            'flag_path' => '/flags/fr.svg',
        ])->assertCreated()
            ->assertJsonPath('message', 'Language created successfully.')
            ->assertJsonPath('data.name', 'French')
            ->assertJsonPath('data.code', 'fr');

        $languageId = $createResponse->json('data.id');

        $this->postJson('/api/admin/languages', [
            'name' => 'French Duplicate',
            'code' => 'fr',
        ])->assertStatus(422)
            ->assertJsonValidationErrors('code');

        $this->putJson("/api/admin/languages/{$languageId}", [
            'name' => 'Canadian French',
            'code' => 'fr-ca',
            'flag_path' => null,
        ])->assertOk()
            ->assertJsonPath('message', 'Language updated successfully.')
            ->assertJsonPath('data.id', $languageId)
            ->assertJsonPath('data.name', 'Canadian French')
            ->assertJsonPath('data.code', 'fr-ca')
            ->assertJsonPath('data.flag_path', null);
    }

    public function test_destroy_blocks_language_in_use_and_deletes_free_language(): void
    {
        $this->actingAsAdmin();

        $inUse = Language::query()->create([
            'name' => 'English',
            'code' => 'en',
        ]);
        $target = Language::query()->create([
            'name' => 'Slovak',
            'code' => 'sk',
        ]);
        $third = Language::query()->create([
            'name' => 'German',
            'code' => 'de',
        ]);

        $pairAsSource = LanguagePair::query()->create([
            'source_language_id' => $inUse->id,
            'target_language_id' => $target->id,
        ]);
        LanguagePair::query()->create([
            'source_language_id' => $third->id,
            'target_language_id' => $inUse->id,
        ]);

        $group = FieldGroup::query()->create([
            'name' => 'Science',
            'code' => 'SCI',
        ]);
        $field = Field::query()->create([
            'field_group_id' => $group->id,
            'name' => 'Biology',
            'code' => 'BIO',
        ]);
        $owner = $this->createActiveUser('language_delete_owner');

        $glossary = Glossary::query()->create([
            'owner_id' => $owner->id,
            'language_pair_id' => $pairAsSource->id,
            'field_id' => $field->id,
            'approved' => true,
            'is_public' => true,
        ]);
        GlossaryTranslation::query()->create([
            'glossary_id' => $glossary->id,
            'language_id' => $inUse->id,
            'title' => 'In-use glossary translation',
        ]);
        $term = Term::query()->create([
            'glossary_id' => $glossary->id,
            'field_id' => $field->id,
            'created_by' => $owner->id,
        ]);
        TermTranslation::query()->create([
            'term_id' => $term->id,
            'language_id' => $inUse->id,
            'title' => 'In-use term translation',
        ]);

        $this->deleteJson("/api/admin/languages/{$inUse->id}")
            ->assertStatus(422)
            ->assertJsonPath(
                'message',
                'Cannot delete language in use (source pairs: 1, target pairs: 1, glossary translations: 1, term translations: 1).'
            );

        $freeLanguage = Language::query()->create([
            'name' => 'Italian',
            'code' => 'it',
        ]);

        $this->deleteJson("/api/admin/languages/{$freeLanguage->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Language deleted successfully.');

        $this->assertDatabaseMissing('languages', ['id' => $freeLanguage->id]);
    }

    private function actingAsAdmin(): User
    {
        $admin = $this->createActiveUser('language_admin');
        $admin->syncRoles([]);
        $admin->assignRole('Admin');
        Sanctum::actingAs($admin);

        return $admin;
    }

    private function createActiveUser(string $prefix): User
    {
        $suffix = $this->uniqueSuffix();

        $user = User::factory()->create([
            'email' => "{$prefix}_{$suffix}@ukf.sk",
            'username' => "{$prefix}_{$suffix}",
            'activated' => true,
            'banned' => false,
            'email_verified_at' => now(),
        ]);
        $user->assignRole('Student');

        return $user;
    }

    private function uniqueSuffix(): string
    {
        return str_replace('.', '', (string) microtime(true)) . random_int(100, 999);
    }
}
