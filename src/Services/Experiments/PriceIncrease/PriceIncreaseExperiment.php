<?php

namespace Wizz\ApiClientHelpers\Services\Experiments\PriceIncrease;

use Wizz\ApiClientHelpers\Helpers\CacheHelper;
use Wizz\ApiClientHelpers\Helpers\CookieHelper;
use Wizz\ApiClientHelpers\Services\Experiments\ExperimentTrait;
use \Illuminate\Http\Request;

class PriceIncreaseExperiment
{
  use ExperimentTrait;

  public function run(Request $request, array $experiment_info)
  {
    $cookie_name = $experiment_info['cookie_name'];
    $experiment_groups = $experiment_info['groups'];
    $ab_group_from_cookie = $request->cookie($cookie_name);

    $group_from_query = $request->query('priceIncreaseGroup');
    if ($group_from_query) {
      $info = $experiment_info['groups'][$group_from_query] ?? false;
      if ($info) {
        return [
          'priceIncreaseValue' => $info['priceIncreaseValue'],
          'experimentGroup' => $group_from_query
        ];
      }
    }

    if (!$ab_group_from_cookie) {
      $weighted_values = $this->getWeightedValues($experiment_groups);
      $random_group_name = $this->getRandomWeightedGroup($weighted_values);
      $group_info = $experiment_groups[$random_group_name];

      return [
        'priceIncreaseValue' => $group_info['priceIncreaseValue'],
        'experimentGroup' => $random_group_name,
        'cookie' => [
          'name' => $cookie_name,
          'value' => $random_group_name,
        ]
      ];
    }

    $group_info = $experiment_groups[$ab_group_from_cookie];
    return [
      'priceIncreaseValue' => $group_info['priceIncreaseValue'],
      'experimentGroup' => $ab_group_from_cookie
    ];
  }
}
