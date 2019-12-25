<?php

namespace Wizz\ApiClientHelpers\Services\AutoComposing;

use Wizz\ApiClientHelpers\Services\AutoComposing\Contracts\CustomScriptManagerInterface;
use Symfony\Component\DomCrawler\Crawler;
use DOMDocument;

class CustomScriptManager implements CustomScriptManagerInterface
{
    /**
     * @param $jsFile
     * @param bool $addCustomScript
     */
    public function add($jsFile, bool $addCustomScript = false)
    {
        if ($addCustomScript) {
            $this->firstPageRedir($jsFile);
        }
    }

    private function firstPageRedir($jsFile)
    {
        $pageNumber = request()->get('page');
        if ($pageNumber == 1) {
            unset($_GET['page']);
            $url = 'https://' . $_SERVER['HTTP_HOST'] . parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
            $urlWithoutFirstPageNumber = count($_GET) ? $url .'?'. http_build_query($_GET) : $url;

            $scriptForRedirect = "
            var urlParams = window.location.search.substring(1)
            if (urlParams) {
                var params = urlParams.split('&')
                var queryParams = {}
                params.forEach((item) => {
                    var param = item.split('=')
                    queryParams[param[0]] = param[1]
                })
                if (queryParams.page && queryParams.page == 1) {
                    window.history.pushState({}, 'Hide', '{$urlWithoutFirstPageNumber}');
                }
            }";

            fwrite($jsFile, $scriptForRedirect."\n");
        }
    }
}
