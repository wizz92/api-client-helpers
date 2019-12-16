<?php
namespace Wizz\ApiClientHelpers\Helpers;

use Wizz\ApiClientHelpers\Helpers\ArrayHelper;
use Cache;
use Cookie;

class CacheHelper
{
    /**
     * Function to see if we should be caching response from frontend repo.
     * If $slug is passed, it will also check whether this $slug is already in cache;
     */
    public static function shouldWeCache($ck = false)
    {
        if (self::conf('use_cache_frontend') === false) {
            return false;
        }
        if (request()->input('cache') === 'false') {
            return false;
        }
        if (request()->query('page', false)) {
          return false;
        }
        if (request()->query('rate', false)) {
          return false;
        }
        if (request()->is('services/bootstrap')) {
          return false;
        }

        return true;
    }

    public static function conf(string $key = '', bool $allow_default = true)
    {
        $domain_key = env('use_landings_repo', false)
            ? request()->get('pname')
            : self::getDomain();
        $suf = $key ? '+'.$key : '';
        $config_file = $key ? ArrayHelper::sign(config('api_configs'), $prepend = '', $sign = '+', $ignore_array = true)  : config('api_configs');
        return $allow_default ? array_get($config_file, $domain_key.$suf, array_get($config_file, 'defaults'.$suf)) : array_get($config_file, $domain_key.$suf, false);
    }

    /**
     * @return string
     */
    public static function getDomain()
    {
        if (app()->environment('production')) {
          return array_get($_SERVER, 'HTTP_HOST', '');
        }
        $switchDomain = request()->get('domain');
        $pname = request()->get('pname');
        if ($switchDomain) {
            return $switchDomain;
        }
        if ($pname) {
            return $pname;
        }
        return array_get($_SERVER, 'HTTP_HOST', '');
    }

    /**
     * remove all cookies
     * @return void
     */
    public static function forgetCookie()
    {
        foreach ($_COOKIE as $name => $value) {
            setcookie($name, null, -1);
        }
    }

    /**
     * @param string $domain
     * @return string
     */
    public static function setDomain($domain)
    {
        session()->put('current_domain', $domain);
        return $domain;
    }

    public static function CK($slug) //CK = Cache Key
    {
        $slug = request()->url(); //request()->getHttpHost().$slug;
        $ua = strtolower(request()->header('User-Agent'));
        $slug = $ua && strrpos($ua, 'msie') > -1 ? "_ie_".$slug : $slug;
        return md5($slug);
    }

        /**
    * returns data from cache or calls a function from second parameter and puts result in cache
    *
    * @param {key} cache key
    * @param {data_function} function to call if key is not found in cache
    * @param {lifetime} minutes to store in cache
    * @param {rewrite} should we force rewrite even if data is available in cache?
    *
    * @return error if data_function is not a function or data from cache if key is found or result of data_function if key is not found in cache
    */

    public static function cacher($key, $data_function, $life_time = 1000, $skip = false, $disableSWC = false)
    {
        if (!is_callable($data_function)) {
            throw new Exception('cacher function expects second parameter to be a function '.gettype($data_function).' given.');
        }
        // if we have logical reasons to not cache content -> just call function and return result
        if (!self::shouldWeCache($key) && !$disableSWC) {
          return call_user_func($data_function);
        }

        if ($skip) {
          return call_user_func($data_function);          
        }

        // if we can cache -> check if we have cache value by given key -> return from cache
        if (Cache::has($key)) {
            return Cache::get($key);
        }

        // if we haven`t cache value by given key -> call user function and return result
        $data = call_user_func($data_function);
        Cache::put($key, $data, $life_time);
        return $data;
    }

    public static function attachCORSToResponse($response) {
      $origin = request()->header('Origin') ?? '';
      $allowed_origins = self::conf('allowed_origins') ?? [];

      $allowed_origins = is_array($allowed_origins) ? $allowed_origins : [$allowed_origins];

      if (in_array($origin, $allowed_origins)) {
        $response->header('Access-Control-Allow-Origin', $origin)
          ->header('Access-Control-Allow-Credentials', 'true')
          ->header('Access-Control-Allow-Methods', 'POST, GET, OPTIONS, DELETE')
          ->header('Access-Control-Allow-Headers', 'Content-Type, Accept, X-Requested-With, remember-me');
      }

      return $response;
    }

    public static function getSpasificListOfUrls($params)
    {
        $appId = CacheHelper::conf('client_id');
        $clientSecret = CacheHelper::conf('client_secret');
        $paramsInString = http_build_query($params);

        $cacheKey = "all_pages_url_by_params_{$paramsInString}";
        $skipCache = isset($params['type']) ? true : false;

        $urls = self::cacher($cacheKey, function() use ($appId, $clientSecret, $paramsInString, &$skipCache) {
          
            $query = env('secret_url')."/get-pages-url?client_id=$appId&client_secret=$clientSecret&{$paramsInString}";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $query);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $res = curl_exec($ch);
            curl_close($ch);
    
            $result = json_decode($res);
            
            if (!is_object($result) || (property_exists($result, 'errors') && count($result->errors) > 0)) {
              $skipCache = true;
              return false;
            }

            return $result->data ?? false;
          }, 60*24*30, $skipCache);
        return $urls;
    }
}
