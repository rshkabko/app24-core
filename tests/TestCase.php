<?php

namespace Flamix\App24Core\Tests;

use Tests\TestCase as LaravelTestCase;
use Flamix\App24Core\App24;
use Flamix\App24Core\Models\Portals;
use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class TestCase extends LaravelTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');

        // Reset singleton static state between tests
        App24::reInstance();
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app24.access.id', 'test_app_id');
        $app['config']->set('app24.access.secret', 'test_app_secret');
    }

    /**
     * Create a portal directly in DB for tests that need an existing portal.
     */
    protected function createPortal(array $override = []): Portals
    {
        return Portals::create(array_merge([
            'app_code' => config('app.name'),
            'app_id' => config('app24.access.id'),
            'app_secret' => config('app24.access.secret'),
            'user_id' => 1,
            'domain' => 'test.bitrix24.com',
            'member_id' => 'member_test_123',
            'access_token' => 'access_token_test',
            'refresh_token' => 'refresh_token_test',
            'expires' => now()->addHour(),
            'lang' => 'en',
            'admin_only' => 0,
            'region' => 'eu',
            'oauth_server' => 'oauth.bitrix.info',
        ], $override));
    }
}
