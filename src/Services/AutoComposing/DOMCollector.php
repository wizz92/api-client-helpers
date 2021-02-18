<?php

namespace Wizz\ApiClientHelpers\Services\AutoComposing;

use Wizz\ApiClientHelpers\Services\AutoComposing\Contracts\ComposingInterface;
use Wizz\ApiClientHelpers\Helpers\CacheHelper;
use Symfony\Component\DomCrawler\Crawler;
use DOMDocument;
use Cache;

class DOMCollector
{
    protected $styles;
    protected $bodyScripts;

    /**
     * DOMCollector constructor.
     * @param Crawler $crawler
     */
    public function __construct(Crawler $crawler)
    {
        $this->crawler = $crawler;
    }

    /**
     * @param string $name
     * @param string|array $value
     */
    public function add(string $name, $value)
    {
        $this->$name = $value;
        return $this;
    }

    /**
     * @return array
     */
    public function get() : string
    {
        $DOMDocument = new DOMDocument;
        libxml_use_internal_errors(true);
        $DOMDocument->loadHTML($this->crawler->html());
        libxml_use_internal_errors(false);
        $this->crawler = new Crawler($DOMDocument);
        if (config('compose_configs.composeConditions.styles')) {
            $headStyles = implode(array_get($this->styles, 'head'));
            $bodyStyles = implode(array_get($this->styles, 'body'));
            $this->addElementToDOM($DOMDocument, 'style', $headStyles, 'head');
            $this->addElementToDOM($DOMDocument, 'style', $bodyStyles, 'body');
        }
        return $this->crawler->html();
    }

    /**
     * add element to DOM
     * @param DOMDocument $DOMDocument
     * @param string      $tagToCreate
     * @param string      $tagInner
     * @param string      $tagToAttach
     */
    private function addElementToDOM(DOMDocument $DOMDocument, string $tagToCreate, string $tagInner, string $tagToAttach)
    {
        $appId = CacheHelper::conf('client_id');
        $domain = CacheHelper::conf('domain');
        
        $element = $DOMDocument->createElement($tagToCreate);

        switch ($tagToCreate) {
            case 'style':
                $variable = $DOMDocument->createTextNode($tagInner);
                break;

            case 'script':
                $variable = $DOMDocument->createAttribute('src');
                $path = array_get(parse_url($tagInner), 'path', []);
                $pageType = explode('/', $path)[4] ?? 'generals';
                $version = Cache::tags([$appId, $domain, $pageType, "{$appId}_{$pageType}"])->get(md5("{$appId}_{$domain}") ?? '1453ErRor');
                
                $variable->value = $tagInner."?v=$version";
                $element->appendChild($variable);
                $variable = $DOMDocument->createAttribute('class');
                $variable->value = 'js-url';

                break;
        }

        $element->appendChild($variable);

        $this->crawler->add($element);
        $placement = $this->crawler->filter($tagToAttach)->first()->getNode(0);
        if ($placement) {
            $placement->appendChild($element);
        }
    }
}
