<?php

namespace Flamix\App24Core\Tests\Unit;

use Flamix\App24Core\Tests\TestCase;

class HelpersTest extends TestCase
{
    public function test_lang_helper_gets_current_locale(): void
    {
        app()->setLocale('ru');
        $this->assertEquals('ru', lang());
    }

    public function test_lang_helper_sets_locale(): void
    {
        lang('ua');
        $this->assertEquals('ua', app()->getLocale());
    }

    public function test_lang_helper_returns_set_value(): void
    {
        $result = lang('en');
        $this->assertEquals('en', $result);
    }

    public function test_app24_url_contains_path(): void
    {
        $url = app24_url('/test-path');
        $this->assertStringContainsString('/test-path', $url);
    }

    public function test_app24_url_appends_session_id(): void
    {
        $sessionName = config('session.cookie');
        $sessionId = session()?->getId();

        $url = app24_url('/test-path');

        if ($sessionName && $sessionId) {
            $this->assertStringContainsString("{$sessionName}={$sessionId}", $url);
        } else {
            // Without session, URL is plain
            $this->assertStringContainsString('/test-path', $url);
        }
    }

    public function test_app24_url_uses_ampersand_when_query_exists(): void
    {
        $sessionName = config('session.cookie');
        if (!$sessionName || !session()?->getId()) {
            $this->markTestSkipped('No session available');
        }

        $url = app24_url('/test?foo=bar');

        $this->assertStringContainsString('&' . $sessionName . '=', $url);
    }

    public function test_app24_url_uses_question_mark_when_no_query(): void
    {
        $sessionName = config('session.cookie');
        if (!$sessionName || !session()?->getId()) {
            $this->markTestSkipped('No session available');
        }

        $url = app24_url('/clean-path');

        $this->assertStringContainsString('?' . $sessionName . '=', $url);
    }

    public function test_app24_route_generates_url_for_known_route(): void
    {
        $url = app24_route('app24.install');
        $this->assertStringContainsString('app24/install', $url);
    }

    public function test_app24_route_does_not_duplicate_session_param(): void
    {
        $sessionName = config('session.cookie');
        if (!$sessionName) {
            $this->markTestSkipped('No session cookie configured');
        }

        $url = app24_route('app24.install', [$sessionName => 'custom_session']);

        // Count occurrences of session param — should be exactly 1
        $count = substr_count($url, $sessionName . '=');
        $this->assertEquals(1, $count);
    }
}
