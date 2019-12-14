<?php
namespace Wizz\ApiClientHelpers\Services\CacheSeparating;

use Illuminate\Support\Facades\Storage;
use Cache;

/**
 * Class CacheCleanHelper
 * @package Wizz\ApiClientHelpers\Service\CacheSeparating
 */
class CacheCleanHelper
{
    public $errors = [];
    /**
     * removing custom page's cache
     *
     * @param  int $appId
     * @param  string $type
     *
     * @return void
     */
    public function clearCacheForCustomPages(int $appId, string $type)
    {
        $type = $type == 'essay' ? $type.'s' : $type;

        $customKeys = [
            $type.'_'.$appId,
            "all_pages_url_by_params_app_id=$appId"
        ];

        foreach ($customKeys as $key) {
            $this->clearCacheByKey($appId, $type, null, $key); 
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
    public function clearCacheByKey(int $appId, string $type, string $url = null, string $customKey = null)
    {
        $pathForCache = [
            'blog' => "/blog/$url",
            'general' => "$url",
            'landing' => "$url",
            'essay' => "/essays/$url",
            'flashcard' => "/flashcards/$url",
        ];

        $path = $pathForCache[$type];
        if (!$path && !$customKey) {
            $this->errrors[$type] = "we don't have {$type} type";
        }

        $cacheKey = $customKey ? md5($customKey) : md5("{$path}_{$appId}");
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
    public function clearComposingFiles(string $composedDirectoryName)
    {
        if (Storage::disk('public_assets')->exists("/composed/{$composedDirectoryName}")) {
            Storage::disk('public_assets')->deleteDirectory("/composed/{$composedDirectoryName}");
        }
    }
}
