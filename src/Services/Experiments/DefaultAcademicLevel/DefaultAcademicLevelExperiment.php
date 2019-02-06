<?php

namespace Wizz\ApiClientHelpers\Services\Experiments\DefaultAcademicLevel;

use Wizz\ApiClientHelpers\Helpers\CacheHelper;
use Wizz\ApiClientHelpers\Helpers\CookieHelper;
use Wizz\ApiClientHelpers\Services\Experiments\ExperimentTrait;
use \Illuminate\Http\Request;

class DefaultAcademicLevelExperiment
{
  use ExperimentTrait;

  public function run(Request $request, array $experiment_info)
  {
    $cookie_name = $experiment_info['cookie_name'];
    $experiment_groups = $experiment_info['groups'];
    $ab_group_from_cookie = $request->cookie($cookie_name);

    if (!$ab_group_from_cookie) {
      $weighted_values = $this->getWeightedValues($experiment_groups);
      $random_group_name = $this->getRandomWeightedGroup($weighted_values);
      $group_info = $experiment_groups[$random_group_name];

      return [
        'academicLevelId' => $group_info['academicLevelId'],
        'experimentGroup' => $random_group_name,
        'cookie' => [
          'name' => $cookie_name,
          'value' => $random_group_name,
        ]
      ];
    }

    $group_info = $experiment_groups[$ab_group_from_cookie];
    return [
      'academicLevelId' => $group_info['academicLevelId'],
      'experimentGroup' => $ab_group_from_cookie
    ];
  }
  // potentialy useless
  // public function getExperimentInfo($bootstrap_data)
  // {
  //   $default_info = [
  //     'enabled' => false
  //   ];
  //   $info = $bootstrap_data->experiments->defaultAcademicLevel ?? null;
    
  //   if (!$info) {
  //     return $default_info;
  //   }

  //   $info = json_decode(json_encode($info), true); // convert info from object to array

  //   return $info;
  // }
}
