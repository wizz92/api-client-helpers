<?php

namespace Wizz\ApiClientHelpers\Services\CacheSeparating\Contracts;

interface SeparateManagerInterface
{
    /**
     * @param string $method
     * @param string $pageContent
     * @param bool $updateContent
     * @return $this
     */
    public function clear(int $appId = null, string $type = null);
}
