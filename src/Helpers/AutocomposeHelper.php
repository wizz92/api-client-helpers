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
        $cacheKeySuffix = $queryName . '_' . $key;
        if ($shouldComposeStyles) {
            $styleCacheKey = 'parse_body_style'.$cacheKeySuffix;
            $styles = $builder->make('collectStyles', $pageContent, true)->get($styleCacheKey, 'styles');
        }
        $processedPageContent = $builder->make('collectDOM', $pageContent);
        if ($shouldComposeStyles) {
            $processedPageContent = $processedPageContent->add('styles', $styles);
        }
        $processedPageContent = $processedPageContent->get();
        return '<!DOCTYPE html> <html lang="en">' . $processedPageContent . '</html>';
    }
}
