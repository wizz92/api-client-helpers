<?php
use Wizz\ApiClientHelpers\Middleware\BlockUrlsMiddleware;
use Wizz\ApiClientHelpers\Middleware\UpdateGlobalsMiddleware;
use Wizz\ApiClientHelpers\Middleware\ABTestsMiddleware;
use Wizz\ApiClientHelpers\Middleware\CheckBrowserMiddleware;

Route::get('/cache-clear', '\Wizz\ApiClientHelpers\ACHController@clear');
Route::get('/sitemap.xml', '\Wizz\ApiClientHelpers\ACHController@proxy')->where('slug', '.+');
// here we setup proxy for robots which leads to api
// into api we get robots from mongo_config or from robots generator
Route::get('/robots.txt', '\Wizz\ApiClientHelpers\ACHController@proxy')->where('slug', '.+');

Route::get('/robots_generator', '\Wizz\ApiClientHelpers\ACHController@proxy')->where('slug', '.+');

Route::get('/r/{slug?}', '\Wizz\ApiClientHelpers\ACHController@redirect')->where('slug', '.+');

Route::get('order_form/{order_id}/{token}', '\Wizz\ApiClientHelpers\ACHController@outerAuthForm');

Route::any('api/{slug?}', '\Wizz\ApiClientHelpers\ACHController@proxy')->where('slug', '.*');
Route::get('assets/{slug?}', '\Wizz\ApiClientHelpers\ACHController@proxy')->where('slug', '.*');

Route::get('/t/check', '\Wizz\ApiClientHelpers\ACHController@check');

Route::get('/t/clear_cache', '\Wizz\ApiClientHelpers\ACHController@clearCache');
// TODO do we need this here?
Route::get('clients/payments/success', function () {
    $params = http_build_query(request()->all());
    return redirect()->to('https://api.speedy.company/clients/payments/success?'.$params);
})->where('slug', '.+');

Route::get('clients/payments/failure', function () {
    $params = http_build_query(request()->all());
    return redirect()->to('https://api.speedy.company/clients/payments/failure?'.$params);
})->where('slug', '.+');

Route::get('payments/failure', function () {
    $params = http_build_query(request()->all());
    return redirect()->to('https://api.speedy.company/clients/payments/failure?'.$params);
})->where('slug', '.+');

Route::get('payments/cancel', function () {
    $params = http_build_query(request()->all());
    return redirect()->to('https://api.speedy.company/clients/payments/cancel?'.$params);
})->where('slug', '.+');

Route::get('payments/pending', function () {
    $params = http_build_query(request()->all());
    return redirect()->to('https://api.speedy.company/clients/payments/pending?'.$params);
})->where('slug', '.+');
// TODO change to use in multisite mode.
if (env('use_frontend_repo') === true) {
    /*

    This check is here to ensure that we are not fucking up projects,

    where frontend_repo is not explicitly enabled.

    */
    Route::get('{slug?}', '\Wizz\ApiClientHelpers\ACHController@frontendRepo')
        ->middleware(UpdateGlobalsMiddleware::class)
        ->middleware(BlockUrlsMiddleware::class)
//        ->middleware(ABTestsMiddleware::class)
        ->middleware(CheckBrowserMiddleware::class)
        ->middleware(\Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class)
        ->where('slug', '.+');
}
