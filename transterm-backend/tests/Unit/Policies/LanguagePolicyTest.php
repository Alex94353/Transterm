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

        $this->assertNull($policy->before($user, 'view'));
    }

    public function test_view_methods_require_permission(): void
    {
        $policy = new LanguagePolicy();
        $language = new Language();
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('can')->with('language.view-any')->andReturn(true);
        $user->shouldReceive('can')->with('language.view')->andReturn(true);

        $this->assertTrue($policy->viewAny($user));
        $this->assertFalse($policy->viewAny(null));

        $this->assertTrue($policy->view($user, $language));
        $this->assertFalse($policy->view(null, $language));
    }

    public function test_mutating_methods_require_permissions(): void
    {
        $policy = new LanguagePolicy();
        $language = new Language();
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('can')->with('language.create')->andReturn(true);
        $user->shouldReceive('can')->with('language.update')->andReturn(true);
        $user->shouldReceive('can')->with('language.delete')->andReturn(true);

        $this->assertTrue($policy->create($user));
        $this->assertTrue($policy->update($user, $language));
        $this->assertTrue($policy->delete($user, $language));
    }
}
