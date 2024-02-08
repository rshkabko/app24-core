<?php

use Illuminate\Support\Facades\Route;
use Flamix\App24Core\Controllers;

/**
 * WEB
 */
Route::group(['prefix' => 'app24', 'middleware' => ['web']], function () {
    Route::any('install', [Controllers\InstallController::class, 'install'])->name('app24.install');
});

/**
 * API
 *
 * API routing didn't work with SESSION
 */
Route::group(['prefix' => 'app24', 'middleware' => ['api', 'throttle:600,1']], function () {
    Route::post('uninstall/{hash}', [Controllers\InstallController::class, 'destroy'])->name('app24.uninstall');
});