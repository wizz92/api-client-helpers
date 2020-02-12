<?php
namespace Wizz\ApiClientHelpers\Services\CacheSeparating;

use Wizz\ApiClientHelpers\Services\CacheSeparating\Contracts\CacheCleanerInterface;
use Wizz\ApiClientHelpers\Services\CacheSeparating\CacheCleanHelper;
use Cache;

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
     * @param  string|null $domain
     * @param  int|null $appId
     * @param  string|null $type
     *
     * @return void|array
     */
    public function run($domain, $appId, $type)
    {
        $this->cacheCleanHelper->clearComposingFiles("{$domain}");
        Cache::tags([$appId])->flush();

        return View::make('api-client-helpers::cache', ['result' => "Cache for {$domain} project was deleted"]);
    }
}
