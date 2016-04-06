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
            __DIR__.'configs/api_config.php' => config_path('api_config.php'),
        ]);
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //
        include __DIR__.'/routes.php';
        include __DIR__.'/Helpers/array.php';
        include __DIR__.'/Helpers/cookies.php';
        include __DIR__.'/Helpers/request.php';
        $this->mergeConfigFrom(
            __DIR__.'configs/api_config.php', 'api_config'
        );
        $this->app->make('Wizz\ApiClientHelpers\Token');
    }
}
