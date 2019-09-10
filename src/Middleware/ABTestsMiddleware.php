<?php

namespace Wizz\ApiClientHelpers\Middleware;

use Wizz\ApiClientHelpers\Services\Getters\ClientConfigGetter;
use Wizz\ApiClientHelpers\Services\Experiments\DefaultAcademicLevel\DefaultAcademicLevelExperiment;
use Wizz\ApiClientHelpers\Services\Experiments\PriceIncrease\PriceIncreaseExperiment;
use Wizz\ApiClientHelpers\Helpers\CacheHelper;
use Wizz\ApiClientHelpers\Helpers\CookieHelper;
use Closure;
use Cookie;

class ABTestsMiddleware
{
    public function __construct(
        DefaultAcademicLevelExperiment $defaultAcademicLevelExperiment,
        PriceIncreaseExperiment $priceIncreaseExperiment
    ) {
        $this->defaultAcademicLevelExperiment = $defaultAcademicLevelExperiment;
        $this->priceIncreaseExperiment = $priceIncreaseExperiment;
        $app_id = CacheHelper::conf('client_id');
        $this->clientConfigGetter = new ClientConfigGetter($app_id);
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
        $experiments_results = [];
        $cookies_max_age = 10 * 365 * 24 * 60;
        $cookie = "";

        foreach ($experiments as $experiment_name => $experiment_info) {
            if (!array_get($experiment_info, 'enabled', false)) {
                return $next($request);
            }

            switch ($experiment_name) {
                case 'defaultAcademicLevel':
                    $experiment_result_info = $this->defaultAcademicLevelExperiment->run($request, $experiment_info);
                    $experiments_results['defaultAcademicLevelExperiment'] = [
                      'defaultAcademicLevel' => $experiment_result_info['academicLevelId'],
                      'defaultAcademicLevelExperimentGroup' => $experiment_result_info['experimentGroup']
                    ];
                    break;

                case 'priceIncrease':
                    $experiment_result_info = $this->priceIncreaseExperiment->run($request, $experiment_info);
                    $experiments_results['priceIncreaseExperiment'] = [
                      'priceIncreaseValue' => $experiment_result_info['priceIncreaseValue'],
                      'priceIncreaseExperimentGroup' => $experiment_result_info['experimentGroup']
                    ];
                    break;

                default:
                    return $next($request);
            }
        }

        if (array_key_exists('cookie', $experiment_result_info)) {
            list('name' => $name, 'value' => $value) = $experiment_result_info['cookie'];
            $cookie = $cookie . "$name=$value; Max-Age=$cookies_max_age";
        }

        $request->attributes->add([
          'experimentsResults' => $experiments_results
        ]);

        return $cookie
        ? $next($request)->header('Set-Cookie', $cookie)
        : $next($request);
    }
}
