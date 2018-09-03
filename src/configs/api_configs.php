<?php


return [
    'defaults' => [
        'grant_type' => env('grant_type', 'client_credentials'),
        'tracking_hits' => false,
        'security_code'     => env('security_code', 'qwe123'),
        'cache_frontend_for'=> env('cache_frontend_for', 60*24*31),
        'not_found_redirect_seconds' => env('not_found_redirect_seconds', 0),
        'not_found_redirect_code' => env('not_found_redirect_code', 301),
        'not_found_redirect_mode' => env('not_found_redirect_mode', 'http'), // other option is "view"
        'client_id'         => env('client_id', 1),
        'client_secret'     => env('client_secret', 'abc'),
        'url'               => env('url', 'api'),
        'secret_url'        => env('secret_url', 'http://localhost:8001'),
        'use_frontend_repo' => env('use_frontend_repo', false),
        'frontend_repo_url' => env('frontend_repo_url', 'https://localhost:8080/pc/'),
        'use_cache_frontend' => env('use_cache_frontend', true),
        'http_auth' => false,
        'alias_domain' => null,
        'pname_query' => true
    ],
    'domain.net' => [
        'client_id'         => 'test',
        'client_secret'     => env('client_secret', 'domain.net.client_secret'),
    ],
    'papercoach.net' => [
        'client_id'         => 'test',
        'client_secret'     => env('client_secret', 'domain.net.client_secret'),
    ],
    'languages' => [
        'en'
    ],
    'multilingualSites' => [
        'localhost'
    ]
];
