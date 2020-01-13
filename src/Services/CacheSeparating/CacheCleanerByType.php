<?php
namespace Wizz\ApiClientHelpers\Services\CacheSeparating;

use Wizz\ApiClientHelpers\Services\CacheSeparating\Contracts\CacheCleanerInterface;
use Wizz\ApiClientHelpers\Services\CacheSeparating\CacheCleanHelper;
use Cache;

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
     * @param  string|null $domain
     * @param  int|null $appId
     * @param  string|null $type
     *
     * @return void|array
     */
    public function run($domain, $appId, $type)
    {
        $this->cacheCleanHelper->clearComposingFiles("{$type}s");
        Cache::tags([$type.'s'])->flush();

        return ['result' => "Cache for all {$type}s was deleted"];
    }
}
