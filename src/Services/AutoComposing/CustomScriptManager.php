<?php

namespace Wizz\ApiClientHelpers\Services\AutoComposing;

use Wizz\ApiClientHelpers\Services\AutoComposing\Contracts\CustomScriptManagerInterface;
use Symfony\Component\DomCrawler\Crawler;
use DOMDocument;

class CustomScriptManager implements CustomScriptManagerInterface
{
    /**
     * @param $jsFile
     */
    public function add($jsFile)
    {
        $this->firstPageRedir($jsFile);
    }

    private function firstPageRedir($jsFile)
    {
        $pageNumber = request()->get('page');
        if ($pageNumber == 1) {
            unset($_GET['page']);
            $urlWithoutFirstPageNumber = request()->url() .'?'. http_build_query($_GET);
            $scriptForRedirect = "window.history.pushState({}, 'Hide', '{$urlWithoutFirstPageNumber}');";
            fwrite($jsFile, $scriptForRedirect."\n");
        }
    }
}
