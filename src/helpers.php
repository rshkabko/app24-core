<?php

use Illuminate\Support\Facades\Log;
use Symfony\Component\VarDumper\VarDumper;
use Flamix\B24App\Models\Portals;
use Flamix\B24App\Controllers;

/**
 * Смарт логи
 *
 * При разработке в ключевых местах можно оставлять sdump,
 * который ключается автоматически, если DEBUG=true или B24_DEBUG=текущему домену
 *
 * sdd - для жесткой отладки прям на клиентском проекте
 * sdump - дебаг, который можно оставлять в ключевых местах
 */

if (!function_exists('sdd')) {
    function sdd(...$vars)
    {
        if (!config('app.debug', false)) {
            if (empty(config('b24app.app.b24_debug_portal')) || empty(Portals::getDomain()))
                return false;

            if (empty(Portals::getDomain()))
                return false;

            if (config('b24app.app.b24_debug_portal') != Portals::getDomain())
                return false;
        }

        foreach ($vars as $v)
            VarDumper::dump($v);

        exit(1);
    }
}

if (!function_exists('sdump')) {
    function sdump($var, ...$moreVars)
    {
        if (!config('app.debug', false)) {
            if (empty(config('b24app.app.b24_debug_portal')) || empty(Portals::getDomain()))
                return false;

            if (empty(Portals::getDomain()))
                return false;

            if (config('b24app.app.b24_debug_portal') != Portals::getDomain())
                return false;
        }

        VarDumper::dump($var);

        foreach ($moreVars as $v)
            VarDumper::dump($v);

        if (1 < func_num_args())
            return func_get_args();

        return $var;
    }
}

if (!function_exists('lang')) {
    function lang(?string $lang = null): string
    {
        if ($lang) {
            app()->setLocale($lang);
            return $lang;
        }

        return app()->getLocale();
    }
}

if (!function_exists('portalLog')) {
    /**
     * Запись лога в портал, чтобы был в БД
     *
     * @param string $msg
     * @param array $data
     * @param int $portal_id
     * @return void
     */
    function portalLog(string $msg, array $data = [], int $portal_id = 0)
    {
        Controllers\LogController::portal($msg, $data, $portal_id);
    }
}

if (!function_exists('chLog')) {
    /**
     * Запись лога в нужный канал и возврат екземпляра канала
     *
     * @param string $msg Сообщение
     * @param array $data Массив для дампа
     * @param string $chanel Канал (необходимо создать)
     * @param int $portal_id ID портала, если нужно записать в лог портала
     * @return \Psr\Log\LoggerInterface
     * @example $log = chLog('Hello', [], 'jobs', 2);
     */
    function chLog(string $msg, array $data = [], string $chanel = 'daily', int $portal_id = 0)
    {
        $log = Log::channel($chanel);
        $log->info($msg, $data);

        if ($portal_id > 0)
            portalLog($msg, $data, $portal_id);

        return $log;
    }
}

if (!function_exists('isFree')) {
    function isFree()
    {
        return app(Controllers\LicenseController::class)->isFreeApp();
    }
}

if (!function_exists('static_link')) {
    function static_link(string $url, bool $force = false)
    {
        if ($force || config('app.debug', false))
            $url .= '?' . time();

        return $url;
    }
}

if (!function_exists('app_type')) {
    function app_type()
    {
        $host = $_SERVER['HTTP_HOST'] ?? false;
        if (empty($host))
            return false;

        $configs = \Flamix\B24App\Controllers\App\CoreLoader::loadAppsConfigurationsFile();
        if (empty($configs))
            return false;

        foreach ($configs as $type => $configs) {
            if (in_array($host, array_keys($configs)))
                return $type;
        }
    }
}
