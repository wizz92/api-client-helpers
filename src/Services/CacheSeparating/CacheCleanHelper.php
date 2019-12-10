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
        $cacheKeys = [
            'essay' => "essays_{$appId}",
            'blog' => "blog_{$appId}"
        ];

        $this->clearCacheByKey($appId, $type, null, array_get($cacheKeys, $type)); 
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
            'blog' => "blog/$url",
            'general' => "general/$url",
            'landing' => "$url",
            'essay' => "essays/$url",
            'flashcard' => "flashcards/$url",
        ];

        $path = $pathForCache[$type];
                
        if (!$path && !$customKey) {
            // return ['error' => "we don't have this type"];
            $this->errrors[$type] = "we don't have {$type} type";
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
    public function clearComposingFiles(string $composedDirectoryName)
    {
        if (Storage::disk('public_assets')->exists("/composed/{$composedDirectoryName}")) {
            Storage::disk('public_assets')->deleteDirectory("/composed/{$composedDirectoryName}");
        }
    }
}
