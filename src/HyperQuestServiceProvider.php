<?php

namespace AhmetAksoy\HyperQuest;

use Illuminate\Support\ServiceProvider;

class HyperQuestServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

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
        $this->mergeConfigFrom(__DIR__.'/../config/hyperquest.php', 'hyperquest');

        // Register the service the package provides.
        // $this->app->singleton('hyperquest', function ($app) {
        //     return new HyperQuest;
        // });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['hyperquest'];
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
            __DIR__.'/../config/hyperquest.php' => config_path('hyperquest.php'),
        ], 'hyperquest.config');
    }
}
