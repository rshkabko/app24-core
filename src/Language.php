<?php

namespace Flamix\App24Core;

use Flamix\App24Core\Models\Portals;

class Language
{
    /**
     * Устанавливаем язык из БД
     *
     * По умолчанию язык берем по домену. Если домена нет (CRON), то нужно автоматически ставить так
     * \Flamix\B24App\Language::set(2);
     *
     * @param int $portal_id
     * @return string
     */
    public static function set(int $portal_id = 0): string
    {
        $lang = $portal_id ? Portals::find($portal_id)?->lang : self::getPortalLanguage();
        $lang = empty($lang) ? config('app24.app.def_lang', 'en') : $lang;
        return lang($lang);
    }

    /**
     * Получаем язык интерфейса портала
     *
     * @return string
     */
    public static function get(): string
    {
        return lang();
    }

    /**
     * Возвращаем только разрешенный язык
     *
     * @param string|null $lang
     * @return string
     */
    public static function filter(?string $lang): string
    {
        if (in_array(strtolower($lang), ['en', 'ua', 'pl', 'ru'])) {
            return strtolower($lang);
        }

        return config('app24.app.def_lang', 'en');
    }

    /**
     * Определяем регион портала по установке (из БД)
     *
     * @return string
     */
    public static function getPortalLanguage(): string
    {
        $lang = Portals::getByDomain()?->lang;

        if (empty($lang)) {
            $lang = config('app24.app.def_lang', 'en');
        }

        return self::filter($lang);
    }

    /**
     * Detect and save portal language to DB.
     *
     * @param int $id
     * @param string|null $lang
     * @return string
     */
    public static function setPortalLanguage(int $id, ?string $lang = null): string
    {
        // Try to get lang from request
        if (!$lang) {
            $lang = request()->LANG ?? request()->lang ?? null;
        }
        $lang = self::filter($lang);

        $portal = Portals::find($id);
        $portal->lang = $lang;
        $portal->save();

        return $lang;
    }
}
