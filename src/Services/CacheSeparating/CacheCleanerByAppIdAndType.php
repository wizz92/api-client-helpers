<?php
namespace Wizz\ApiClientHelpers\Services\CacheSeparating;

use Wizz\ApiClientHelpers\Services\CacheSeparating\Contracts\CacheCleanerInterface;
use Wizz\ApiClientHelpers\Services\CacheSeparating\CacheCleanHelper;
use Cache;

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
     * @param  string|null $domain
     * @param  int|null $appId
     * @param  string|null $type
     *
     * @return void|array
     */
    public function run(string $domain = null, int $appId = null, string $type = null)
    {
        $this->cacheCleanHelper->clearComposingFiles("{$domain}/{$type}s");

        $tag = "{$appId}_{$type}s";
        Cache::tags([$tag])->flush();

        return ['result' => "Cache for {$type} type in {$domain} project was deleted"];
    }
}
