<?php
namespace Wizz\ApiClientHelpers\Services\CacheSeparating;

use Illuminate\Support\Facades\Storage;

/**
 * Class CacheCleanHelper
 * @package Wizz\ApiClientHelpers\Service\CacheSeparating
 */
class CacheCleanHelper
{
    /**
    * removing composing files
    *
    * @param  string $composedDirectoryName
    *
    * @return void
    */
    public function clearComposingFiles(string $folderPath)
    {
        $paths = Storage::disk('public_assets')->allDirectories();
        $pathsForDeletion = array_map(function ($path) use ($folderPath) {
            if (preg_match("^{$folderPath}^", $path)) {
                Storage::disk('public_assets')->deleteDirectory($path);
            }
        }, $paths);
    }
}
