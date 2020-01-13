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
    public function clear($appId, $type)
    {
        if (!$type && !$appId) {
            return ['error' => 'You should choose type of pages or client id'];
        }

        $configData = array_values(array_filter(config('api_configs'), function($index) use ($appId) {
            return array_get($index, 'client_id') == $appId;
        }))[0];

        $domain = $configData['domain'] ?? null;
        return $this->separate($domain, $appId, $type);
    }

    /**
     * @return array
     */
    private function separate($domain, $appId, $type)
    {
        switch (true) {
            case !$appId && $type:
                $cleaner = CacheCleanerByType::class;
                break;
            
            case $appId && !$type:
                $cleaner = CacheCleanerByAppId::class;
                break;
            
            case $appId && $type:
                $cleaner = CacheCleanerByAppIdAndType::class;
                break;
        }
        
        return app()->make($cleaner)->run($domain, $appId, $type);
    }
}
