<?php

namespace Wizz\ApiClientHelpers\Middleware;

use Wizz\ApiClientHelpers\Services\Getters\ClientConfigGetter;
use Wizz\ApiClientHelpers\Services\Experiments\DefaultAcademicLevel\DefaultAcademicLevelExperiment;
use Wizz\ApiClientHelpers\Helpers\CacheHelper;
use Closure;

class ABTestsMiddleware
{
    public function __construct(DefaultAcademicLevelExperiment $defaultAcademicLevelExperiment)
    {
      $this->defaultAcademicLevelExperiment = $defaultAcademicLevelExperiment;
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
      $experiment_info = $this->clientConfigGetter->getExperimentConfig('defaultAcademicLevel');

      if (!$experiment_info['enabled']) {
        return $next($request);
      }

      $experiment_result_info = $this->defaultAcademicLevelExperiment->run($request, $experiment_info);

      $experiments_results = [
        'defaultAcademicLevelExperiment' => [
          'defaultAcademicLevel' => $experiment_result_info['academicLevelId'],
          'defaultAcademicLevelExperimentGroup' => $experiment_result_info['experimentGroup']
        ]
      ];

      $request->attributes->add([
        'experimentsResults' => $experiments_results
      ]);

      return array_key_exists('cookie', $experiment_result_info) 
        ? $next($request)->cookie(
          $experiment_result_info['cookie']['name'], 
          $experiment_result_info['cookie']['value'], 
          10 * 365 * 24 * 60, '/', null, false, false)
        : $next($request);
    }
}
