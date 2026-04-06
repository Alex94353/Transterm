<?php

namespace Tests\Unit\Support;

use App\Models\User;
use App\Support\ApiListCache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ApiListCacheTest extends TestCase
{
    public function test_terms_key_is_stable_for_same_query_with_different_order(): void
    {
        Cache::flush();

        $r1 = Request::create('/api/terms?a=1&b=2', 'GET');
        $r2 = Request::create('/api/terms?b=2&a=1', 'GET');

        $this->assertSame(ApiListCache::termsKey($r1), ApiListCache::termsKey($r2));
    }

    public function test_ttl_is_at_least_one_second(): void
    {
        config()->set('api_cache.ttl', 0);

        $this->assertSame(1, ApiListCache::ttlSeconds());
    }

    public function test_key_contains_user_scope_for_authenticated_requests(): void
    {
        $request = Request::create('/api/glossaries?per_page=10', 'GET');
        $request->setUserResolver(fn (): User => new User(['id' => 77]));

        $key = ApiListCache::glossariesKey($request);

        $this->assertStringContainsString('scope:user:77', $key);
    }

    public function test_bumping_versions_changes_cache_key(): void
    {
        Cache::flush();
        $request = Request::create('/api/glossaries?per_page=10', 'GET');

        $before = ApiListCache::glossariesKey($request);
        ApiListCache::bumpGlossaryAndTermVersions();
        $after = ApiListCache::glossariesKey($request);

        $this->assertNotSame($before, $after);
    }
}
