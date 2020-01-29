<?php

namespace Wizz\ApiClientHelpers\Middleware;

use Illuminate\Http\Request;
use Closure;
use Log;
use Browser;

class CheckBrowserMiddleware
{
    public $unsupportedBrowsers = [
        'Internet Explorer',
        'Opera Mini',
        'Opera Mobile'
    ];

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
        if (in_array($browserFamily, $this->unsupportedBrowsers)) {
            $request['cache'] = 'false';
        }
        return $next($request);
    }
}
