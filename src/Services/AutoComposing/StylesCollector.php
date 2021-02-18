<?php

namespace Wizz\ApiClientHelpers\Services\AutoComposing;

use Symfony\Component\DomCrawler\Crawler;
use Wizz\ApiClientHelpers\Services\AutoComposing\EntityComposer;

class StylesCollector extends EntityComposer
{
    const HTTPS_CONNECTION_OPTIONS = [
        "ssl" => [
            "verify_peer"=>false,
            "verify_peer_name"=>false,
        ]
    ];

    protected function compose(array $headSelectors, array $bodySelectors): array
    {
        $headStyles = $this->getSectionStylesArray($headSelectors);
        $bodyStyles = $this->getSectionStylesArray($headSelectors);
        return [
            'head' => $headStyles,
            'body' => $bodyStyles
        ];
    }

    private function getStylesByFilter(string $filter, bool $shouldUseHttp = false) : array
    {
        $connectionContext = $shouldUseHttp ? null : stream_context_create(self::HTTPS_CONNECTION_OPTIONS);
        return $this->crawler->filter($filter)->each(function (Crawler $node) use ($shouldUseHttp, $connectionContext) {
            $link = $shouldUseHttp ?  : $node->attr('href');
            if ($shouldUseHttp) {
                $link = str_replace('https:', 'http:', $node->attr('href'));
                return file_get_contents($link);
            }
            return file_get_contents($link, false, $connectionContext);
        });
    }

    private function getSectionStylesArray(array $sectionSelectors) : array
    {
        $sectionStyles = [];
        foreach ($sectionSelectors as $selector => $selectorOptions) {
            array_merge($this->getStylesByFilter($selector, $selectorOptions['use_http']), $sectionStyles);
        }
        return $sectionStyles;
    }
}
