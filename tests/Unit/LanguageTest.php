<?php

namespace Flamix\App24Core\Tests\Unit;

use Flamix\App24Core\Language;
use Flamix\App24Core\Tests\TestCase;

class LanguageTest extends TestCase
{
    public function test_filter_allows_en(): void
    {
        $this->assertEquals('en', Language::filter('en'));
    }

    public function test_filter_allows_ru(): void
    {
        $this->assertEquals('ru', Language::filter('ru'));
    }

    public function test_filter_allows_ua(): void
    {
        $this->assertEquals('ua', Language::filter('ua'));
    }

    public function test_filter_normalizes_uppercase_to_lowercase(): void
    {
        $this->assertEquals('en', Language::filter('EN'));
        $this->assertEquals('ru', Language::filter('RU'));
        $this->assertEquals('ua', Language::filter('UA'));
    }

    public function test_filter_returns_default_for_unsupported_language(): void
    {
        $this->assertEquals('en', Language::filter('de'));
        $this->assertEquals('en', Language::filter('fr'));
        $this->assertEquals('en', Language::filter('es'));
    }

    public function test_filter_returns_default_for_null(): void
    {
        $this->assertEquals('en', Language::filter(null));
    }

    public function test_filter_returns_default_for_empty_string(): void
    {
        $this->assertEquals('en', Language::filter(''));
    }

    public function test_filter_uses_config_default(): void
    {
        config(['app24.app.def_lang' => 'ru']);
        $this->assertEquals('ru', Language::filter('invalid'));
    }

    public function test_set_portal_language_saves_to_db(): void
    {
        $portal = $this->createPortal(['lang' => 'en']);

        Language::setPortalLanguage($portal->id, 'ru');

        $this->assertDatabaseHas('portals', [
            'id' => $portal->id,
            'lang' => 'ru',
        ]);
    }

    public function test_set_portal_language_filters_invalid_language(): void
    {
        $portal = $this->createPortal(['lang' => 'en']);

        Language::setPortalLanguage($portal->id, 'de');

        $this->assertDatabaseHas('portals', [
            'id' => $portal->id,
            'lang' => 'en', // filtered to default
        ]);
    }

    public function test_set_changes_app_locale(): void
    {
        $portal = $this->createPortal(['lang' => 'ru']);

        Language::set($portal->id);

        $this->assertEquals('ru', app()->getLocale());
    }

    public function test_set_uses_default_when_portal_has_no_lang(): void
    {
        $portal = $this->createPortal(['lang' => null]);

        Language::set($portal->id);

        $this->assertEquals(config('app24.app.def_lang', 'en'), app()->getLocale());
    }

    public function test_get_returns_current_locale(): void
    {
        app()->setLocale('ua');

        $this->assertEquals('ua', Language::get());
    }
}
