<?php

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

if (!function_exists('app24')) {
    function app24(int $id = 0): \Flamix\App24Core\App24
    {
        return \Flamix\App24Core\App24::getInstance($id);
    }
}

if (!function_exists('user24')) {
    function user24(): \Flamix\App24Core\User24
    {
        return \Flamix\App24Core\User24::getInstance();
    }
}

if (! function_exists('app24_url')) {
    function app24_url($path = null, $parameters = [], $secure = null)
    {
        $session_id = session()?->getId();
        $session_cookie_key = config('session.cookie');
        $url = url($path, $parameters, $secure);

        if ($session_cookie_key && $session_id && !isset($parameters[$session_cookie_key])) {
            $url .= str_contains($url, '?') ? '&' : '?';
            $url .= "{$session_cookie_key}={$session_id}";
        }

        return $url;
    }
}

if (!function_exists('app24_route')) {
    function app24_route(string $name, array $parameters = [], bool $absolute = true)
    {
        $session_id = session()?->getId();
        $session_cookie_key = config('session.cookie');
        if ($session_cookie_key && $session_id && !isset($parameters[$session_cookie_key])) {
            $parameters[$session_cookie_key] = $session_id;
        }

        return app('url')->route($name, $parameters, $absolute);
    }
}