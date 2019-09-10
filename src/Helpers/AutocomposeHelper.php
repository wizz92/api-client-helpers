<?php

namespace Wizz\ApiClientHelpers\Helpers;

use Wizz\ApiClientHelpers\Services\AutoComposing\Contracts\BuilderInterface;
use Wizz\ApiClientHelpers\Helpers\CacheHelper;
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
    public static function parseBody($pageContent)
    {
        $builder = app()->make(BuilderInterface::class);
        $path = request()->path() === '/' ? 'index' : request()->path();

        $styles = $builder->make('collectStyles', $pageContent, true)->get();
        $scripts = $builder->make('collectScripts', $pageContent)->add('path', $path)->get();

        $processedPageContent = $builder
            ->make('collectDOM', $pageContent)
            ->add('styles', $styles)
            ->add('scripts', $scripts)
            ->get()['html'];

        return response('<!DOCTYPE html> <html>' . $processedPageContent . '</html>');
    }
}
