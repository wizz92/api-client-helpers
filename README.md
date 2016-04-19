# api-client-helpers

This is pack of helper class to make speedy api client work

# Instalation


Edit composer.json, add

"minimum-stability": "dev",

Install package.

composer require wizz/api-client-helpers


edit config/app.php, add

Wizz\ApiClientHelpers\ApiClientHelpersServiceProvider::class,

to providers array.


use php artisan vendor:publish to publish api_configs.php file.

# Usage

init your token in router

use \Wizz\ApiClientHelpers\Token;


Route::get(
			'{slug?}', function($slug, Token $token, Request $request) 
		{
			
			if(!$token->init($request))
			{
				return $token->errors;
			}
			return view('index')
				->with('access_token', $token->getToken())
				->with('bootstrap', $token->getBootstrapData())
				;

		}
		)->where('slug', '.+');

That's all. 

All routes with prefix api will be proxy redirected to secret_url from .env file.



