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

create upload and documents folders in public directory

# Usage

it will just work

That's all. 

All routes with prefix api will be proxy redirected to secret_url from .env file.




Funcs:
1. Receive and cache pages from remote page server.
2. proxy all requests to secret API
3. handles redirects
4. can work in multi site mode. (serve many websites from 1 client)
5. can block users with certain utm_marks or user_agents
6. can store all configs in db