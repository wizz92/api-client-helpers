<?php

namespace Wizz\ApiClientHelpers\Services\AutoComposing\Contracts;

interface BuilderInterface
{
    /**
     * @param string $method
     * @param string $pageContent
     * @param bool $updateContent
     * @return $this
     */
    public function make(string $method, string $pageContent, bool $updateContent = false);
}
