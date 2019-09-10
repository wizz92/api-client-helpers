<?php

namespace Wizz\ApiClientHelpers\Services\AutoComposing\Contracts;

interface ComposingInterface
{
    /**
     * @return array
     */
    public function get(): array;

    /**
     * @param string $name
     * @param string|array $value
     */
    public function add(string $name, $value);
}
