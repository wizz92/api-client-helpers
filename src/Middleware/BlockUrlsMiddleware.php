<?php

namespace Wizz\ApiClientHelpers\Middleware;

use Closure;
// use Input;
use Log;

class BlockUrlsMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $from_url = $request->server('HTTP_REFERER');
        $utm_host = $request->input('utm_host', '');
        $referrer_has_gov = (strpos($from_url, '.gov') !== false) ? true : false;
        $utm_host_has_gov = (strpos($utm_host, '.gov') !== false) ? true : false;

        if($referrer_has_gov || $utm_host_has_gov) 
        {
            return redirect("https://google.com");
        }

        if(!$utm_host && !$from_url)
        {
            return $next($request);
        }
        
        foreach (config('api_configs.list_of_urls_to_block') as $url => $destination) {
            
            $host = ($utm_host && strpos($utm_host, $url) !== false) ? $utm_host : false;
            // dd($host);
            $host = (!$host && $from_url && strpos($from_url, $url) !== false) ? $from_url : $host;
            $s = 'Blocking visitor for utm from ' . $utm_host . '.';
            if($request->has('rt'))
            {
                $s = $s . ' Link code was ' . $request->input('rt');
            }
            Log::info($s);
            if ($host) {
                return redirect($destination);
            }
        }
        return $next($request);
    }
}
