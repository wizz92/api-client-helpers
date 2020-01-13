<?php

namespace Wizz\ApiClientHelpers\Services\CacheSeparating\Contracts;

interface CacheCleanerInterface
{
    /**
     * @param  string|null $domain
     * @param  int|null $appId
     * @param  string|null $type
     *
     * @return void|array
     */
    
    public function run(string $domain = null, int $appId = null, string $type = null);
}
