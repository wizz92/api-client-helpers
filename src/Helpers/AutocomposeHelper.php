<?php

namespace Wizz\ApiClientHelpers\Helpers;

use Wizz\ApiClientHelpers\Helpers\CacheHelper;
use Wizz\ApiClientHelpers\Services\AutoComposing\Contracts\BuilderInterface;
use Closure;
use Cache;

class AutocomposeHelper
{
    const CACHE_EXPIRE = 60 * 24 * 2;

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public static function parseBody($pageContent, $queryName)
    {
        $builder = app()->make(BuilderInterface::class);
        $path = request()->path() === '/' ? 'index' : request()->path();
        $fullPathArray = explode('/', $path);
        $key = ($fullPathArray[0] === 'essays' && isset($fullPathArray[1])) ? 'nested-essays' : $path;
        $shouldComposeStyles = config('compose_configs.composeConditions.styles');
        $shouldComposeScripts = config('compose_configs.composeConditions.scripts');
        if ($shouldComposeStyles) {
            $style =  CacheHelper::cacher('parse_body_style' . $queryName . '_' . $key, function () use ($builder, $pageContent, $path) {
                return $builder->make('collectStyles', $pageContent, true)->get();
            }, self::CACHE_EXPIRE);
        }
        if ($shouldComposeScripts) {
            $scripts =  CacheHelper::cacher('parse_body_scripts' . $queryName . '_' . $key, function () use ($builder, $pageContent, $path) {
                return $builder->make('collectScripts', $pageContent)->add('path', $path)->get();
            }, self::CACHE_EXPIRE);
        }

        $processedPageContent = $builder->make('collectDOM', $pageContent);
        if ($shouldComposeStyles) {
            $processedPageContent = $processedPageContent->add('styles', $style);
        }
        if ($shouldComposeScripts) {
            $processedPageContent = $processedPageContent->add('scripts', $scripts);
        }
        $processedPageContent = $processedPageContent->get()['html'];
        return '<!DOCTYPE html> <html lang="en">' . $processedPageContent . '</html>';
    }
}
