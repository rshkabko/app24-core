<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Flamix\App24Core\Controllers;

/**
 * WEB
 */
Route::group(['prefix' => 'b24app', 'middleware' => ['web']], function () {
    Route::any('installing', [Controllers\InstallController::class, 'installing']);
});

/**
 * API
 *
 * API routing didn't work with SESSION
 */
Route::group(['prefix' => 'b24app', 'middleware' => ['api', 'throttle:600,1', 'lang']], function () {
    // Событие удаление портала (ранее, мы регистрировали обработчик на удаления по этому URL)
    Route::post('uninstall', [Controllers\InstallController::class, 'destroy']);

    /*
     * Для сохранения настроек я использую этот пакет - https://github.com/anlutro/laravel-settings
     * Естественно, я его немного переделал при вызове контроллеров :)
     */
    Route::group(['prefix' => 'settings', 'middleware' => ['B24App', 'B24Settings', 'CheckApiToken', 'isAccessedType']], function () {
        Route::any('save', [Controllers\SettingController::class, 'saveSettings']);
        Route::any('delete', [Controllers\SettingController::class, 'deleteSettings']);
        Route::get('all', [Controllers\SettingController::class, 'getAllSettings']);
        Route::get('get', [Controllers\SettingController::class, 'getSetting']);
    });
});
