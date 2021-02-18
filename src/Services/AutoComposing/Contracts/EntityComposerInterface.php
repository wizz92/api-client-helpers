<?php


namespace Wizz\ApiClientHelpers\Services\AutoComposing\Contracts;


interface EntityComposerInterface
{
    public function get(string $cacheKey, string $entityName): array;
}