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
    )
    {
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
      $cookie_string = "";

      foreach ($experiments as $experiment_name => $experiment_info) {
        switch ($experiment_name) {
          case 'defaultAcademicLevel':
            if (!$experiment_info['enabled']) {
              break;
            }
      
            $experiment_result_info = $this->defaultAcademicLevelExperiment->run($request, $experiment_info);
      
            $experiments_results['defaultAcademicLevelExperiment'] = [
              'defaultAcademicLevel' => $experiment_result_info['academicLevelId'],
              'defaultAcademicLevelExperimentGroup' => $experiment_result_info['experimentGroup']
            ];

            if (array_key_exists('cookie', $experiment_result_info)) {
              list('name' => $name, 'value' => $value) = $experiment_result_info['cookie'];
              $new_cookie = "$name=$value; Max-Age=$cookies_max_age";
              $cookie_string = $cookie_string ? $cookie_string . ",$new_cookie" : $cookie_string . $new_cookie;
            }
            break;
          case 'priceIncrease':
            if (!$experiment_info['enabled']) {
              break;
            }

            $experiment_result_info = $this->priceIncreaseExperiment->run($request, $experiment_info);

            $experiments_results['priceIncreaseExperiment'] = [
              'priceIncreaseValue' => $experiment_result_info['priceIncreaseValue'],
              'priceIncreaseExperimentGroup' => $experiment_result_info['experimentGroup']
            ];

            if (array_key_exists('cookie', $experiment_result_info)) {
              list('name' => $name, 'value' => $value) = $experiment_result_info['cookie'];
              $new_cookie = "$name=$value; Max-Age=$cookies_max_age";
              $cookie_string = $cookie_string ? $cookie_string . ",$new_cookie" : $cookie_string . $new_cookie;
            }
            break;
          
          default:
            return $next($request);
        }
      }

      $request->attributes->add([
        'experimentsResults' => $experiments_results
      ]);

      return $cookie_string 
        ? $next($request)->header('Set-Cookie', $cookie_string)
        : $next($request);
    }
}
