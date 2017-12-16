<?php

namespace Wizz\ApiClientHelpers\Middleware;

use Closure;
use Log;

class UpdateGlobalsMiddleware
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
        session(['addition' => $request->all()]);
        
        return $next($request);
    }
}
