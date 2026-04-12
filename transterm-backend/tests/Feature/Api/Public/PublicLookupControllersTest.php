<?php

namespace Tests\Feature\Api\Public;

use App\Models\Country;
use App\Models\Field;
use App\Models\FieldGroup;
use App\Models\Glossary;
use App\Models\Language;
use App\Models\LanguagePair;
use App\Models\Reference;
use App\Models\Term;
use App\Models\TermReference;
use App\Models\TermTranslation;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicLookupControllersTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;
    protected string $seeder = DatabaseSeeder::class;

    public function test_country_index_and_show_support_filters(): void
    {
        $slovakia = Country::query()->create(['name' => 'Slovakia']);
        Country::query()->create(['name' => 'Czech Republic']);

        $this->getJson('/api/countries?search=Slova&per_page=50')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $slovakia->id)
            ->assertJsonPath('data.0.name', 'Slovakia');

        $this->getJson("/api/countries?id={$slovakia->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $slovakia->id);

        $this->getJson("/api/countries/{$slovakia->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $slovakia->id)
            ->assertJsonPath('data.name', 'Slovakia');
    }

    public function test_language_index_and_show_support_id_code_and_search_filters(): void
    {
        $english = Language::query()->create([
            'name' => 'English',
            'code' => 'en',
        ]);
        Language::query()->create([
            'name' => 'Slovak',
            'code' => 'sk',
        ]);

        $this->getJson('/api/languages?code=en&per_page=50')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $english->id)
            ->assertJsonPath('data.0.code', 'en');

        $this->getJson('/api/languages?search=lish')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $english->id);

        $this->getJson("/api/languages/{$english->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $english->id)
            ->assertJsonPath('data.name', 'English');
    }

    public function test_field_group_and_field_endpoints_include_relationships_and_filters(): void
    {
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
            'code' => 'SE',
        ]);
        Field::query()->create([
            'field_group_id' => $groupB->id,
            'name' => 'Cardiology',
            'code' => 'CARD',
        ]);

        $this->getJson('/api/field-groups?code=TECH')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $groupA->id)
            ->assertJsonPath('data.0.code', 'TECH');

        $this->getJson("/api/field-groups/{$groupA->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $groupA->id)
            ->assertJsonFragment([
                'id' => $fieldA->id,
                'name' => 'Software Engineering',
            ]);

        $this->getJson("/api/fields?field_group_id={$groupA->id}&search=Software&per_page=50")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $fieldA->id)
            ->assertJsonPath('data.0.field_group_id', $groupA->id);

        $this->getJson("/api/fields/{$fieldA->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $fieldA->id)
            ->assertJsonPath('data.field_group.id', $groupA->id);
    }

    public function test_language_pair_index_and_show_support_source_and_target_filters(): void
    {
        $en = Language::query()->create(['name' => 'English', 'code' => 'en']);
        $sk = Language::query()->create(['name' => 'Slovak', 'code' => 'sk']);
        $de = Language::query()->create(['name' => 'German', 'code' => 'de']);

        $pairEnSk = LanguagePair::query()->create([
            'source_language_id' => $en->id,
            'target_language_id' => $sk->id,
        ]);
        LanguagePair::query()->create([
            'source_language_id' => $en->id,
            'target_language_id' => $de->id,
        ]);

        $this->getJson("/api/language-pairs?source_language_id={$en->id}&target_language_id={$sk->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $pairEnSk->id)
            ->assertJsonPath('data.0.source_language_id', $en->id)
            ->assertJsonPath('data.0.target_language_id', $sk->id);

        $this->getJson("/api/language-pairs/{$pairEnSk->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $pairEnSk->id)
            ->assertJsonPath('data.source_language.code', 'en')
            ->assertJsonPath('data.target_language.code', 'sk');
    }

    public function test_reference_index_and_show_support_filters_and_nested_relations(): void
    {
        $referenceOwner = $this->createActiveUser('reference_owner');
        $otherUser = $this->createActiveUser('reference_other');

        $source = Language::query()->create(['name' => 'English', 'code' => 'en']);
        $target = Language::query()->create(['name' => 'Slovak', 'code' => 'sk']);
        $pair = LanguagePair::query()->create([
            'source_language_id' => $source->id,
            'target_language_id' => $target->id,
        ]);
        $group = FieldGroup::query()->create(['name' => 'Linguistics', 'code' => 'LING']);
        $field = Field::query()->create([
            'field_group_id' => $group->id,
            'name' => 'Terminology',
            'code' => 'TERM',
        ]);
        $glossary = Glossary::query()->create([
            'owner_id' => $referenceOwner->id,
            'language_pair_id' => $pair->id,
            'field_id' => $field->id,
            'approved' => true,
            'is_public' => true,
        ]);
        $term = Term::query()->create([
            'glossary_id' => $glossary->id,
            'field_id' => $field->id,
            'created_by' => $referenceOwner->id,
        ]);
        $translation = TermTranslation::query()->create([
            'term_id' => $term->id,
            'language_id' => $source->id,
            'title' => 'Lexeme',
        ]);

        $reference = Reference::query()->create([
            'user_id' => $referenceOwner->id,
            'source' => 'Applied Linguistics Handbook',
            'type' => 'book',
            'language' => 'en',
        ]);
        Reference::query()->create([
            'user_id' => $otherUser->id,
            'source' => 'Unrelated source',
            'type' => 'article',
            'language' => 'sk',
        ]);
        TermReference::query()->create([
            'term_translation_id' => $translation->id,
            'reference_id' => $reference->id,
            'type' => 'definition',
        ]);

        $this->getJson("/api/references?user_id={$referenceOwner->id}&type=book&language=en&search=Linguistics")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $reference->id)
            ->assertJsonPath('data.0.type', 'book')
            ->assertJsonPath('data.0.term_references_count', 1);

        $this->getJson("/api/references/{$reference->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $reference->id)
            ->assertJsonPath('data.term_references_count', 1)
            ->assertJsonPath('data.term_references.0.term_translation.title', 'Lexeme');
    }

    private function createActiveUser(string $prefix): User
    {
        $suffix = $this->uniqueSuffix();

        $user = User::factory()->create([
            'email' => "{$prefix}_{$suffix}@student.ukf.sk",
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
