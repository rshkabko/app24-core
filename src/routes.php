<?php

use Illuminate\Support\Facades\Route;
use Flamix\App24Core\Controllers\InstallController;

Route::group(['prefix' => 'app24'], function () {
    // Install
    Route::post('install', [InstallController::class, 'install'])->middleware(['web', 'SaveDomain'])->name('app24.install');
    // Delete
    Route::post('uninstall/{hash}', [InstallController::class, 'destroy'])->middleware(['api', 'App24'])->name('app24.uninstall');
});