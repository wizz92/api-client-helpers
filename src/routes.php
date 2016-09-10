<?php 

Route::get('/r/{slug?}', '\Wizz\ApiClientHelpers\ACHController@redirect')->where('slug', '.+');

Route::any('api/{slug?}', '\Wizz\ApiClientHelpers\ACHController@proxy')->where('slug', '.*');

Route::get('/t/check', '\Wizz\ApiClientHelpers\ACHController@check');

Route::get('/t/clear_cache', '\Wizz\ApiClientHelpers\ACHController@clear_cache');

if(env('use_frontend_repo') === true)
{
	/*

	This check is here to ensure that we are not fucking up projects,

	where frontend_repo is not explicitly enabled.

	*/
	Route::get('{slug?}', '\Wizz\ApiClientHelpers\ACHController@frontend_repo')->where('slug', '.+');	

}

