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
        $this->app->make('Wizz\ApiClientHelpers\Token');
    }
}
