<?php

namespace Tests\Unit\Policies;

use App\Models\Language;
use App\Models\User;
use App\Policies\LanguagePolicy;
use Mockery;
use Tests\TestCase;

class LanguagePolicyTest extends TestCase
{
    public function test_before_returns_null_when_user_has_no_matching_permission(): void
    {
        $policy = new LanguagePolicy();
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('can')->with('admin.access')->andReturn(false);
        $user->shouldReceive('getAllPermissions')->andReturn(collect());

        $this->assertNull($policy->before($user, 'view'));
    }

    public function test_view_methods_require_authenticated_user(): void
    {
        $policy = new LanguagePolicy();
        $language = new Language();
        $user = new User(['id' => 2]);

        $this->assertTrue($policy->viewAny($user));
        $this->assertFalse($policy->viewAny(null));

        $this->assertTrue($policy->view($user, $language));
        $this->assertFalse($policy->view(null, $language));
    }

    public function test_mutating_methods_are_denied_by_default(): void
    {
        $policy = new LanguagePolicy();
        $language = new Language();
        $user = new User(['id' => 2]);

        $this->assertFalse($policy->create($user));
        $this->assertFalse($policy->update($user, $language));
        $this->assertFalse($policy->delete($user, $language));
    }
}
