<?php

namespace Flamix\App24Core\Tests\Feature;

use Flamix\App24Core\Tests\TestCase;

class UninstallSecurityTest extends TestCase
{
    public function test_uninstall_with_wrong_hash_returns_error(): void
    {
        $portal = $this->createPortal(['domain' => 'security.bitrix24.com']);

        $response = $this->json('POST', route('app24.uninstall', ['hash' => 'wrong_hash']), [
            'auth' => ['domain' => 'security.bitrix24.com'],
            'data' => ['CLEAN' => true],
        ]);

        $response->assertStatus(200)->assertJson([
            'status' => false,
            'error' => 'Bad hash',
        ]);
    }

    public function test_uninstall_with_correct_hash_succeeds(): void
    {
        $portal = $this->createPortal(['domain' => 'valid.bitrix24.com']);
        $hash = hash_hmac('sha256', "delete_{$portal->id}", config('app.key'));

        $response = $this->json('POST', route('app24.uninstall', ['hash' => $hash]), [
            'auth' => ['domain' => 'valid.bitrix24.com'],
            'data' => ['CLEAN' => true],
        ]);

        $response->assertStatus(200)->assertJsonFragment(['status' => true]);
        $this->assertDatabaseMissing('portals', ['id' => $portal->id]);
    }

    public function test_uninstall_without_clean_keeps_data(): void
    {
        $portal = $this->createPortal(['domain' => 'keep.bitrix24.com']);
        $hash = hash_hmac('sha256', "delete_{$portal->id}", config('app.key'));

        $response = $this->json('POST', route('app24.uninstall', ['hash' => $hash]), [
            'auth' => ['domain' => 'keep.bitrix24.com'],
            'data' => ['CLEAN' => false],
        ]);

        $response->assertStatus(200)->assertJsonFragment(['status' => true]);
        $this->assertDatabaseHas('portals', ['id' => $portal->id, 'deleted_at' => null]);
    }

    public function test_uninstall_with_empty_domain_returns_error(): void
    {
        $response = $this->json('POST', route('app24.uninstall', ['hash' => 'any']), [
            'auth' => [],
            'data' => ['CLEAN' => true],
        ]);

        // Empty domain triggers App24Exception or returns domain-related error
        $response->assertStatus(200);
        $content = $response->json();
        $this->assertTrue(
            isset($content['error']) || (isset($content['status']) && $content['status'] === false),
            'Expected an error response for empty domain'
        );
    }

    public function test_uninstall_hash_differs_per_portal(): void
    {
        $portal1 = $this->createPortal(['domain' => 'portal1.bitrix24.com']);
        $portal2 = $this->createPortal(['domain' => 'portal2.bitrix24.com']);

        $hash1 = hash_hmac('sha256', "delete_{$portal1->id}", config('app.key'));
        $hash2 = hash_hmac('sha256', "delete_{$portal2->id}", config('app.key'));

        $this->assertNotEquals($hash1, $hash2);
    }

    public function test_uninstall_hash_for_portal1_does_not_work_for_portal2(): void
    {
        $portal1 = $this->createPortal(['domain' => 'first.bitrix24.com']);
        $portal2 = $this->createPortal(['domain' => 'second.bitrix24.com']);

        $hash1 = hash_hmac('sha256', "delete_{$portal1->id}", config('app.key'));

        $response = $this->json('POST', route('app24.uninstall', ['hash' => $hash1]), [
            'auth' => ['domain' => 'second.bitrix24.com'],
            'data' => ['CLEAN' => true],
        ]);

        $response->assertStatus(200)->assertJson([
            'status' => false,
            'error' => 'Bad hash',
        ]);
    }

    public function test_uninstall_without_auth_returns_error(): void
    {
        $response = $this->json('POST', route('app24.uninstall', ['hash' => 'any']), [
            'data' => ['CLEAN' => true],
        ]);

        $response->assertStatus(200);
        $content = $response->json();
        $this->assertTrue(
            isset($content['error']) || (isset($content['status']) && $content['status'] === false),
            'Expected an error response when auth is missing'
        );
    }
}
