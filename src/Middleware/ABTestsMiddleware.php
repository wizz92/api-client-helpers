<?php

namespace Wizz\ApiClientHelpers\Middleware;

use Wizz\ApiClientHelpers\ACHController;
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
        $this->appId = $appId;
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
        $experimentResultInfo = [];
        $experiments = $this->clientConfigGetter->getExperimentsInfo();
        $experimentsResults = [];
        $cookiesMaxAge = 10 * 365 * 24 * 60;
        $detect = new \Mobile_Detect();
        $isSpeedyPaper = ACHController::SPEEDYPAPER_DOMAIN == $request->getHttpHost();
        $cookies = [];
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
                case 'tooltip':
                    $experimentResultInfo = $this->defaultExperimentMamager->run($request, $experimentInfo, 'tooltip');
                    $experimentsResults['tooltipExperiment'] = [
                        'tooltipValue' => $experimentResultInfo['tooltipValue'],
                        'tooltipExperimentGroup' => $experimentResultInfo['experimentGroup']
                    ];
                    break;
                case 'desktop':
                    $experimentResultInfo = $this->defaultExperimentMamager->run($request, $experimentInfo, 'desktop');
                    $experimentsResults['desktopExperiment'] = [
                        'desktopValue' => $experimentResultInfo['desktopValue'],
                        'desktopExperimentGroup' => $experimentResultInfo['experimentGroup']
                    ];
                    break;
                case 'cashback':
                    $experimentResultInfo = $this->defaultExperimentMamager->run($request, $experimentInfo, 'cashback');
                    $experimentsResults['cashbackExperiment'] = [
                        'cashbackValue' => $experimentResultInfo['cashbackValue'],
                        'cashbackExperimentGroup' => $experimentResultInfo['experimentGroup']
                    ];
                    break;
            }

            if (array_key_exists('cookie', $experimentResultInfo)) {
                if ($detect->isMobile() && $isSpeedyPaper && $experimentResultInfo['cookie']['name'] == 'PAGE_REDIRECT') {
                    $pageRedirect = $experimentResultInfo['cookie']['value'];
                    if ($pageRedirect == 'FI1' && request()->path() == '/') {
                        $slug = 'free-inquiry-new-design';
                        return redirect($slug . $this->getQueryParams($request))
                                ->withCookie($experimentResultInfo['cookie']['name'], $experimentResultInfo['cookie']['value'], $cookiesMaxAge);
                    }
                }
                if ($experimentResultInfo['cookie']['name'] == 'PAGE_REDIRECT' && Cookie::get('PAGE_REDIRECT')) {
                    $experimentResultInfo['cookie']['value'] = 'FI2';
                }
                $cookies += [$experimentResultInfo['cookie']['name'] => $experimentResultInfo['cookie']['value']];
            }
        }

        $request->attributes->add([
          'experimentsResults' => $experimentsResults
        ]);
        $desktop = null;
        if (!$detect->isMobile()) {
            $desktop = isset($_COOKIE['DESKTOP']) ? $_COOKIE['DESKTOP'] : $cookies['DESKTOP'] ?? 'EC1';
        }

        return $next($request)
            ->cookie('PAGE_REDIRECT', $cookies['PAGE_REDIRECT'] ?? 'FI1')
            ->cookie('TOP_WRITER_NOTIF', $_COOKIE['TOP_WRITER_NOTIF'] ?? $cookies['TOP_WRITER_NOTIF'] ?? 'PG1')
            ->cookie('PRO_WRITER_NOTIF', $_COOKIE['PRO_WRITER_NOTIF'] ?? $cookies['PRO_WRITER_NOTIF'] ?? 'PH1')
            ->cookie('TOOLTIP', $_COOKIE['TOOLTIP'] ?? $cookies['TOOLTIP'] ?? 'TO1')
            ->cookie('CASHBACK', 'CASHBACK_GROUP_B')
            ->cookie('DESKTOP', $desktop ?? 'EC1');
    }


    protected function getQueryParams($request)
    {
        $getQueryParams = http_build_query($request->query());
        return $getQueryParams ? '?'.$getQueryParams : null;
    }

    protected function getCashbackTagFromReferrer($request, $cookies)
    {
        if (!isset($request->query()['rt']) && isset($cookies['CASHBACK'])) {
            return $cookies['CASHBACK'];
        }
    }
}
