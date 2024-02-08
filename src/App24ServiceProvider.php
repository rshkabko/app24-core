<?php

namespace Flamix\App24Core;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;
use Flamix\App24Core\Console\Commands as FlamixCommands;

class App24ServiceProvider extends ServiceProvider
{
    public function boot(Router $router)
    {
        // Middleware: App24
        $router->aliasMiddleware('B24App', Middleware\B24App::class);
        $router->aliasMiddleware('B24Settings', Middleware\B24Settings::class);

        // Middleware: User24
        $router->aliasMiddleware('B24User', Middleware\B24User::class);
        $router->aliasMiddleware('B24UserAdmin', Middleware\B24UserAdmin::class);

        // Register grouped Middleware "app24"
        $this->app->booted(function () use ($router) {
            $router->pushMiddlewareToGroup('app24', Middleware\B24App::class);
            $router->pushMiddlewareToGroup('app24', Middleware\B24Settings::class);
        });

        // Register grouped Middleware "user24". Some times we need use app without user
        $this->app->booted(function () use ($router) {
            $router->pushMiddlewareToGroup('user24', 'app24'); // Need app24 middleware by default
            $router->pushMiddlewareToGroup('user24', Middleware\B24User::class);
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
            FlamixCommands\refreshToken::class,
        ]);
    }
}