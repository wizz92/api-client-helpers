<?php

namespace Wizz\ApiClientHelpers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Cache;
use Wizz\ApiClientHelpers\Helpers\CurlRequest;
use Wizz\ApiClientHelpers\Helpers\CacheHelper;
use Wizz\ApiClientHelpers\Helpers\CookieHelper;
use Wizz\ApiClientHelpers\Helpers\Validator;

class ACHController extends Controller
{
    /*

    Setting up our error message for the client.

    */
    public function __construct()
    {
        $one = "Sorry, looks like something went wrong. ";

        $two = (CacheHelper::conf('support_email')) ? "Please contact us at <a href='mailto:".CacheHelper::conf('support_email')."'>".CacheHelper::conf('support_email')."</a>" : "Please contact us via email";

        $three = ' for further assistance.';

        $this->error_message = $one.$two.$three;
        $this->security_code = CacheHelper::conf('security_code');
        $this->redirect_code = config('api_configs.not_found_redirect_code', 301);
        $this->redirect_mode = config('api_configs.not_found_redirect_mode');

        $this->version = "1.2";
    }

    // public function splitUrlIntoSegments($url)
    // {
    //     $url_without_query_string = explode('?', $url)[0];
    //     return array_values(array_filter(explode('/', $url_without_query_string), function ($var) {
    //         return ($var) ? true : false;
    //     }));
    // }

    /*

    The actual function for handling frontend repo requests.

    */
    public function frontendRepo(Request $req)
    {
        $slug = $req->path();
        if (!Validator::validateFrontendConfig()) {
            return $this->error_message;
        }
        $ck = CacheHelper::CK($slug);
        if (CacheHelper::shouldWeCache($ck)) {
            return CookieHelper::insertToken(Cache::get($ck));
        }

        try {
            $front = CacheHelper::conf('frontend_repo_url');
            $query = [];
            // $domain = $req->url();
            // cut shit from here
            if (substr($slug, 0, 1) === '/') {
                $slug = substr($slug, 1);
            }
            // if we using landings.repo for projects
            // we need to add pname identifier to request
            $project_alias = CacheHelper::conf('project_alias');
            if ($project_alias) {
                $query['pname'] = $project_alias;
            }

            $url = $front.$slug. '?' . http_build_query(array_merge($req->all(), $query));
            $page = file_get_contents($url, false, stream_context_create(CookieHelper::arrContextOptions()));

            $http_code = array_get($http_response_header, 0, 'HTTP/1.1 200 OK');

            if (strpos($http_code, '302') > -1 || strpos($http_code, '301') > -1) {
                $location = array_get($http_response_header, 3, '/');
                $location = str_replace("Location: ", "", $location);
                return redirect()->to($location);
            }

            // what is this?
            if (strpos($http_code, '238') > -1) {
                // code 238 is used for our internal communication between frontend repo and client site,
                // so that we do not ignore errors (410 is an error);
                if ($this->redirect_mode === "view") {
                    return response(view('api-client-helpers::not_found'), $this->redirect_code);
                } else //if($this->redirect_mode === "http")
                { // changed this to else, so that we use http redirect by default even if nothing is specified
                    return redirect()->to('/', $this->redirect_code);
                }
            }

            if (CacheHelper::shouldWeCache()) {
                Cache::put($ck, $page, CacheHelper::conf('cache_frontend_for'));
            }

            return CookieHelper::insertToken($page);
        } catch (Exception $e) {
            // \Log::info($e);
            return $this->error_message;
        }
    }

    /*

    Function to clear all cache (e.g. when new frontend repo code is pushed).

    */
    public function clearCache()
    {
        if (request()->input('code') !== $this->security_code) {
            return ['result' => 'no access'];
        }
        try {
            \Artisan::call('cache:clear');
            return ['result' => 'success'];
        } catch (Exception $e) {
            // \Log::info($e);
            return ['result' => 'error'];
        }
    }
    /*

    Prozy function for api by @wizz.

    */
    public function proxy(Request $request)
    {

        $r = new CurlRequest($request);
        $r->execute();
        CookieHelper::setCookiesFromCurlResponse($r->headers['cookies']);

        if ($r->redirect_status) {
            return redirect()->to(array_get($r->headers, 'location'));
        }

        if (strpos('q'.$r->content_type, 'text/html') && strpos('q'.$r->body, 'Whoops,')) {
            return response()->json([
            'status' => 400,
            'errors' => [$this->error_message],
            'alerts' => []
            ]);
        }
        if (strpos('q'.$r->content_type, 'text/html') || strpos('q'.$r->content_type, 'text/plain')) {
            return $r->body;
        }
        if ($r->content_type == 'application/json') {
            return response()->json(json_decode($r->body));
        }
        if (strpos('q'.$r->content_type, 'xml')) {
            return (new \SimpleXMLElement($r->body))->asXML();
        }

        return response($r->body)
            ->header('Content-Type', $r->content_type)
            ->header('Content-Disposition', array_get($r->headers, 'content-disposition'));
    }

    /*

    Our redirector to api functionality.

    */
    public function redirect($slug, Request $request)
    {
// TODO needs fix to work in multi client mode
        if (!Validator::validate_redirect_config()) {
            return $this->error_message;
        }

        return redirect()->to(CacheHelper::conf('secret_url').'/'.$slug.'?'.http_build_query($request->all()));
    }

    /*

    Little helper to see the state of affairs in browser.

    */
    public function check()
    {
        if (request()->input('code') !== $this->security_code) {
            return;
        }

        return [
            'frontend_repo' => Validator::isOk('validateFrontendConfig'),
            'redirect' => Validator::isOk('validateRedirectConfig'),
            'caching' => Validator::isOk('shouldWeCache'),
            'version' => $this->version
        ];
    }
}
