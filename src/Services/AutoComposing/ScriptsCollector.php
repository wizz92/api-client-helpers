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
        $appId = CacheHelper::conf('client_id');
        $composedDirectoryName = "composed/{$viewRoot}";

        $path = preg_match("/\//", $this->path) ? preg_replace("/\//", '-', $this->path) : $this->path;
        
        // if ($appId == 69) {
            $result = $this->getNewDirNameAndPath($appId, $composedDirectoryName);

            $composedDirectoryName = $result['directoryName'];
            $path = $result['path'];
            $addScriptForRedirect = $result['addScriptForRedirect'];
        // }


        if (!Storage::disk('public_assets')->exists($composedDirectoryName)) {
             Storage::disk('public_assets')->makeDirectory($composedDirectoryName);
        }

        $bodyJSFileName = "assets/{$composedDirectoryName}/body-{$path}.js";

        $jsFile = fopen($bodyJSFileName, 'a+');
        if (flock($jsFile, LOCK_EX | LOCK_NB)) { 
            ftruncate($jsFile, 0);
            $this->customScriptManager->add($jsFile, $addScriptForRedirect);
            
            $this->crawler->filter('body > script.js-scripts-section')->each(function (Crawler $node, $i) use ($jsFile) {
                $script = $node->attr('src');
                foreach ($node as $n) {
                    $targetFileContent = file_get_contents($script);
                    fwrite($jsFile, $targetFileContent."\n");
                    $n->parentNode->removeChild($n);
                }
            });

            fflush($jsFile);        
            flock($jsFile, LOCK_UN);
        } else {
            sleep(7);
        }

        fclose($jsFile);

         $hashedTargetJSFilePath = $this->getHashedFilePath($bodyJSFileName);

         return [
           'body' => $this->getValue($bodyJSFileName, $hashedTargetJSFilePath),
         ];
    }

     /**
      * get hashed file path
      * @param  string $bodyFileName
      * @return string
      */
    private function getHashedFilePath(string $bodyFileName): string
    {
        $hashedTargetFilePath = "";

        if (!app()->environment('local') && file_exists($bodyFileName)) {
            $finalContent = file_get_contents($bodyFileName);
            $finalContentHash = md5($finalContent);
            $path = str_replace('/', '-', $this->path);
            $hashedTargetFilePath = str_replace("{$path}.js", "{$path}.{$finalContentHash}.js", $bodyFileName);
            if (!file_exists($hashedTargetFilePath)) {
                rename($bodyFileName, $hashedTargetFilePath);
            }
        }
         return $hashedTargetFilePath;
    }

     /**
      * get value
      * @param  string $name
      * @param  string $path
      * @return string
      */
    private function getValue(string $name, string $path): string
    {
         $rootUrl = env('root_url', 'https://' . request()->getHttpHost());

         return app()->environment('local') ? "{$rootUrl}/{$name}" : "{$rootUrl}/{$path}";
    }

    
    /**
     * get new castom dir and file names for composing files 
     *
     * @param  int $appId
     * @param  string $composedDirectoryName
     *
     * @return void
     */
    private function getNewDirNameAndPath(int $appId, string $composedDirectoryName)
    {
        $essence = explode('/', $this->path)[0] ?? false;
        $dataWithUrls = CacheHelper::getSpasificListOfUrls(['app_id' => $appId]);

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

                $isItEssay = in_array($pathForEssayOrCategory, $essaysUrl);

                $path = $isItEssay ? 'essay' : $pathForEssayOrCategory;
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
                
            case $essence == 'landing';
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
