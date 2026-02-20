<?php

namespace Flamix\App24Core\Tests\Unit;

use Flamix\App24Core\User24;
use Flamix\App24Core\Tests\TestCase;

class User24Test extends TestCase
{
    protected function tearDown(): void
    {
        // Reset singleton static state
        $reflection = new \ReflectionClass(User24::class);
        foreach (['expire'] as $prop) {
            $property = $reflection->getProperty($prop);
            $property->setAccessible(true);
            $property->setValue(null, 0);
        }

        parent::tearDown();
    }

    /**
     * BUG TEST: isAuthExpire() returns true when expire is 0 (default).
     * This means every request triggers token refresh when reading from session.
     */
    public function test_is_auth_expire_returns_true_when_zero(): void
    {
        $reflection = new \ReflectionClass(User24::class);
        $prop = $reflection->getProperty('expire');
        $prop->setAccessible(true);
        $prop->setValue(null, 0);

        // BUG: expire=0 means time() >= 0 is always true
        $this->assertTrue(User24::isAuthExpire());
    }

    /**
     * isAuthExpire() should return false when token is still valid.
     */
    public function test_is_auth_expire_returns_false_when_not_expired(): void
    {
        $reflection = new \ReflectionClass(User24::class);
        $prop = $reflection->getProperty('expire');
        $prop->setAccessible(true);
        $prop->setValue(null, time() + 3600);

        $this->assertFalse(User24::isAuthExpire());
    }

    /**
     * isAuthExpire() should return true when token is expired.
     */
    public function test_is_auth_expire_returns_true_when_past(): void
    {
        $reflection = new \ReflectionClass(User24::class);
        $prop = $reflection->getProperty('expire');
        $prop->setAccessible(true);
        $prop->setValue(null, time() - 100);

        $this->assertTrue(User24::isAuthExpire());
    }

    /**
     * isAuthExpire() should return true when exactly at current time.
     */
    public function test_is_auth_expire_returns_true_at_boundary(): void
    {
        $reflection = new \ReflectionClass(User24::class);
        $prop = $reflection->getProperty('expire');
        $prop->setAccessible(true);
        $prop->setValue(null, time());

        // time() >= time() is true (expired)
        $this->assertTrue(User24::isAuthExpire());
    }
}
