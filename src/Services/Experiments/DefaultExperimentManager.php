<?php

namespace Wizz\ApiClientHelpers\Services\Experiments;

use Wizz\ApiClientHelpers\Helpers\CacheHelper;
use Wizz\ApiClientHelpers\Helpers\CookieHelper;
use Wizz\ApiClientHelpers\Services\Experiments\ExperimentTrait;
use Wizz\ApiClientHelpers\Services\Experiments\DefaultExperimentManagerInterface;
use \Illuminate\Http\Request;

class DefaultExperimentManager implements DefaultExperimentManagerInterface
{
    use ExperimentTrait;

    public function run(Request $request, array $experimentInfo, string $type)
    {
        $cookieName = $experimentInfo['cookie_name'];
        $experimentGroups = $experimentInfo['groups'];
        $abGroupFromCookie = $request->cookie($cookieName);

        $groupFromQuery = $request->query("{$type}Group");
        if ($groupFromQuery) {
            $info = $experimentInfo['groups'][$groupFromQuery] ?? false;
            if ($info) {
                return [
                  "{$type}Value" => $info["{$type}Value"],
                  'experimentGroup' => $groupFromQuery,
                    'cookie' => [
                        'name' => $cookieName,
                        'value' => $groupFromQuery,
                    ]
                ];
            }
        }

        if (!$abGroupFromCookie) {
            $weightedValues = $this->getWeightedValues($experimentGroups);
            $randomGroupName = $this->getRandomWeightedGroup($weightedValues);
            $groupInfo = $experimentGroups[$randomGroupName];

            return [
              "{$type}Value" => $groupInfo["{$type}Value"],
              'experimentGroup' => $randomGroupName,
              'cookie' => [
                'name' => $cookieName,
                'value' => $randomGroupName,
              ]
            ];
        }

        $groupInfo = $experimentGroups[$abGroupFromCookie];
        return [
          "{$type}Value" => $groupInfo["{$type}Value"],
          'experimentGroup' => $abGroupFromCookie
        ];
    }
}
