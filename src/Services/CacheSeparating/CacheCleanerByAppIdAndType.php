<?php
namespace Wizz\ApiClientHelpers\Services\CacheSeparating;

use Wizz\ApiClientHelpers\Services\CacheSeparating\Contracts\CacheCleanerInterface;
use Wizz\ApiClientHelpers\Services\CacheSeparating\CacheCleanHelper;

/**
 * Class CacheCleanerByAppIdAndType
 * @package Wizz\ApiClientHelpers\Service\CacheSeparating
 */
class CacheCleanerByAppIdAndType implements CacheCleanerInterface
{
    protected $cacheCleanHelper;

    public function __construct(CacheCleanHelper $cacheCleanHelper)
    {
        $this->cacheCleanHelper = $cacheCleanHelper;
    }

    /**
     * removing cache for certain client id and type
     *
     * @param  mixed $dataWithUrls
     * @param  int|null $appId
     * @param  string|null $type
     *
     * @return void|array
     */
    public function run($dataWithUrls, int $appId = null, string $type = null)
    {
        $domain = $dataWithUrls->domain;
        $urls = $dataWithUrls->urls;

        $this->cacheCleanHelper->clearComposingFiles("{$domain}/{$type}s");
  
        foreach ($urls as $key => $url) {
            $this->cacheCleanHelper->clearCacheByKey($appId, $type, $url);
        }
        
        $this->cacheCleanHelper->clearCacheForCustomPages($appId, $type);

        return $this->cacheCleanHelper->errors ? $this->cacheCleanHelper->errors : ['result' => "Cache for {$type} type in {$domain} project was deleted"];
    }
}
