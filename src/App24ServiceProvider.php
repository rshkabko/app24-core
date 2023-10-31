<?php

namespace Flamix\App24Core;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;
use Flamix\App24Core\Console\Commands as FlamixCommands;

class App24ServiceProvider extends ServiceProvider
{
    public function boot(Router $router)
    {
        // Register Middleware
        $router->aliasMiddleware('B24App', Middleware\B24App::class);

        // Need User
        $router->aliasMiddleware('B24User', Middleware\B24User::class);
        $router->aliasMiddleware('B24UserAdmin', Middleware\B24UserAdmin::class);

        // Register grouped Middleware "app24"
        $this->app->booted(function () use ($router) {
            $router->pushMiddlewareToGroup('app24', Middleware\B24App::class);
            $router->pushMiddlewareToGroup('app24', Middleware\B24User::class);
            $router->pushMiddlewareToGroup('app24', Middleware\B24UserAdmin::class);
        });

        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'flamix');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'app24-core');

        $this->loadRoutesFrom(__DIR__ . '/routes.php');

        // Publishing is only necessary when using the CLI.
        if ($this->app->runningInConsole())
            $this->bootForConsole();
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
        include_once 'Exceptions/FxException.php';

        // Register the service the package provides.
        /*$this->app->singleton('b24app', function ($app) {
            return new B24App;
        });*/
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
            FlamixCommands\refreshToken::class,
        ]);
    }
}
