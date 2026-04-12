<?php

namespace Tests\Feature\Api\Admin;

use App\Models\Field;
use App\Models\FieldGroup;
use App\Models\Glossary;
use App\Models\Language;
use App\Models\LanguagePair;
use App\Models\Term;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminFieldControllerTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;
    protected string $seeder = DatabaseSeeder::class;

    public function test_index_filters_sorting_and_show_include_relationship_counts(): void
    {
        $this->actingAsAdmin();

        $groupA = FieldGroup::query()->create([
            'name' => 'Technology',
            'code' => 'TECH',
        ]);
        $groupB = FieldGroup::query()->create([
            'name' => 'Medicine',
            'code' => 'MED',
        ]);

        $fieldA = Field::query()->create([
            'field_group_id' => $groupA->id,
            'name' => 'Software Engineering',
            'code' => 'SOFT',
        ]);
        $fieldB = Field::query()->create([
            'field_group_id' => $groupB->id,
            'name' => 'Cardiology',
            'code' => 'CARD',
        ]);

        $owner = $this->createActiveUser('field_show_owner');
        $this->createGlossaryAndTermForField($fieldA, $owner);

        $this->getJson("/api/editor/fields?field_group_id={$groupA->id}&code=SOFT&search=ware&id_order=asc&per_page=50")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $fieldA->id)
            ->assertJsonPath('data.0.field_group_id', $groupA->id);

        $this->getJson('/api/editor/fields?id_order=invalid&per_page=50')
            ->assertOk()
            ->assertJsonPath('data.0.id', max($fieldA->id, $fieldB->id));

        $this->getJson("/api/editor/fields/{$fieldA->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $fieldA->id)
            ->assertJsonPath('data.field_group.id', $groupA->id)
            ->assertJsonPath('data.glossaries_count', 1)
            ->assertJsonPath('data.terms_count', 1);
    }

    public function test_store_update_and_validation_flow(): void
    {
        $this->actingAsAdmin();

        $groupA = FieldGroup::query()->create([
            'name' => 'Business',
            'code' => 'BUS',
        ]);
        $groupB = FieldGroup::query()->create([
            'name' => 'Finance',
            'code' => 'FIN',
        ]);

        $this->postJson('/api/admin/fields', [
            'name' => 'Invalid field',
            'code' => 'INV',
        ])->assertStatus(422)
            ->assertJsonValidationErrors('field_group_id');

        $createResponse = $this->postJson('/api/admin/fields', [
            'field_group_id' => $groupA->id,
            'name' => 'Accounting',
            'code' => 'ACC',
        ])->assertCreated()
            ->assertJsonPath('message', 'Field created successfully.')
            ->assertJsonPath('data.field_group_id', $groupA->id)
            ->assertJsonPath('data.name', 'Accounting');

        $fieldId = $createResponse->json('data.id');

        $this->putJson("/api/admin/fields/{$fieldId}", [
            'field_group_id' => $groupB->id,
            'name' => 'Corporate Finance',
            'code' => 'CFIN',
        ])->assertOk()
            ->assertJsonPath('message', 'Field updated successfully.')
            ->assertJsonPath('data.id', $fieldId)
            ->assertJsonPath('data.field_group_id', $groupB->id)
            ->assertJsonPath('data.name', 'Corporate Finance')
            ->assertJsonPath('data.code', 'CFIN');
    }

    public function test_destroy_blocks_field_in_use_and_deletes_free_field(): void
    {
        $this->actingAsAdmin();

        $group = FieldGroup::query()->create([
            'name' => 'Science',
            'code' => 'SCI',
        ]);
        $inUse = Field::query()->create([
            'field_group_id' => $group->id,
            'name' => 'Biology',
            'code' => 'BIO',
        ]);
        $free = Field::query()->create([
            'field_group_id' => $group->id,
            'name' => 'Astronomy',
            'code' => 'ASTRO',
        ]);

        $owner = $this->createActiveUser('field_delete_owner');
        $this->createGlossaryAndTermForField($inUse, $owner);

        $this->deleteJson("/api/admin/fields/{$inUse->id}")
            ->assertStatus(422)
            ->assertJsonPath(
                'message',
                'Cannot delete field in use (1 glossaries, 1 terms). Reassign related data first.'
            );

        $this->deleteJson("/api/admin/fields/{$free->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Field deleted successfully.');

        $this->assertDatabaseMissing('fields', ['id' => $free->id]);
    }

    private function createGlossaryAndTermForField(Field $field, User $owner): void
    {
        $source = Language::query()->create([
            'name' => 'English',
            'code' => 'en' . random_int(100, 999),
        ]);
        $target = Language::query()->create([
            'name' => 'Slovak',
            'code' => 'sk' . random_int(100, 999),
        ]);
        $pair = LanguagePair::query()->create([
            'source_language_id' => $source->id,
            'target_language_id' => $target->id,
        ]);

        $glossary = Glossary::query()->create([
            'owner_id' => $owner->id,
            'language_pair_id' => $pair->id,
            'field_id' => $field->id,
            'approved' => true,
            'is_public' => true,
        ]);

        Term::query()->create([
            'glossary_id' => $glossary->id,
            'field_id' => $field->id,
            'created_by' => $owner->id,
        ]);
    }

    private function actingAsAdmin(): User
    {
        $admin = $this->createActiveUser('field_admin');
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
