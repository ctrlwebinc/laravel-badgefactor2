<?php

namespace Ctrlweb\BadgeFactor2;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Ctrlweb\BadgeFactor2\Console\Commands\MigrateWooCommerceData;
use Ctrlweb\BadgeFactor2\Console\Commands\MigrateWordPressUsers;

class CoreServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->registerPublishing();
            $this->commands([
                MigrateWordPressUsers::class,
                MigrateWooCommerceData::class,
            ]);
        }

        $this->registerResources();
    }

    /**
     * Register the package's publishable resources.
     *
     * @return void
     */
    protected function registerPublishing()
    {
        $this->publishes(
            [
                __DIR__.'/Console/stubs/BadgeFactor2ServiceProvider.stub' => app_path('Providers/BadgeFactor2ServiceProvider.php'),
            ],
            'bf2-provider'
        );

        $this->publishes(
            [
                __DIR__.'/../config/badgefactor2.php' => config_path('badgefactor2.php'),
            ],
            'bf2-config'
        );

        $this->publishes(
            [
                __DIR__.'/../public' => public_path('vendor/badgefactor2'),
            ],
            'bf2-assets'
        );

        $this->publishes(
            [
                __DIR__.'/../resources/lang' => resource_path('lang/vendor/badgefactor2'),
            ],
            'bf2-lang'
        );

        $this->publishes(
            [
                __DIR__.'/../resources/views/partials' => resource_path('views/vendor/badgefactor2/partials'),
            ],
            'bf2-views'
        );

        $this->publishes(
            [
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ],
            'bf2-migrations'
        );
    }

    /**
     * Register the package resources such as routes, templates, etc.
     *
     * @return void
     */
    protected function registerResources()
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'badgefactor2');
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'badgefactor2');
        $this->loadJsonTranslationsFrom(resource_path('lang/vendor/badgefactor2'));
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->registerRoutes();
    }

    /**
     * Register the package routes.
     *
     * @return void
     */
    protected function registerRoutes()
    {
        Route::group($this->routeConfiguration(), function () {
            $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        });
    }

    /**
     * Get the BadgeFactor2 route group configuration array.
     *
     * @return array
     */
    protected function routeConfiguration()
    {
        return [
            'namespace' => 'Ctrlweb\BadgeFactor2\Http\Controllers',
            'domain'    => config('badgefactor2.domain', null),
            'prefix'    => 'bf2-api',
            //'middleware' => 'bf2',
        ];
    }
}
