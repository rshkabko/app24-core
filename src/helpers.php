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