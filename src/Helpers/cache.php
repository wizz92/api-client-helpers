<?php
use Wizz\ApiClientHelpers\Helpers\ConfigHelper;
        /*

    Function to see if we should be caching response from frontend repo.

    If $slug is passed, it will also check whether this $slug is already in cache;

    */
    function should_we_cache($ck = false)
    {
        if(conf('use_cache_frontend') === false) return false;

        if(request()->input('cache') === 'false') return false;

        if(!app()->environment('production')) return false;
        if($ck && !Cache::has($ck)) return false;

        return true;
    }

    function conf(string $key = '', bool $allow_default = true){
        return ConfigHelper::get(string $key = '', bool $allow_default = true);
    }




    function CK($slug) //CK = Cache Key
    {
        $slug = request()->fullUrl(); //request()->getHttpHost().$slug;
        $ua = strtolower(request()->header('User-Agent'));
        $slug = $ua && strrpos($ua, 'msie') > -1 ? "_ie_".$slug : $slug;
        return md5($slug);
    }