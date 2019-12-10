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
     * @param  array $dataWithUrls
     * @param  int|null $appId
     * @param  string|null $type
     *
     * @return void|array
     */
    public function run(array $dataWithUrls, int $appId = null, string $type = null)
    {
        foreach ($dataWithUrls as $key => $info) {
            $domain = array_get($info, 'domain', null);
            $appId = array_get($info, 'app_id', null);
            $urls = array_get($info, 'urls', []);

            $this->cacheCleanHelper->clearComposingFiles("{$domain}/{$type}s");

            foreach ($urls as $url) {
                $this->cacheCleanHelper->clearCacheByKey($appId, $type, $url);
            }
           
            $this->cacheCleanHelper->clearCacheForCustomPages($appId, $type);
        }

        return $this->cacheCleanHelper->errors ? $this->cacheCleanHelper->errors : ['result' => "Cache for all {$type}s was deleted"];
    }
}
