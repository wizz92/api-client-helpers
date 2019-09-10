<?php

namespace Wizz\ApiClientHelpers\Services\AutoComposing;

use Wizz\ApiClientHelpers\Services\AutoComposing\Contracts\ComposingInterface;
use Symfony\Component\DomCrawler\Crawler;
use DOMDocument;

class DOMCollector implements ComposingInterface
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
    public function get(): array
    {
        $DOMDocument = new DOMDocument;
        libxml_use_internal_errors(true);
        $DOMDocument->loadHTML($this->crawler->html());
        libxml_use_internal_errors(false);
        $this->crawler = new Crawler($DOMDocument);
        $headStyles = implode(array_get($this->styles, 'head'));
        $bodyStyles = implode(array_get($this->styles, 'body'));

        $this->addElementToDOM($DOMDocument, 'style', $headStyles, 'head');
        $this->addElementToDOM($DOMDocument, 'style', $bodyStyles, 'body');
        $this->addElementToDOM($DOMDocument, 'script', array_get($this->scripts, 'body'), 'body');

        return ['html' => $this->crawler->html()];
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

        $element = $DOMDocument->createElement($tagToCreate);

        switch ($tagToCreate) {
            case 'style':
                $variable = $DOMDocument->createTextNode($tagInner);
                break;

            case 'script':
                $variable = $DOMDocument->createAttribute('src');
                $variable->value = $tagInner;
                $element->appendChild($variable);
                $variable = $DOMDocument->createAttribute('class');
                $variable->value = 'js-url';

                break;
        }

        $element->appendChild($variable);

        $this->crawler->add($element);
        $placement = $this->crawler->filter($tagToAttach)->first()->getNode(0);
        $placement->appendChild($element);
    }
}
