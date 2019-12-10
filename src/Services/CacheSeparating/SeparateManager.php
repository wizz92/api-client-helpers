<?php
namespace Wizz\ApiClientHelpers\Services\CacheSeparating;

use Wizz\ApiClientHelpers\Services\CacheSeparating\Contracts\SeparateManagerInterface;
use Wizz\ApiClientHelpers\Services\CacheSeparating\Contracts\CacheCleanerInterface;
use Illuminate\Support\Facades\Storage;
use Wizz\ApiClientHelpers\Helpers\CacheHelper;

/**
 * Class SeparateManager
 * @package Wizz\ApiClientHelpers\Service\CacheSeparating
 */
class SeparateManager implements SeparateManagerInterface
{
    /**
     * @param int|null $appId
     * @param string|null $type
     */
    public function clear(int $appId = null, string $type = null)
    {
        if (!$type && !$appId) {
            return ['error' => 'You should choose type of pages or client id'];
        }

        $paramsForUrl = [
            'app_id' => $appId,
            'type' => $type
        ];

        $withoutNull = array_filter($paramsForUrl);
        $dataWithUrls = CacheHelper::getSpasificListOfUrls($withoutNull);
        $paramsInString = implode(" ",$withoutNull);

        if (!isset($dataWithUrls->urls) || !$dataWithUrls) {
            return  ['error' => "We don't have any pages with this params: {$paramsInString}"];
          }
        
        return $this->separate($dataWithUrls, $appId, $type);
    }

    /**
     * @return array
     */
    private function separate($dataWithUrls, $appId, $type)
    {
        switch (true) {
            case !$appId && $type:
                $cleaner = new CacheCleanerByType();
                break;
            
            case $appId && !$type:
                $cleaner = new CacheCleanerByAppId();
                break;
            
            case $appId && $type:
                $cleaner = new CacheCleanerByAppIdAndType();
                break;
        }

        return $cleaner->run($dataWithUrls, $appId, $type);
    }
}
