<?php

namespace Wizz\ApiClientHelpers\Services\AutoComposing;

use Wizz\ApiClientHelpers\Services\AutoComposing\Contracts\ComposingInterface;
use Symfony\Component\DomCrawler\Crawler;
use Illuminate\Support\Facades\Storage;

class StylesCollector implements ComposingInterface
{
    /**
     * StylesCollector constructor.
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
     * @param string $project_name
     * @return array
     * @throws \Exception
     */
    public function get(): array
    {
        $outerHeadStyles = $this->crawler->filter('head > link.outer-link')->each(function (Crawler $node, $i) {
            $link = $node->attr('href');
            $opts = [
                "ssl" => [
                    "verify_peer"=>false,
                    "verify_peer_name"=>false,
                ]
            ];
            $context = stream_context_create($opts);
            $targetFileContent = file_get_contents($link, false, $context);
            foreach ($node as $n) {
                $n->parentNode->removeChild($n);
            }
            return $targetFileContent;
        });

        $innerHeadStyles = $this->getStyles('head > link.styles-section');
        $bodyStyles = $this->getStyles('body > link.bottom-styles-section');

        return [
          'head' => array_merge($outerHeadStyles, $innerHeadStyles),
          'body' => $bodyStyles
        ];
    }

    private function getStyles($filter)
    {
        return $this->crawler->filter($filter)->each(function (Crawler $node, $i) {
            $link = $node->attr('href');
            $link = str_replace('https:', 'http:', $link);
            $targetFileContent = file_get_contents($link);
            foreach ($node as $n) {
                $n->parentNode->removeChild($n);
            }
            return $targetFileContent;
        });
    }
}
