<?php
namespace Wizz\ApiClientHelpers\Services\CacheSeparating;

use Wizz\ApiClientHelpers\Services\CacheSeparating\Contracts\SeparateManagerInterface;

/**
 * Class SeparateManager
 * @package Wizz\ApiClientHelpers\Service\CacheSeparating
 */
class SeparateManager implements SeparateManagerInterface
{
    /**
     * @param int|null $appId
     * @param string|null $type
     */
    public function clear(int $appId = null, string $type = null)
    {
        if (!$type && !$appId) {
            return ['error' => 'You should choose type of pages or client id'];
        }

        $paramsForUrl = [
            'app_id' => $appId,
            'type' => $type
        ];

        $withoutNull = array_filter($paramsForUrl);
        $dataWithUrls = CacheHelper::getSpasificListOfUrls($withoutNull);

        $paramsInString = implode(" ",$withoutNull);

        if (!isset($dataWithUrls->urls) || !$dataWithUrls) {
            return  ['error' => "We don't have any pages with this params: $paramsInString"];
          }

        switch (true) {
            case !$appId && $type:
                return $this->clearCacheByType($type, $dataWithUrls);
                break;
            
            case $appId && !$type:
                return $this->clearCacheByAppId($appId, $dataWithUrls);
                break;
            
            case !$appId && !$type:
                return $this->clearCacheByAppIdAndType($appId, $type, $dataWithUrls);
                break;
        }
    }
    
    /**
     * removing cache for certain type
     *
     * @param  string $type
     * @param  array $dataWithUrls
     *
     * @return void|array
     */
    protected function clearCacheByType(string $type, array $dataWithUrls)
    {
        foreach ($dataWithUrls as $key => $info) {
            $domain = array_get($info, 'domain', null);
            $appId = array_get($info, 'app_id', null);
            $urls = array_get($info, 'urls', []);

            $this->clearComposingFiles("{$domain}/{$type}s");

            foreach ($urls as $url) {
                $this->clearCacheByKey($appId, $type, $url);
            }
            $this->clearCacheForCustomPages($type);
        }

        return ['result' => "Cache for all {$type}s was deleted"];
    }

    /**
     * removing cache for certain client id
     *
     * @param  mixed $appId
     * @param  mixed $dataWithUrls
     *
     * @return void|array
     */
    protected function clearCacheByAppId(int $appId, array $dataWithUrls)
    {
          $domain = $dataWithUrls->domain;
          unset($dataWithUrls->domain);
  
          foreach ($dataWithUrls as $key => $info) {
            $type = $info->type;
            $urls = $info->urls;
  
            $this->clearComposingFiles("{$domain}");
  
            foreach ($urls as $key => $url) {
                $this->clearCacheByKey($appId, $type, $url);
            }
            $this->clearCacheForCustomPages($type);
        }

        return ['result' => "Cache for for {$domain} project was deleted"];
    }

    /**
     * removing cache for certain client id and type
     *
     * @param  int $appId
     * @param  string $type
     * @param  array $dataWithUrls
     *
     * @return void
     */
    protected function clearCacheByAppIdAndType(int $appId, string $type, array $dataWithUrls)
    {
        $domain = $dataWithUrls->domain;
        $urls = $dataWithUrls->urls;

        $this->clearComposingFiles("{$domain}/{$type}s");
  
        foreach ($urls as $key => $url) {
            $this->clearCacheByKey($appId, $type, $url);
        }
        $this->clearCacheForCustomPages($type);

        return ['result' => "Cache for {$type} type in {$domain} project was deleted"];
    }

    /**
     * removing custom page's cache
     *
     * @param  string $type
     *
     * @return void
     */
    private function clearCacheForCustomPages(string $type)
    {
        switch ($type) {
            case 'essay':
                $this->clearCacheByKey($appId, $type, null, "essays_{$appId}"); 
                break;
          
            case 'blog':
                $this->clearCacheByKey($appId, $type, null, "blog_{$appId}");
                break;
          }
    }
    
    /**
     * removing page's cache files
     *
     * @param  int $appId
     * @param  string $type
     * @param  string|null $url
     * @param  string|null $customKey
     *
     * @return void|array
     */
    private function clearCacheByKey(int $appId, string $type, string $url = null, string $customKey = null)
    {
        $pathForCache = [
            'blog' => "blog/$url",
            'general' => "general/$url",
            'landing' => "$url",
            'essay' => "essays/$url",
            'flashcard' => "flashcards/$url",
        ];

        $path = $pathForCache[$type];
                
        if (!$path && !$customKey) {
            return ['error' => "we don't have this type"];
        }

        $cacheKey = md5($customKey) ?? md5("{$path}_{$appId}");
        if (Cache::has($cacheKey)) {
              Cache::forget($cacheKey);
        }
    }

    /**
     * removing composing files
     *
     * @param  string $composedDirectoryName
     *
     * @return void
     */
    private function clearComposingFiles(string $composedDirectoryName)
    {
        if (Storage::disk('public_assets')->exists("/composed/{$composedDirectoryName}")) {
            Storage::disk('public_assets')->deleteDirectory("/composed/{$composedDirectoryName}");
        }
    }
}
