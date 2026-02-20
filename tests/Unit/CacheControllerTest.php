<?php

namespace Flamix\App24Core\Tests\Unit;

use Flamix\App24Core\Controllers\CacheController;
use Flamix\App24Core\Tests\TestCase;
use Illuminate\Support\Facades\Cache;

class CacheControllerTest extends TestCase
{
    public function test_key_generates_portal_domain_key(): void
    {
        $key = CacheController::key('portal_domain', 'test.bitrix24.com');

        $this->assertStringContainsString('portal_app_', $key);
        $this->assertStringContainsString(config('app.name'), $key);
        $this->assertStringContainsString('test.bitrix24.com', $key);
    }

    public function test_key_generates_portal_id_key(): void
    {
        $key = CacheController::key('portal_id', 42);

        $this->assertEquals('portal_data_42', $key);
    }

    public function test_key_throws_on_unknown_key(): void
    {
        $this->expectException(\UnhandledMatchError::class);

        CacheController::key('unknown_key', 'value');
    }

    public function test_clear_portal_cache_removes_portal_id_cache(): void
    {
        $portal = $this->createPortal();
        $cacheKey = CacheController::key('portal_id', $portal->id);

        Cache::put($cacheKey, 'cached_data', 600);
        $this->assertTrue(Cache::has($cacheKey));

        CacheController::clearPortalCache($portal->id);
        $this->assertFalse(Cache::has($cacheKey));
    }

    public function test_clear_portal_cache_removes_domain_cache_when_provided(): void
    {
        $domain = 'test.bitrix24.com';
        $portal = $this->createPortal(['domain' => $domain]);
        $domainKey = CacheController::key('portal_domain', $domain);

        Cache::put($domainKey, 123, 600);
        $this->assertTrue(Cache::has($domainKey));

        CacheController::clearPortalCache($portal->id, $domain);
        $this->assertFalse(Cache::has($domainKey));
    }

    /**
     * BUG TEST: Domain cache is NOT cleared when domain is not explicitly provided.
     * This documents a cache staleness issue.
     */
    public function test_clear_portal_cache_does_not_remove_domain_cache_when_not_provided(): void
    {
        $domain = 'test.bitrix24.com';
        $portal = $this->createPortal(['domain' => $domain]);
        $domainKey = CacheController::key('portal_domain', $domain);

        Cache::put($domainKey, 123, 600);

        CacheController::clearPortalCache($portal->id);
        // BUG: domain cache remains stale
        $this->assertTrue(Cache::has($domainKey));
    }

    public function test_key_portal_domain_includes_app_name_for_isolation(): void
    {
        config(['app.name' => 'app_one']);
        $key1 = CacheController::key('portal_domain', 'test.bitrix24.com');

        config(['app.name' => 'app_two']);
        $key2 = CacheController::key('portal_domain', 'test.bitrix24.com');

        $this->assertNotEquals($key1, $key2);
    }
}
