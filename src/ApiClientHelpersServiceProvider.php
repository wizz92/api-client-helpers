<?php

namespace Wizz\ApiClientHelpers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\AliasLoader;

class ApiClientHelpersServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {

        include __DIR__.'/ACHController.php';
        // include __DIR__.'/Helpers/cache.php';
        $this->mergeConfigFrom(__DIR__.'/configs/api_configs.php', 'api_configs');
        $this->app->make('Wizz\ApiClientHelpers\Token');
        $this->app->make('Wizz\ApiClientHelpers\ACHController');
        $this->app->make('Wizz\ApiClientHelpers\Middleware\BlockUrlsMiddleware');
        $this->app->make('Wizz\ApiClientHelpers\Middleware\UpdateGlobalsMiddleware');

        $this->publishes([
            __DIR__.'/configs/api_configs.php' => config_path('api_configs.php'),
        ]);
        $this->loadRoutesFrom(__DIR__.'/routes.php');
        $this->loadViewsFrom(__DIR__.'/views', 'api-client-helpers');

        AliasLoader::getInstance()->alias('Httpauth', 'Intervention\Httpauth\Facades\Httpauth');
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->register('Intervention\Httpauth\Httpauth');
        // include __DIR__.'/routes.php';
        // $this->loadViewsFrom(__DIR__.'/views', 'api-client-helpers');

        // include __DIR__.'/ACHController.php';
        // include __DIR__.'/Helpers/cache.php';
        // $this->mergeConfigFrom(__DIR__.'/configs/api_configs.php', 'api_configs');
        // $this->app->make('Wizz\ApiClientHelpers\Token');
        // $this->app->make('Wizz\ApiClientHelpers\ACHController');
        // $this->app->make('Wizz\ApiClientHelpers\Middleware\BlockUrlsMiddleware');
        // $this->app->make('Wizz\ApiClientHelpers\Middleware\UpdateGlobalsMiddleware');
    }
}
