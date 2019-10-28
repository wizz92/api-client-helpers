<?php

namespace Wizz\ApiClientHelpers\Services\AutoComposing;

use Wizz\ApiClientHelpers\Services\AutoComposing\Contracts\ComposingInterface;
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
        if (!Storage::disk('public_assets')->exists($composedDirectoryName)) {
             Storage::disk('public_assets')->makeDirectory($composedDirectoryName);
        }

        $path = preg_match("/\//", $this->path) ? preg_replace("/\//", '-', $this->path) : $this->path;

        $bodyJSFileName = "assets/{$composedDirectoryName}/body-{$path}.js";

        $jsFile = fopen($bodyJSFileName, 'a+');
        if (flock($jsFile, LOCK_EX | LOCK_NB)) { 
            ftruncate($jsFile, 0);
            
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
}
