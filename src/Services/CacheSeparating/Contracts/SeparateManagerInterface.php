<?php

namespace Wizz\ApiClientHelpers\Services\CacheSeparating\Contracts;

interface SeparateManagerInterface
{
    /**
     * @param int|null $appId
     * @param string|null $type
     * @return $this
     */
    public function clear($appId, $type);
}
