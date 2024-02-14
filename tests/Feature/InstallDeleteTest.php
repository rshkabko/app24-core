<?php

namespace Flamix\App24Core\Tests\Feature;

use Tests\TestCase;
use Flamix\App24Core\Models\Portals;
use Illuminate\Foundation\Testing\WithFaker;

/**
 * Basic test for install and delete app.
 * php artisan test ./vendor/flamix/app24-core/tests --configuration ./vendor/flamix/app24-core/phpunit.xml
 */
class InstallDeleteTest extends TestCase
{
    /**
     * Mannualy add package providers.
     *
     * @param $app
     * @return array
     */
    protected function getPackageProviders($app): array
    {
        return [
            \Flamix\App24Core\App24ServiceProvider::class,
        ];
    }

    /**
     * Before every method.
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
    }

    /**
     * When all tests are done.
     *
     * @return void
     */
    public static function tearDownAfterClass(): void
    {
        shell_exec('php artisan migrate:reset');
    }

    // TODO: Take from our real Rest24
    private function getAuth()
    {
        return [
            'domain' => 'pr.flamix.info',
            'member_id' => 'member_id',
            'access_token' => 'access_token',
            'refresh_token' => 'refresh_token',
            'application_token' => 'application_token',
            'expires' => now()->addHour()->getTimestamp(),
        ];
    }

    public function test_can_add_in_vendor()
    {
        $response = $this->json('POST', route('app24.install'), []);
        $response->assertStatus(200)->assertJson([
            'status' => false,
            'error' => 'Auth is empty!',
        ]);
    }

    public function test_can_install_app()
    {
        $response = $this->json('POST', route('app24.install'), $this->getAuth());
        $response->assertStatus(200)->assertSee('Install portal #1');
    }

    public function test_is_app_in_db()
    {
        $this->assertTrue(Portals::find(1)->id === 1);
    }

    public function test_can_unistall_app()
    {
        $response = $this->json('POST', route('app24.uninstall', ['hash' => hash_hmac('sha256', "delete_1", config('app.key'))]), [
            'auth' => $this->getAuth(),
            'data' => ['CLEAN' => true]
        ]);
        $response->assertStatus(200)->assertJson([
            'status' => true
        ]);
    }

    public function test_is_app_was_deleted_frim_db()
    {
        $this->assertTrue(Portals::count() === 0);
    }
}