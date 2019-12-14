<?php
namespace Wizz\ApiClientHelpers\Services\CacheSeparating;

use Wizz\ApiClientHelpers\Services\CacheSeparating\Contracts\CacheCleanerInterface;
use Wizz\ApiClientHelpers\Services\CacheSeparating\CacheCleanHelper;

/**
 * Class CacheCleanerByType
 * @package Wizz\ApiClientHelpers\Service\CacheSeparating
 */
class CacheCleanerByType implements CacheCleanerInterface
{
    protected $cacheCleanHelper;

    public function __construct(CacheCleanHelper $cacheCleanHelper)
    {
        $this->cacheCleanHelper = $cacheCleanHelper;
    }

    /**
     * removing cache for certain type
     *
     * @param  mixed $dataWithUrls
     * @param  int|null $appId
     * @param  string|null $type
     *
     * @return void|array
     */
    public function run($dataWithUrls, int $appId = null, string $type = null)
    {
        foreach ($dataWithUrls as $key => $info) {
            $domain = $info->domain ?? null;
            $appId = $info->app_id ?? null;
            $urls = $info->urls ?? [];

            $this->cacheCleanHelper->clearComposingFiles("{$domain}/{$type}s");
            $this->cacheCleanHelper->clearCacheForCustomPages($appId, $type);

            foreach ($urls as $url) {
                $this->cacheCleanHelper->clearCacheByKey($appId, $type, $url);
            }
        }

        return $this->cacheCleanHelper->errors ? $this->cacheCleanHelper->errors : ['result' => "Cache for all {$type}s was deleted"];
    }
}
