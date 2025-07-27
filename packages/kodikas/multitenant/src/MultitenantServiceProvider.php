<?php

namespace Kodikas\Multitenant;

use Illuminate\Support\ServiceProvider;
use Kodikas\Multitenant\Console\Commands\TenantMakeCommand;
use Kodikas\Multitenant\Console\Commands\TenantMigrateCommand;
use Kodikas\Multitenant\Console\Commands\TenantSeedCommand;
use Kodikas\Multitenant\Http\Middleware\EnsureTenantMiddleware;
use Kodikas\Multitenant\Http\Middleware\IdentifyTenantMiddleware;
use Kodikas\Multitenant\Http\Middleware\TenantAccessControlMiddleware;

class MultitenantServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/multitenant.php',
            'multitenant'
        );

        $this->app->singleton('tenant', function ($app) {
            return new TenantManager($app);
        });

        $this->app->bind('tenant.resolver', function ($app) {
            return new TenantResolver($app);
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'multitenant');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/multitenant.php' => config_path('multitenant.php'),
            ], 'multitenant-config');

            $this->publishes([
                __DIR__.'/../database/migrations/' => database_path('migrations'),
            ], 'multitenant-migrations');

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/multitenant'),
            ], 'multitenant-views');

            $this->commands([
                TenantMakeCommand::class,
                TenantMigrateCommand::class,
                TenantSeedCommand::class,
            ]);
        }

        // Register middleware
        $this->app['router']->aliasMiddleware('tenant.identify', IdentifyTenantMiddleware::class);
        $this->app['router']->aliasMiddleware('tenant.ensure', EnsureTenantMiddleware::class);
        $this->app['router']->aliasMiddleware('tenant.access', TenantAccessControlMiddleware::class);

        // Boot tenant resolver
        $this->app['tenant.resolver']->boot();
    }
}
