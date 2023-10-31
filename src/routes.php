<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
    Route::get('uninstall/{hash}', [Controllers\InstallController::class, 'destroy'])->name('app24.uninstall');
});
