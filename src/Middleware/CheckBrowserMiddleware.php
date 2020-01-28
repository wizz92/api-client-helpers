<?php

namespace Wizz\ApiClientHelpers\Middleware;

use Illuminate\Http\Response;
use Closure;
use Log;
use Browser;

class CheckBrowserMiddleware
{
    /**
     * @param Request $request
     * @param Closure $next
     * @return Response $next
     * @throws \Exception
     */
    public function handle(Request $request, Closure $next)
    {
        $browserFamily = Browser::browserFamily();
        $browserVersion = Browser::browserVersion();

        $projectName = $request->get('pname') ?? "";
        if (($browserFamily == 'Internet Explorer') || ($browserFamily == 'Opera Mobile')) {
            $request->attributes->add(['cache' => false]);
        }
        return $next($request);
    }
}