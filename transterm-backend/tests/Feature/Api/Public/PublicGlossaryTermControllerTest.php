<?php

namespace Tests\Feature\Api\Public;

use App\Models\Comment;
use App\Models\Field;
use App\Models\FieldGroup;
use App\Models\Glossary;
use App\Models\GlossaryTranslation;
use App\Models\Language;
use App\Models\LanguagePair;
use App\Models\Reference;
use App\Models\Term;
use App\Models\TermReference;
use App\Models\TermTranslation;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PublicGlossaryTermControllerTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;
    protected string $seeder = DatabaseSeeder::class;

    public function test_glossary_index_for_guest_returns_only_public_approved_items(): void
    {
        $owner = $this->createActiveUser('glossary_owner');
        $domain = $this->createDomain();

        $publicGlossary = Glossary::query()->create([
            'owner_id' => $owner->id,
            'language_pair_id' => $domain['pair']->id,
            'field_id' => $domain['field']->id,
            'approved' => true,
            'is_public' => true,
        ]);
        GlossaryTranslation::query()->create([
            'glossary_id' => $publicGlossary->id,
            'language_id' => $domain['source']->id,
            'title' => 'Physics Glossary',
            'description' => 'Public terms',
        ]);

        $privateGlossary = Glossary::query()->create([
            'owner_id' => $owner->id,
            'language_pair_id' => $domain['pair']->id,
            'field_id' => $domain['field']->id,
            'approved' => false,
            'is_public' => false,
        ]);
        GlossaryTranslation::query()->create([
            'glossary_id' => $privateGlossary->id,
            'language_id' => $domain['source']->id,
            'title' => 'Physics Private',
            'description' => null,
        ]);

        $this->getJson('/api/glossaries?search=Physics&approved=true&is_public=true&per_page=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $publicGlossary->id)
            ->assertJsonPath('meta.per_page', 5);
    }

    public function test_glossary_index_for_authenticated_owner_includes_private_items_with_cache_enabled(): void
    {
        Cache::flush();
        config([
            'api_cache.enabled' => true,
            'api_cache.ttl' => 120,
        ]);

        $owner = $this->createActiveUser('glossary_viewer');
        $otherUser = $this->createActiveUser('glossary_other');
        $domain = $this->createDomain();

        $ownedPrivate = Glossary::query()->create([
            'owner_id' => $owner->id,
            'language_pair_id' => $domain['pair']->id,
            'field_id' => $domain['field']->id,
            'approved' => false,
            'is_public' => false,
        ]);
        GlossaryTranslation::query()->create([
            'glossary_id' => $ownedPrivate->id,
            'language_id' => $domain['source']->id,
            'title' => 'Owner Hidden Glossary',
            'description' => null,
        ]);

        Glossary::query()->create([
            'owner_id' => $otherUser->id,
            'language_pair_id' => $domain['pair']->id,
            'field_id' => $domain['field']->id,
            'approved' => false,
            'is_public' => false,
        ]);

        Sanctum::actingAs($owner);

        $this->getJson("/api/glossaries?owner_id={$owner->id}&search=Owner&per_page=100")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $ownedPrivate->id)
            ->assertJsonPath('meta.per_page', 30);
    }

    public function test_glossary_show_denies_guest_for_private_item_and_allows_owner(): void
    {
        $owner = $this->createActiveUser('glossary_show_owner');
        $domain = $this->createDomain();

        $privateGlossary = Glossary::query()->create([
            'owner_id' => $owner->id,
            'language_pair_id' => $domain['pair']->id,
            'field_id' => $domain['field']->id,
            'approved' => false,
            'is_public' => false,
        ]);
        GlossaryTranslation::query()->create([
            'glossary_id' => $privateGlossary->id,
            'language_id' => $domain['source']->id,
            'title' => 'Private Owner Glossary',
            'description' => 'Visible only for owner',
        ]);

        $this->getJson("/api/glossaries/{$privateGlossary->id}")
            ->assertForbidden();

        Sanctum::actingAs($owner);
        $this->getJson("/api/glossaries/{$privateGlossary->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $privateGlossary->id)
            ->assertJsonPath('data.owner.id', $owner->id);
    }

    public function test_term_index_for_guest_returns_only_terms_from_public_approved_glossaries(): void
    {
        $publicCreator = $this->createActiveUser('public_term_creator');
        $privateCreator = $this->createActiveUser('private_term_creator');
        $domain = $this->createDomain();

        $publicGlossary = Glossary::query()->create([
            'owner_id' => $publicCreator->id,
            'language_pair_id' => $domain['pair']->id,
            'field_id' => $domain['field']->id,
            'approved' => true,
            'is_public' => true,
        ]);
        $privateGlossary = Glossary::query()->create([
            'owner_id' => $privateCreator->id,
            'language_pair_id' => $domain['pair']->id,
            'field_id' => $domain['field']->id,
            'approved' => false,
            'is_public' => false,
        ]);

        $publicTerm = Term::query()->create([
            'glossary_id' => $publicGlossary->id,
            'field_id' => $domain['field']->id,
            'created_by' => $publicCreator->id,
        ]);
        TermTranslation::query()->create([
            'term_id' => $publicTerm->id,
            'language_id' => $domain['source']->id,
            'title' => 'Neuron',
            'definition' => 'Cell of nervous system',
        ]);

        $privateTerm = Term::query()->create([
            'glossary_id' => $privateGlossary->id,
            'field_id' => $domain['field']->id,
            'created_by' => $privateCreator->id,
        ]);
        TermTranslation::query()->create([
            'term_id' => $privateTerm->id,
            'language_id' => $domain['source']->id,
            'title' => 'Neuron hidden',
        ]);

        $this->getJson('/api/terms?search=Neuron&approved=true&is_public=true&per_page=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $publicTerm->id)
            ->assertJsonPath('meta.per_page', 5);
    }

    public function test_term_index_for_authenticated_user_includes_own_private_terms_with_cache_enabled(): void
    {
        Cache::flush();
        config([
            'api_cache.enabled' => true,
            'api_cache.ttl' => 120,
        ]);

        $viewer = $this->createActiveUser('private_term_owner');
        $otherUser = $this->createActiveUser('private_term_other');
        $domain = $this->createDomain();

        $privateGlossary = Glossary::query()->create([
            'owner_id' => $otherUser->id,
            'language_pair_id' => $domain['pair']->id,
            'field_id' => $domain['field']->id,
            'approved' => false,
            'is_public' => false,
        ]);

        $ownPrivateTerm = Term::query()->create([
            'glossary_id' => $privateGlossary->id,
            'field_id' => $domain['field']->id,
            'created_by' => $viewer->id,
        ]);
        TermTranslation::query()->create([
            'term_id' => $ownPrivateTerm->id,
            'language_id' => $domain['source']->id,
            'title' => 'Owner private term',
        ]);

        $otherPrivateTerm = Term::query()->create([
            'glossary_id' => $privateGlossary->id,
            'field_id' => $domain['field']->id,
            'created_by' => $otherUser->id,
        ]);
        TermTranslation::query()->create([
            'term_id' => $otherPrivateTerm->id,
            'language_id' => $domain['target']->id,
            'title' => 'Other private term',
        ]);

        Sanctum::actingAs($viewer);

        $this->getJson("/api/terms?created_by={$viewer->id}&search=private&per_page=100")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $ownPrivateTerm->id)
            ->assertJsonPath('meta.per_page', 30);
    }

    public function test_term_show_denies_non_creator_for_private_term_and_allows_creator(): void
    {
        $creator = $this->createActiveUser('term_show_creator');
        $intruder = $this->createActiveUser('term_show_intruder');
        $domain = $this->createDomain();

        $privateGlossary = Glossary::query()->create([
            'owner_id' => $creator->id,
            'language_pair_id' => $domain['pair']->id,
            'field_id' => $domain['field']->id,
            'approved' => false,
            'is_public' => false,
        ]);

        $term = Term::query()->create([
            'glossary_id' => $privateGlossary->id,
            'field_id' => $domain['field']->id,
            'created_by' => $creator->id,
        ]);

        $translation = TermTranslation::query()->create([
            'term_id' => $term->id,
            'language_id' => $domain['source']->id,
            'title' => 'Restricted term',
            'definition' => 'Private definition',
        ]);

        $reference = Reference::query()->create([
            'user_id' => $creator->id,
            'source' => 'Private source',
            'type' => 'book',
            'language' => 'en',
        ]);
        TermReference::query()->create([
            'term_translation_id' => $translation->id,
            'reference_id' => $reference->id,
            'type' => 'definition',
        ]);
        Comment::query()->create([
            'term_id' => $term->id,
            'user_id' => $intruder->id,
            'body' => 'Needs moderation',
            'is_spam' => false,
        ]);

        Sanctum::actingAs($intruder);
        $this->getJson("/api/terms/{$term->id}")
            ->assertForbidden();

        Sanctum::actingAs($creator);
        $this->getJson("/api/terms/{$term->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $term->id)
            ->assertJsonPath('data.comments_count', 1)
            ->assertJsonPath('data.translations_count', 1)
            ->assertJsonPath('data.translations.0.references.0.reference.id', $reference->id)
            ->assertJsonPath('data.comments.0.user_id', $intruder->id);
    }

    /**
     * @return array{source: Language, target: Language, pair: LanguagePair, group: FieldGroup, field: Field}
     */
    private function createDomain(): array
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
        $group = FieldGroup::query()->create([
            'name' => 'Science',
            'code' => 'SCI' . random_int(100, 999),
        ]);
        $field = Field::query()->create([
            'field_group_id' => $group->id,
            'name' => 'Biology',
            'code' => 'BIO' . random_int(100, 999),
        ]);

        return compact('source', 'target', 'pair', 'group', 'field');
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
