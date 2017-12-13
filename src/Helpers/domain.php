<?php
// if working in multidomain mode
// try to find config with this key
// if exists - return it.
// else take defaults
// in case no key are rewritten params are taken from default config (.env)
function from_config(string $key)
{
    $config_file = array_sign(config('api_configs'));
    $domain_key = $_SERVER['SERVER_NAME'];
    return array_get($config_file, $domain_key.'+'.$key, array_get($config_file, 'defaults'.'+'.$key));
}