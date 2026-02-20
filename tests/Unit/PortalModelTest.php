<?php

namespace Flamix\App24Core\Tests\Unit;

use Flamix\App24Core\Controllers\CacheController;
use Flamix\App24Core\Models\Portals;
use Flamix\App24Core\Tests\TestCase;
use Illuminate\Support\Facades\Cache;

class PortalModelTest extends TestCase
{
    public function test_portal_can_be_created(): void
    {
        $portal = $this->createPortal(['domain' => 'create-test.bitrix24.com']);

        $this->assertDatabaseHas('portals', [
            'id' => $portal->id,
            'domain' => 'create-test.bitrix24.com',
        ]);
    }

    public function test_portal_deletes(): void
    {
        $portal = $this->createPortal();
        $portalId = $portal->id;
        $portal->delete();

        $this->assertDatabaseMissing('portals', ['id' => $portalId]);
    }

    public function test_portal_cache_cleared_on_save(): void
    {
        $portal = $this->createPortal();
        $cacheKey = CacheController::key('portal_id', $portal->id);

        Cache::put($cacheKey, 'cached_value', 600);
        $this->assertTrue(Cache::has($cacheKey));

        $portal->lang = 'ru';
        $portal->save();

        $this->assertFalse(Cache::has($cacheKey));
    }

    public function test_portal_cache_cleared_on_delete(): void
    {
        $portal = $this->createPortal();
        $cacheKey = CacheController::key('portal_id', $portal->id);

        Cache::put($cacheKey, 'cached_value', 600);
        $this->assertTrue(Cache::has($cacheKey));

        $portal->delete();

        $this->assertFalse(Cache::has($cacheKey));
    }

    public function test_get_data_returns_portal_by_id(): void
    {
        $portal = $this->createPortal(['domain' => 'data-test.bitrix24.com']);

        $result = Portals::getData($portal->id);

        $this->assertEquals($portal->id, $result->id);
        $this->assertEquals('data-test.bitrix24.com', $result->domain);
    }

    public function test_get_data_throws_for_nonexistent_id(): void
    {
        $this->expectException(\App\Exceptions\App24Exception::class);

        Portals::getData(99999);
    }

    public function test_get_by_id_caches_result(): void
    {
        $portal = $this->createPortal();
        $cacheKey = CacheController::key('portal_id', $portal->id);

        $this->assertFalse(Cache::has($cacheKey));

        // First call should cache
        app(Portals::class)->getByID($portal->id);

        $this->assertTrue(Cache::has($cacheKey));
    }

    public function test_portal_stores_region(): void
    {
        $portal = $this->createPortal(['region' => 'ru']);

        $this->assertDatabaseHas('portals', [
            'id' => $portal->id,
            'region' => 'ru',
        ]);
    }

    public function test_portal_stores_oauth_server(): void
    {
        $portal = $this->createPortal(['oauth_server' => 'oauth.bitrix24.tech']);

        $this->assertDatabaseHas('portals', [
            'id' => $portal->id,
            'oauth_server' => 'oauth.bitrix24.tech',
        ]);
    }

    public function test_multiple_portals_same_domain_different_app_code(): void
    {
        $this->createPortal(['domain' => 'shared.bitrix24.com', 'app_code' => 'app_one']);
        $this->createPortal(['domain' => 'shared.bitrix24.com', 'app_code' => 'app_two']);

        $count = Portals::where('domain', 'shared.bitrix24.com')->count();
        $this->assertEquals(2, $count);
    }

    /**
     * BUG TEST: $guarded = [] allows mass-assignment of any field including secrets.
     */
    public function test_guarded_is_empty_allows_mass_assignment(): void
    {
        $portal = Portals::create([
            'app_code' => 'test',
            'app_id' => 'INJECTED_APP_ID',
            'app_secret' => 'INJECTED_SECRET',
            'user_id' => 1,
            'domain' => 'mass-assign.bitrix24.com',
            'member_id' => 'member',
            'access_token' => 'token',
            'refresh_token' => 'refresh',
            'expires' => now()->addHour(),
        ]);

        // This test passes because $guarded = [] — this is a security concern
        $this->assertEquals('INJECTED_APP_ID', $portal->app_id);
        $this->assertEquals('INJECTED_SECRET', $portal->app_secret);
    }
}
