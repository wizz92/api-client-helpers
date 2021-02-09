<?php
namespace Wizz\ApiClientHelpers\Helpers;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use \Illuminate\Http\Request;
use Wizz\ApiClientHelpers\Helpers\ArrayHelper;
use Wizz\ApiClientHelpers\Helpers\CacheHelper;

class Validator
{
    /*

    Little helper for our check function.

    */
    public static function isOk($func)
    {
        return ($func()) ? 'OK' : 'OFF';
    }

    /*

    Validating that all our configs necessary for frontend repo are in place.

    */
    public static function validateFrontendConfig()
    {
        if (! CacheHelper::conf('frontend_repo_url')) {
            return false;
        }

        if (substr(CacheHelper::conf('frontend_repo_url'), -1) != '/') {
            return false;
        }

        return true;
    }

       

    /*

    Validating that all our configs necessary for redirect are in place.

    */
    public static function validateRedirectConfig()
    {
        if (! CacheHelper::conf('secret_url', false)) {
            return false;
        }

        return true;
    }
}
