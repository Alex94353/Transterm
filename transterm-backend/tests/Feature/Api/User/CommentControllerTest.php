<?php

namespace Tests\Feature\Api\User;

use App\Models\Comment;
use App\Models\Field;
use App\Models\FieldGroup;
use App\Models\Glossary;
use App\Models\GlossaryTranslation;
use App\Models\Language;
use App\Models\LanguagePair;
use App\Models\Role;
use App\Models\Term;
use App\Models\TermTranslation;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CommentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;
    protected string $seeder = DatabaseSeeder::class;

    public function test_index_returns_only_authenticated_user_comments_and_applies_filters(): void
    {
        $owner = $this->createActiveUser('comment_owner');
        $otherUser = $this->createActiveUser('comment_other');
        $domain = $this->createDomain();

        $termA = $this->createTermWithTranslation($owner, $domain, 'Alpha term');
        $termB = $this->createTermWithTranslation($owner, $domain, 'Beta term');

        $commentA = Comment::query()->create([
            'term_id' => $termA->id,
            'user_id' => $owner->id,
            'body' => 'Owner comment A',
            'is_spam' => false,
        ]);
        $commentB = Comment::query()->create([
            'term_id' => $termB->id,
            'user_id' => $owner->id,
            'body' => 'Owner comment B',
            'is_spam' => true,
        ]);
        Comment::query()->create([
            'term_id' => $termA->id,
            'user_id' => $otherUser->id,
            'body' => 'Other user comment',
            'is_spam' => true,
        ]);

        Sanctum::actingAs($owner);

        $this->getJson('/api/user/comments?per_page=50')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['id' => $commentA->id, 'user_id' => $owner->id])
            ->assertJsonFragment(['id' => $commentB->id, 'user_id' => $owner->id]);

        $this->getJson("/api/user/comments?term_id={$termB->id}&is_spam=true")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $commentB->id)
            ->assertJsonPath('data.0.is_spam', true);

        $this->getJson("/api/user/comments?term_id={$termA->id}&is_spam=false")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $commentA->id)
            ->assertJsonPath('data.0.is_spam', false);
    }

    public function test_store_validates_payload_and_creates_comment(): void
    {
        $owner = $this->createActiveUser('comment_store_owner');
        $domain = $this->createDomain();
        $term = $this->createTermWithTranslation($owner, $domain, 'Store target term');

        Sanctum::actingAs($owner);

        $this->postJson("/api/terms/{$term->id}/comments", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('body');

        $this->postJson("/api/terms/{$term->id}/comments", [
            'body' => 'This term is very useful.',
        ])->assertCreated()
            ->assertJsonPath('message', 'Comment created successfully.')
            ->assertJsonPath('data.term_id', $term->id)
            ->assertJsonPath('data.user_id', $owner->id)
            ->assertJsonPath('data.is_spam', false);

        $this->assertDatabaseHas('comments', [
            'term_id' => $term->id,
            'user_id' => $owner->id,
            'body' => 'This term is very useful.',
            'is_spam' => 0,
        ]);
    }

    public function test_update_allows_owner_and_blocks_other_user(): void
    {
        $owner = $this->createActiveUser('comment_update_owner');
        $intruder = $this->createActiveUser('comment_update_intruder');
        $intruder->syncRoles([$this->createRoleWithoutCommentPermissions()]);
        $domain = $this->createDomain();
        $term = $this->createTermWithTranslation($owner, $domain, 'Update target term');

        $comment = Comment::query()->create([
            'term_id' => $term->id,
            'user_id' => $owner->id,
            'body' => 'Original body',
            'is_spam' => false,
        ]);

        Sanctum::actingAs($owner);
        $this->putJson("/api/user/comments/{$comment->id}", [
            'body' => 'Updated by owner',
        ])->assertOk()
            ->assertJsonPath('message', 'Comment updated successfully.')
            ->assertJsonPath('data.id', $comment->id)
            ->assertJsonPath('data.body', 'Updated by owner');

        Sanctum::actingAs($intruder);
        $this->putJson("/api/user/comments/{$comment->id}", [
            'body' => 'Intruder update attempt',
        ])->assertForbidden();
    }

    public function test_destroy_allows_owner_and_blocks_non_owner(): void
    {
        $owner = $this->createActiveUser('comment_delete_owner');
        $otherUser = $this->createActiveUser('comment_delete_other');
        $otherUser->syncRoles([$this->createRoleWithoutCommentPermissions()]);
        $domain = $this->createDomain();
        $term = $this->createTermWithTranslation($owner, $domain, 'Destroy target term');

        $ownerComment = Comment::query()->create([
            'term_id' => $term->id,
            'user_id' => $owner->id,
            'body' => 'Delete me',
            'is_spam' => false,
        ]);
        $protectedOwnerComment = Comment::query()->create([
            'term_id' => $term->id,
            'user_id' => $owner->id,
            'body' => 'Owner protected comment',
            'is_spam' => false,
        ]);

        Sanctum::actingAs($owner);
        $this->deleteJson("/api/user/comments/{$ownerComment->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Comment deleted successfully.');

        $this->assertDatabaseMissing('comments', ['id' => $ownerComment->id]);

        Sanctum::actingAs($otherUser);
        $this->deleteJson("/api/user/comments/{$protectedOwnerComment->id}")
            ->assertForbidden();
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
            'name' => 'Engineering',
            'code' => 'ENG' . random_int(100, 999),
        ]);
        $field = Field::query()->create([
            'field_group_id' => $group->id,
            'name' => 'Software',
            'code' => 'SOFT' . random_int(100, 999),
        ]);

        return compact('source', 'target', 'pair', 'group', 'field');
    }

    /**
     * @param array{source: Language, target: Language, pair: LanguagePair, group: FieldGroup, field: Field} $domain
     */
    private function createTermWithTranslation(User $owner, array $domain, string $title): Term
    {
        $glossary = Glossary::query()->create([
            'owner_id' => $owner->id,
            'language_pair_id' => $domain['pair']->id,
            'field_id' => $domain['field']->id,
            'approved' => true,
            'is_public' => true,
        ]);
        GlossaryTranslation::query()->create([
            'glossary_id' => $glossary->id,
            'language_id' => $domain['source']->id,
            'title' => "Glossary for {$title}",
            'description' => null,
        ]);

        $term = Term::query()->create([
            'glossary_id' => $glossary->id,
            'field_id' => $domain['field']->id,
            'created_by' => $owner->id,
        ]);
        TermTranslation::query()->create([
            'term_id' => $term->id,
            'language_id' => $domain['source']->id,
            'title' => $title,
            'definition' => "Definition for {$title}",
        ]);

        return $term;
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

    private function createRoleWithoutCommentPermissions(): Role
    {
        return Role::query()->firstOrCreate([
            'name' => 'NoCommentAccess',
            'guard_name' => 'web',
        ]);
    }

    private function uniqueSuffix(): string
    {
        return str_replace('.', '', (string) microtime(true)) . random_int(100, 999);
    }
}
