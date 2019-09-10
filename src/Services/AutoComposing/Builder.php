<?php
namespace Wizz\ApiClientHelpers\Services\AutoComposing;

use Wizz\ApiClientHelpers\Services\AutoComposing\Contracts\BuilderInterface;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Class Builder
 * @package Wizz\ApiClientHelpers\Service\TrafficSource
 */
class Builder implements BuilderInterface
{
    const STYLES = 'collectStyles';
    const SCRIPTS = 'collectScripts';

    const DOM = 'collectDOM';

    protected $crawler;

    /**
     * @return array
     */
    private function collectors($type)
    {
         $values = [
           self::STYLES => new StylesCollector($this->crawler),
           self::SCRIPTS => new ScriptsCollector($this->crawler),

           self::DOM => new DOMCollector($this->crawler),
         ];

         return $values[$type];
    }

    /**
     * @param string $method
     * @param string $pageContent
     * @param bool $updateContent
     * @return $this
     */
    public function make(string $method, string $pageContent, bool $updateContent = false)
    {
        if (!$this->crawler || $updateContent) {
            $this->crawler = new Crawler($pageContent);
        }

        return $this->collectors($method);
    }
}
