<?php

/**
 * Bootstrap file for package tests.
 * Loads the application autoloader and registers the package test namespace.
 */

// Try to find vendor/autoload.php by walking up from the real path of this file
// Works both when package is symlinked (local dev) and when installed normally (CI)
$autoloadPaths = [
    __DIR__ . '/../../autoload.php',           // Normal: vendor/flamix/app24-core/tests -> vendor/autoload.php
    __DIR__ . '/../vendor/autoload.php',       // Package root: app24-core/vendor/autoload.php
    getcwd() . '/vendor/autoload.php',         // CWD: laravel-project/vendor/autoload.php
];

$autoloaded = false;
foreach ($autoloadPaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $autoloaded = true;
        break;
    }
}

if (!$autoloaded) {
    fwrite(STDERR, "Cannot find vendor/autoload.php. Run composer install first.\n");
    exit(1);
}

// Register autoloader for package tests namespace
spl_autoload_register(function ($class) {
    $prefix = 'Flamix\\App24Core\\Tests\\';
    $baseDir = __DIR__ . '/';

    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});
