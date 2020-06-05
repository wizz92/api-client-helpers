<?php

namespace Wizz\ApiClientHelpers\Middleware;

use Wizz\ApiClientHelpers\Services\Getters\ClientConfigGetter;
use Wizz\ApiClientHelpers\Services\Experiments\DefaultExperimentManagerInterface;
use Wizz\ApiClientHelpers\Helpers\CacheHelper;
use Wizz\ApiClientHelpers\Helpers\CookieHelper;
use Closure;
use Cookie;

class ABTestsMiddleware
{
    public function __construct(DefaultExperimentManagerInterface $defaultExperimentMamager)
    {
        $this->defaultExperimentMamager = $defaultExperimentMamager;
        $appId = CacheHelper::conf('client_id');
        $this->clientConfigGetter = new ClientConfigGetter($appId);
    }
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $experiments = $this->clientConfigGetter->getExperimentsInfo();
        $experimentsResults = [];
        $cookiesMaxAge = 10 * 365 * 24 * 60;

        foreach ($experiments as $experimentName => $experimentInfo) {
            if (!array_get($experimentInfo, 'enabled', false)) {
                return $next($request);
            }

            switch ($experimentName) {
                case 'priceIncrease':
                    $experimentResultInfo = $this->defaultExperimentMamager->run($request, $experimentInfo, 'priceIncrease');
                    $experimentsResults['priceIncreaseExperiment'] = [
                      'priceIncreaseValue' => $experimentResultInfo['priceIncreaseValue'],
                      'priceIncreaseExperimentGroup' => $experimentResultInfo['experimentGroup']
                    ];
                    break;

                case 'topWriterNotification':
                    $experimentResultInfo = $this->defaultExperimentMamager->run($request, $experimentInfo, 'topWriterNotification');
                    $experimentsResults['topWriterNotificationExperiment'] = [
                      'topWriterNotificationValue' => $experimentResultInfo['topWriterNotificationValue'],
                      'topWriterNotificationExperimentGroup' => $experimentResultInfo['experimentGroup']
                    ];
                    break;
                case 'proWriterNotification':
                    $experimentResultInfo = $this->defaultExperimentMamager->run($request, $experimentInfo, 'proWriterNotification');
                    $experimentsResults['proWriterNotificationExperiment'] = [
                        'proWriterNotificationValue' => $experimentResultInfo['proWriterNotificationValue'],
                        'proWriterNotificationExperimentGroup' => $experimentResultInfo['experimentGroup']
                    ];
                    break;
                case 'pageRedirectVersion':
                    $experimentResultInfo = $this->defaultExperimentMamager->run($request, $experimentInfo, 'pageRedirectVersion');
                    $experimentsResults['pageRedirectVersionExperiment'] = [
                        'pageRedirectVersionValue' => $experimentResultInfo['pageRedirectVersionValue'],
                        'pageRedirectVersionExperimentGroup' => $experimentResultInfo['experimentGroup']
                    ];
                    break;
                case 'pageRedirectDesktop':
                    $experimentResultInfo = $this->defaultExperimentMamager->run($request, $experimentInfo, 'pageRedirectDesktop');
                    $experimentsResults['pageRedirectDesktopExperiment'] = [
                        'pageRedirectDesktopValue' => $experimentResultInfo['pageRedirectDesktopValue'],
                        'pageRedirectDesktopExperimentGroup' => $experimentResultInfo['experimentGroup']
                    ];
                    break;

                default:
                    return $next($request);
            }
            if (array_key_exists('cookie', $experimentResultInfo)) {
                $request->cookie($experimentResultInfo['cookie']['name'], $experimentResultInfo['cookie']['value'], $cookiesMaxAge);
            }
        }

        $request->attributes->add([
          'experimentsResults' => $experimentsResults
        ]);

        return $next($request);
    }
}
