<?php

namespace Wizz\ApiClientHelpers\Services\AutoComposing;

use Wizz\ApiClientHelpers\Services\AutoComposing\Contracts\ComposingInterface;
use Wizz\ApiClientHelpers\Services\AutoComposing\Contracts\CustomScriptManagerInterface;
use Wizz\ApiClientHelpers\Helpers\CacheHelper;
use Symfony\Component\DomCrawler\Crawler;
use Illuminate\Support\Facades\Storage;

class ScriptsCollector implements ComposingInterface
{
    /**
     * ScriptsCollector constructor.
     * @param Crawler $crawler
     */
    public function __construct(Crawler $crawler)
    {
        $this->crawler = $crawler;
        $this->customScriptManager = app()->make(CustomScriptManagerInterface::class);
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
        $viewRoot = CacheHelper::getDomain();
        $composedDirectoryName = "composed/{$viewRoot}";

        // $path = preg_match("/\//", $this->path) ? preg_replace("/\//", '-', $this->path) : $this->path;
        
        $result = $this->getNewDirNameAndPath($composedDirectoryName);

        $composedDirectoryName = $result['directoryName'];
        $path = $result['path'];
        $addScriptForRedirect = $result['addScriptForRedirect'];


        if (!Storage::disk('public_assets')->exists($composedDirectoryName)) {
            Storage::disk('public_assets')->makeDirectory($composedDirectoryName);
        }
        $bodyJSInStorage = "{$composedDirectoryName}/body-{$path}.js";
        $bodyJSFileName = "assets/$bodyJSInStorage";
        
        if (Storage::disk('public_assets')->exists($bodyJSInStorage) && Storage::disk('public_assets')->size($bodyJSInStorage) != 0) {
            $this->crawler->filter('body > script.js-scripts-section')->each(function (Crawler $node, $i) {
                foreach ($node as $n) {
                    $n->parentNode->removeChild($n);
                }
            });
        } else {
            $jsFile = fopen($bodyJSFileName, 'a+');
            if (flock($jsFile, LOCK_EX | LOCK_NB)) {
                ftruncate($jsFile, 0);
                
                $this->crawler->filter('body > script.js-scripts-section')->each(function (Crawler $node, $i) use ($jsFile) {
                    $script = $node->attr('src');
                    $script = str_replace('https:', 'http:', $script);
                    foreach ($node as $n) {
                        $targetFileContent = file_get_contents($script);
                        fwrite($jsFile, $targetFileContent."\n");
                        $n->parentNode->removeChild($n);
                    }
                });

                $this->customScriptManager->add($jsFile, $addScriptForRedirect);
                fflush($jsFile);
                flock($jsFile, LOCK_UN);
            } else {
                sleep(7);
            }

            fclose($jsFile);
        }

        $rootUrl = env('root_url', 'https://' . request()->getHttpHost());
        return [
             'body' => "{$rootUrl}/{$bodyJSFileName}",
         ];
    }
    
    /**
     * get new castom dir and file names for composing files
     *
     * @param  string $composedDirectoryName
     *
     * @return void
     */
    private function getNewDirNameAndPath(string $composedDirectoryName)
    {
        $essence = explode('/', $this->path)[0] ?? false;
        $dataWithUrls = CacheHelper::getSpasificListOfUrls();

        $addScriptForRedirect = false;

        $landingsUrl = $dataWithUrls->landing->urls ?? [];
        
        if ($essence == 'blog') {
            $composedDirectoryName .= "/blogs";
        } elseif ($essence == 'essays') {
            $composedDirectoryName .= "/essays";
        } elseif (in_array("/{$this->path}", $landingsUrl)) {
            $composedDirectoryName .= "/landings";
            $essence = 'landing';
        } else {
            $composedDirectoryName .= "/generals";
        }

        switch (true) {
            case preg_match("^essays/^", $this->path):

                $pathForEssayOrCategory = preg_replace('^essays/^', '', $this->path);
                $essaysUrl = $dataWithUrls->essay->urls ?? [];

                $hashedEssaysUrl = array_map(function ($url) {
                    return md5($url);
                }, $essaysUrl);
                $flipedArray = array_flip($hashedEssaysUrl);
                $isItEssay = isset($flipedArray[md5($pathForEssayOrCategory)]);

                $path = $isItEssay ? 'essay' : 'essay-category';
                $addScriptForRedirect = !$isItEssay;
                break;

            case preg_match("^blog/^", $this->path):
                $path = 'blog';
                break;

            case preg_match("^blog^", $this->path):
                $path = 'blogs';
                break;

            case preg_match("/\//", $this->path):
                $path = preg_replace("/\//", '-', $this->path);
                break;
                
            case $essence == 'landing':
                $path = 'landing';
                break;

            default:
                $path = $this->path;
                break;
        }

        return [
            'directoryName' => $composedDirectoryName,
            'path' => $path,
            'addScriptForRedirect' => $addScriptForRedirect
        ];
    }
}
