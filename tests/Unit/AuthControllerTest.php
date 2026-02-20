<?php

namespace Flamix\App24Core\Tests\Unit;

use Carbon\Carbon;
use Flamix\App24Core\Controllers\AuthController;
use Flamix\App24Core\Tests\TestCase;

class AuthControllerTest extends TestCase
{
    /**
     * getAuthFields() should normalize install-format data (uppercase keys from Bitrix24).
     */
    public function test_get_auth_fields_normalizes_install_format(): void
    {
        $input = [
            'DOMAIN' => 'test.bitrix24.com',
            'member_id' => 'member_123',
            'AUTH_ID' => 'auth_token_value',
            'REFRESH_ID' => 'refresh_token_value',
            'AUTH_EXPIRES' => '3600',
        ];

        $result = AuthController::getAuthFields($input);

        $this->assertEquals('test.bitrix24.com', $result['domain']);
        $this->assertEquals('member_123', $result['member_id']);
        $this->assertEquals('auth_token_value', $result['access_token']);
        $this->assertEquals('refresh_token_value', $result['refresh_token']);
        $this->assertEquals(config('app.name'), $result['app_code']);
        $this->assertInstanceOf(Carbon::class, $result['expires']);
    }

    /**
     * getAuthFields() should normalize refresh-format data (lowercase keys).
     */
    public function test_get_auth_fields_normalizes_refresh_format(): void
    {
        $input = [
            'domain' => 'test.bitrix24.com',
            'member_id' => 'member_123',
            'access_token' => 'new_access_token',
            'refresh_token' => 'new_refresh_token',
            'expires' => now()->addHour()->getTimestamp(),
        ];

        $result = AuthController::getAuthFields($input);

        $this->assertEquals('test.bitrix24.com', $result['domain']);
        $this->assertEquals('new_access_token', $result['access_token']);
        $this->assertEquals('new_refresh_token', $result['refresh_token']);
        $this->assertInstanceOf(Carbon::class, $result['expires']);
    }

    /**
     * getAuthFields() should handle Carbon expires correctly.
     */
    public function test_get_auth_fields_handles_carbon_expires(): void
    {
        $carbon = Carbon::now()->addHours(2);

        $input = [
            'domain' => 'test.bitrix24.com',
            'member_id' => 'member_123',
            'access_token' => 'token',
            'refresh_token' => 'refresh',
            'expires' => $carbon,
        ];

        $result = AuthController::getAuthFields($input);

        $this->assertInstanceOf(Carbon::class, $result['expires']);
        $this->assertTrue($result['expires']->eq($carbon));
    }

    /**
     * getAuthFields() should calculate expires from AUTH_EXPIRES when no 'expires' key.
     */
    public function test_get_auth_fields_calculates_expires_from_auth_expires(): void
    {
        $input = [
            'domain' => 'test.bitrix24.com',
            'member_id' => 'member_123',
            'AUTH_ID' => 'token',
            'REFRESH_ID' => 'refresh',
            'AUTH_EXPIRES' => '1800',
        ];

        $before = now()->addSeconds(1600);
        $result = AuthController::getAuthFields($input);
        // AUTH_EXPIRES - 100 = 1700 seconds from now
        $this->assertTrue($result['expires']->gte($before));
    }

    /**
     * getAuthFields() should default to 3600 AUTH_EXPIRES when not provided.
     */
    public function test_get_auth_fields_defaults_to_3600_expires(): void
    {
        $input = [
            'domain' => 'test.bitrix24.com',
            'member_id' => 'member_123',
            'access_token' => 'token',
            'refresh_token' => 'refresh',
        ];

        $before = now()->addSeconds(3400);
        $result = AuthController::getAuthFields($input);
        // 3600 - 100 = 3500 seconds from now
        $this->assertTrue($result['expires']->gte($before));
    }

    /**
     * getAuthFields() should include oauth_server when passed.
     */
    public function test_get_auth_fields_includes_oauth_server(): void
    {
        $input = [
            'domain' => 'test.bitrix24.com',
            'member_id' => 'member_123',
            'access_token' => 'token',
            'refresh_token' => 'refresh',
            'oauth_server' => 'oauth.bitrix24.tech',
        ];

        $result = AuthController::getAuthFields($input);
        $this->assertEquals('oauth.bitrix24.tech', $result['oauth_server']);
    }

    /**
     * getAuthFields() should set oauth_server to null when not provided.
     */
    public function test_get_auth_fields_null_oauth_server_when_missing(): void
    {
        $input = [
            'domain' => 'test.bitrix24.com',
            'member_id' => 'member_123',
            'access_token' => 'token',
            'refresh_token' => 'refresh',
        ];

        $result = AuthController::getAuthFields($input);
        $this->assertNull($result['oauth_server']);
    }

    /**
     * BUG TEST: is_integer(intval($x)) is always true — non-numeric expires won't throw.
     * This documents the existing bug in getAuthFields().
     */
    public function test_get_auth_fields_bug_non_numeric_expires_does_not_throw(): void
    {
        $input = [
            'domain' => 'test.bitrix24.com',
            'member_id' => 'member_123',
            'access_token' => 'token',
            'refresh_token' => 'refresh',
            'expires' => 'not_a_number',
        ];

        // BUG: This should throw App24Exception but doesn't because is_integer(intval('not_a_number')) === true
        $result = AuthController::getAuthFields($input);
        $this->assertInstanceOf(Carbon::class, $result['expires']);
        // intval('not_a_number') === 0, so expires = Carbon::createFromTimestamp(0) = 1970-01-01
        $this->assertTrue($result['expires']->year === 1970);
    }

    /**
     * insertOrUpdateOAuth() should create a new portal in DB.
     */
    public function test_insert_or_update_oauth_creates_portal(): void
    {
        $data = [
            'DOMAIN' => 'new-portal.bitrix24.com',
            'member_id' => 'member_new',
            'AUTH_ID' => 'access_new',
            'REFRESH_ID' => 'refresh_new',
            'AUTH_EXPIRES' => '3600',
        ];

        $id = app(AuthController::class)->insertOrUpdateOAuth($data);

        $this->assertGreaterThan(0, $id);
        $this->assertDatabaseHas('portals', [
            'id' => $id,
            'domain' => 'new-portal.bitrix24.com',
            'member_id' => 'member_new',
        ]);
    }

    /**
     * insertOrUpdateOAuth() should update auth tokens for existing portal.
     */
    public function test_insert_or_update_oauth_updates_existing_portal(): void
    {
        $portal = $this->createPortal(['domain' => 'existing.bitrix24.com']);

        $data = [
            'domain' => 'existing.bitrix24.com',
            'member_id' => 'updated_member',
            'access_token' => 'updated_access',
            'refresh_token' => 'updated_refresh',
            'expires' => now()->addHour()->getTimestamp(),
        ];

        $id = app(AuthController::class)->insertOrUpdateOAuth($data);

        $this->assertEquals($portal->id, $id);
        $this->assertDatabaseHas('portals', [
            'id' => $portal->id,
            'access_token' => 'updated_access',
            'refresh_token' => 'updated_refresh',
        ]);
    }

    /**
     * insertOrUpdateOAuth() should throw when APP_NAME is empty.
     */
    public function test_insert_or_update_oauth_throws_without_app_name(): void
    {
        config(['app.name' => '']);

        $this->expectException(\App\Exceptions\App24Exception::class);

        app(AuthController::class)->insertOrUpdateOAuth([
            'domain' => 'test.bitrix24.com',
            'member_id' => 'member',
            'access_token' => 'token',
            'refresh_token' => 'refresh',
        ]);
    }

    /**
     * insertOrUpdateOAuth() should not overwrite app_code on update.
     */
    public function test_insert_or_update_oauth_preserves_app_code_on_update(): void
    {
        $portal = $this->createPortal([
            'domain' => 'preserve.bitrix24.com',
            'app_code' => 'original_app',
        ]);

        $data = [
            'domain' => 'preserve.bitrix24.com',
            'member_id' => 'new_member',
            'access_token' => 'new_token',
            'refresh_token' => 'new_refresh',
            'expires' => now()->addHour()->getTimestamp(),
        ];

        app(AuthController::class)->insertOrUpdateOAuth($data);

        $this->assertDatabaseHas('portals', [
            'id' => $portal->id,
            'app_code' => 'original_app',
        ]);
    }
}
