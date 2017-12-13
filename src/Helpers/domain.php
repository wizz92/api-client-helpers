<?php
// if working in multidomain mode
// try to find config with this key
// if exists - return it.
// else take defaults
// in case no key are rewritten params are taken from default config (.env)
function conf(string $key = '', bool $allow_default = true)
{
    $domain_key = $_SERVER['SERVER_NAME'];
    $suf = $key ? '+'.$key : ''; 
    $config_file = $key ? array_sign(config('api_configs'))  : config('api_configs');
    return $allow_default ? array_get($config_file, $domain_key.$suf, array_get($config_file, 'defaults'.$suf)) : array_get($config_file, $domain_key.$suf, false);
}