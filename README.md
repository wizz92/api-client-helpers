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

it will just work

That's all. 

All routes with prefix api will be proxy redirected to secret_url from .env file.



