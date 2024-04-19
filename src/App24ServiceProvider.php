<?php

namespace Flamix\App24Core;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;
use Flamix\App24Core\Console\Commands as App24Commands;

class App24ServiceProvider extends ServiceProvider
{
    public function boot(Router $router)
    {
        // Middleware: App24
        $router->aliasMiddleware('App24', Middleware\App24::class);
        $router->aliasMiddleware('App24Settings', Middleware\App24Settings::class);
        $router->aliasMiddleware('SaveDomain', Middleware\SaveDomain::class);

        // Middleware: User24
        $router->aliasMiddleware('User24', Middleware\User24::class);
        $router->aliasMiddleware('User24Admin', Middleware\User24Admin::class);

        $this->app->booted(function () use ($router) {
            // Register grouped Middleware "app24"
            $router->pushMiddlewareToGroup('app24', 'web'); // Session, Cookie, CFRF is required
            $router->pushMiddlewareToGroup('app24', Middleware\StartSession::class);
            $router->pushMiddlewareToGroup('app24', Middleware\SaveDomain::class);
            $router->pushMiddlewareToGroup('app24', Middleware\App24::class);
            $router->pushMiddlewareToGroup('app24', Middleware\App24Settings::class);

            // Lighten version of app24, some times we can extend it
            $router->pushMiddlewareToGroup('app24-api', 'web');  // Session, Cookie, CFRF is required
            $router->pushMiddlewareToGroup('app24-api', Middleware\App24::class);
            $router->pushMiddlewareToGroup('app24-api', Middleware\App24Settings::class);

            // Register grouped Middleware "user24". Some times we need use app without user
            $router->pushMiddlewareToGroup('user24', 'app24'); // Need app24 middleware by default
            $router->pushMiddlewareToGroup('user24', Middleware\User24::class);
        });

        // Translations
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'app24');
        // Views
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'app24-core');
        // Routes
        $this->loadRoutesFrom(__DIR__ . '/routes.php');

        // Publishing is only necessary when using the CLI.
        if ($this->app->runningInConsole()) {
            $this->bootForConsole();
        }
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/app24.php', 'app24');

        // Include Our Exeption
        if (!method_exists(\App\Exceptions\App24Exception::class, 'report')) {
            include_once 'Exceptions/App24Exception.php';
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['app24'];
    }

    /**
     * Console-specific booting.
     *
     * @return void
     */
    protected function bootForConsole()
    {
        // Publishing the configuration file.
        $this->publishes([
            __DIR__ . '/../config/app24.php' => config_path('app24.php'),
        ], 'app24.config');

        $this->publishes([
            __DIR__ . '/../database/migrations/' => database_path('migrations'),
        ], 'migrations');

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Registering package commands.
        $this->commands([
            App24Commands\refreshToken::class,
            App24Commands\Install::class,
        ]);
    }
}