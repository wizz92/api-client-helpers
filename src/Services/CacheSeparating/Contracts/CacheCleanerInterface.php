<?php

namespace Wizz\ApiClientHelpers\Services\CacheSeparating\Contracts;

interface CacheCleanerInterface
{
    /**
     * @param  array $dataWithUrls
     * @param  int|null $appId
     * @param  string|null $type
     *
     * @return void|array
     */
    public function run(array $dataWithUrls, int $appId = null, string $type = null);
}
