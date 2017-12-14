<?php

use Wizz\ApiClientHelpers\Helpers\ArrayHelper;
// if working in multidomain mode
// try to find config with this key
// if exists - return it.
// else take defaults
// in case no key are rewritten params are taken from default config (.env)
function conf(string $key = '', bool $allow_default = true)
{
    // TODO fix miltilevel
    $domain_key = array_get($_SERVER,'SERVER_NAME' ,'');
    $suf = $key ? '+'.$key : ''; 
    // dd($suf);
    
    $config_file = $key ? ArrayHelper::array_sign(config('api_configs'), $prepend = '', $sign = '+', $ignore_array = true)  : config('api_configs');
    // dd($config_file);
    return $allow_default ? array_get($config_file, $domain_key.$suf, array_get($config_file, 'defaults'.$suf)) : array_get($config_file, $domain_key.$suf, false);
}