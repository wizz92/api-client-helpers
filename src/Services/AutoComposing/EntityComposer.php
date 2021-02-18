<?php


namespace Wizz\ApiClientHelpers\Services\AutoComposing;

use Cache;
use Symfony\Component\DomCrawler\Crawler;
use Wizz\ApiClientHelpers\Helpers\CacheHelper;
use Wizz\ApiClientHelpers\Services\AutoComposing\Contracts\EntityComposerInterface;

abstract class EntityComposer implements EntityComposerInterface
{
    const COMPOSED_ENTITY_CACHE_LIFETIME_IN_SEC = 60 * 24 * 2;

    /**
     * @var Page Crawler object
     */
    protected $crawler;

    /**
     * StylesCollector constructor.
     * @param Crawler $crawler
     */
    public function __construct(Crawler $crawler)
    {
        $this->crawler = $crawler;
    }

    /**
     * @param string $cacheKey Cache key for entities compose result
     * @param string $entityName Config selector prefix
     * @return array
     */
    public function get(string $cacheKey, string $entityName): array
    {
        $composedEntities = [];
        $limitedCacheKey = CacheHelper::getLimitedCacheKey($cacheKey);
        $headSelectors = config("compose_configs.selectors.$entityName.head");
        $bodySelectors = config("compose_configs.selectors.$entityName.body");
        if (Cache::has($limitedCacheKey) || !CacheHelper::shouldWeCache()) {
            $composedEntities = Cache::get($limitedCacheKey);
        }
        else {
            $composedEntities = $this->compose($headSelectors, $bodySelectors);
            Cache::put($limitedCacheKey, $composedEntities, self::COMPOSED_ENTITY_CACHE_LIFETIME_IN_SEC);
        }
        $this->clearComposedEntitiesFromDom(array_merge($bodySelectors, $headSelectors));
        return $composedEntities;
    }

    private function clearComposedEntitiesFromDom(array $entitiesSelectors) {
        foreach ($entitiesSelectors as $selector => $ignoredOptions) {
            $this->removeEntitiesFromDomByFilter($selector);
        }
    }

    private function removeEntitiesFromDomByFilter(string $filter)
    {
        $this->crawler->filter($filter)->each(function (Crawler $node) {
            $node = $node->getNode(0);
            $node->parentNode->removeChild($node);
        });
    }

    abstract protected function compose(array $headSelectors, array $bodySelectors): array;
}