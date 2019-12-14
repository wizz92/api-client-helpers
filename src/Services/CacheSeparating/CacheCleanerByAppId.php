<?php
namespace Wizz\ApiClientHelpers\Services\CacheSeparating;

use Wizz\ApiClientHelpers\Services\CacheSeparating\Contracts\CacheCleanerInterface;
use Wizz\ApiClientHelpers\Services\CacheSeparating\CacheCleanHelper;

/**
 * Class CacheCleanerByAppId
 * @package Wizz\ApiClientHelpers\Service\CacheSeparating
 */
class CacheCleanerByAppId implements CacheCleanerInterface
{
    protected $cacheCleanHelper;

    public function __construct(CacheCleanHelper $cacheCleanHelper)
    {
        $this->cacheCleanHelper = $cacheCleanHelper;
    }

    /**
     * removing cache for certain client id
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
        unset($dataWithUrls->domain);

        foreach ($dataWithUrls as $key => $info) {
            $type = $info->type;
            $urls = $info->urls;
  
            $this->cacheCleanHelper->clearComposingFiles("{$domain}");
            $this->cacheCleanHelper->clearCacheForCustomPages($appId, $type);
  
            foreach ($urls as $key => $url) {
                $this->cacheCleanHelper->clearCacheByKey($appId, $type, $url);
            }
        }

        return $this->cacheCleanHelper->errors ? $this->cacheCleanHelper->errors : ['result' => "Cache for for {$domain} project was deleted"];
    }
}
