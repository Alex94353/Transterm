<?php

namespace Tests\Unit\Policies;

use App\Models\Glossary;
use App\Models\User;
use App\Policies\GlossaryPolicy;
use Tests\TestCase;

class GlossaryPolicyTest extends TestCase
{
    public function test_public_approved_glossary_is_viewable_by_guest(): void
    {
        $policy = new GlossaryPolicy();
        $glossary = new Glossary([
            'owner_id' => 10,
            'approved' => true,
            'is_public' => true,
        ]);

        $this->assertTrue($policy->view(null, $glossary));
    }

    public function test_private_or_unapproved_glossary_is_viewable_by_owner(): void
    {
        $policy = new GlossaryPolicy();
        $owner = new User(['id' => 5]);
        $glossary = new Glossary([
            'owner_id' => 5,
            'approved' => false,
            'is_public' => false,
        ]);

        $this->assertTrue($policy->view($owner, $glossary));
    }

    public function test_update_allows_only_owner(): void
    {
        $policy = new GlossaryPolicy();
        $owner = new User(['id' => 3]);
        $otherUser = new User(['id' => 4]);
        $glossary = new Glossary(['owner_id' => 3]);

        $this->assertTrue($policy->update($owner, $glossary));
        $this->assertFalse($policy->update($otherUser, $glossary));
    }

    public function test_delete_allows_only_owner(): void
    {
        $policy = new GlossaryPolicy();
        $owner = new User(['id' => 8]);
        $otherUser = new User(['id' => 9]);
        $glossary = new Glossary(['owner_id' => 8]);

        $this->assertTrue($policy->delete($owner, $glossary));
        $this->assertFalse($policy->delete($otherUser, $glossary));
    }
}
