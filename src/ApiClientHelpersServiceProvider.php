<?php

namespace Wizz\ApiClientHelpers;

use Illuminate\Support\ServiceProvider;

class ApiClientHelpersServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/configs/api_configs.php' => config_path('api_configs.php'),
        ]);
        $this->loadRoutesFrom(__DIR__.'/routes.php');
        $this->loadViewsFrom(__DIR__.'/views', 'api-client-helpers');
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        // include __DIR__.'/routes.php';
        include __DIR__.'/ACHController.php';
        include __DIR__.'/Helpers/cache.php';
        include __DIR__.'/Helpers/validation.php';
        // $this->loadViewsFrom(__DIR__.'/views', 'api-client-helpers');
        $this->mergeConfigFrom(__DIR__.'/configs/api_configs.php', 'api_configs');
        $this->app->make('Wizz\ApiClientHelpers\Token');
        $this->app->make('Wizz\ApiClientHelpers\ACHController');
        $this->app->make('Wizz\ApiClientHelpers\Middleware\BlockUrlsMiddleware');
    }
}
