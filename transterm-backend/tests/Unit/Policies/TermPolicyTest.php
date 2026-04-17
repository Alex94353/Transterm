<?php

namespace Tests\Unit\Policies;

use App\Models\Glossary;
use App\Models\Term;
use App\Models\User;
use App\Policies\TermPolicy;
use Mockery;
use Tests\TestCase;

class TermPolicyTest extends TestCase
{
    public function test_public_approved_glossary_term_is_viewable_by_guest(): void
    {
        $policy = new TermPolicy();
        $term = new Term(['created_by' => 3]);
        $term->setRelation('glossary', new Glossary([
            'approved' => true,
            'is_public' => true,
        ]));

        $this->assertTrue($policy->view(null, $term));
    }

    public function test_private_term_is_viewable_by_creator(): void
    {
        $policy = new TermPolicy();
        $creator = new User(['id' => 7]);
        $term = new Term(['created_by' => 7]);
        $term->setRelation('glossary', new Glossary([
            'approved' => false,
            'is_public' => false,
        ]));

        $this->assertTrue($policy->view($creator, $term));
    }

    public function test_update_is_limited_to_creator(): void
    {
        $policy = new TermPolicy();
        $creator = Mockery::mock(User::class)->makePartial();
        $creator->id = 11;
        $creator->shouldReceive('can')->with('term.update')->andReturn(true);

        $otherUser = Mockery::mock(User::class)->makePartial();
        $otherUser->id = 12;
        $otherUser->shouldReceive('can')->with('term.update')->andReturn(true);

        $term = new Term(['created_by' => 11]);

        $this->assertTrue($policy->update($creator, $term));
        $this->assertFalse($policy->update($otherUser, $term));
    }

    public function test_delete_is_limited_to_creator(): void
    {
        $policy = new TermPolicy();
        $creator = Mockery::mock(User::class)->makePartial();
        $creator->id = 2;
        $creator->shouldReceive('can')->with('term.delete')->andReturn(true);

        $otherUser = Mockery::mock(User::class)->makePartial();
        $otherUser->id = 3;
        $otherUser->shouldReceive('can')->with('term.delete')->andReturn(true);

        $term = new Term(['created_by' => 2]);

        $this->assertTrue($policy->delete($creator, $term));
        $this->assertFalse($policy->delete($otherUser, $term));
    }
}
