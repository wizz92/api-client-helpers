<?php
use Wizz\ApiClientHelpers\Helpers\ArrayHelper;

        /*

    Function to see if we should be caching response from frontend repo.

    If $slug is passed, it will also check whether this $slug is already in cache;

    */
function should_we_cache($ck = false)
{
    if (conf('use_cache_frontend') === false) {
        return false;
    }
    if (request()->input('cache') === 'false') {
        return false;
    }
    if (!app()->environment('production')) {
        return false;
    }
    if ($ck && !Cache::has($ck)) {
        return false;
    }

    return true;
}

function conf(string $key = '', bool $allow_default = true)
{
    $domain_key = getDomain();
    $suf = $key ? '+'.$key : '';
    $config_file = $key ? ArrayHelper::sign(config('api_configs'), $prepend = '', $sign = '+', $ignore_array = true)  : config('api_configs');
    return $allow_default ? array_get($config_file, $domain_key.$suf, array_get($config_file, 'defaults'.$suf)) : array_get($config_file, $domain_key.$suf, false);
}

    /**
     * @return string
     */
function getDomain()
{
    $switchDomain = request()->get('domain') && request()->get('domain_change_code') == 'limpopo' ? request()->get('domain') : false;
    if ($switchDomain) {
        forgetCookie();
        return setDomain($switchDomain);
    }
    $domainFromSession = session()->get('current_domain');
    if ($domainFromSession) {
        return $domainFromSession;
    }
    return setDomain(array_get($_SERVER, 'SERVER_NAME', ''));
}

    /**
     * remove all cookies
     * @return void
     */
function forgetCookie()
{
    foreach ($_COOKIE as $name => $value) {
        setcookie($name, null, -1);
    }
}

    /**
     * @param string $domain
     * @return string
     */
function setDomain($domain)
{
    session()->put('current_domain', $domain);
    return $domain;
}

function CK($slug) //CK = Cache Key
{
    $slug = request()->fullUrl(); //request()->getHttpHost().$slug;
    $ua = strtolower(request()->header('User-Agent'));
    $slug = $ua && strrpos($ua, 'msie') > -1 ? "_ie_".$slug : $slug;
    return md5($slug);
}
