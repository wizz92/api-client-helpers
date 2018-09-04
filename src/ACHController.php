<?php

namespace Wizz\ApiClientHelpers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Wizz\ApiClientHelpers\Helpers\CurlRequest;
use Wizz\ApiClientHelpers\Helpers\CacheHelper;
use Wizz\ApiClientHelpers\Helpers\CookieHelper;
use Wizz\ApiClientHelpers\Helpers\Validator;
use Cookie;
use Cache;
use Httpauth;

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

    private function ensureBasicAuth()
    {
        if (CacheHelper::conf('http_auth')) {
            Httpauth::make(['username' => '1', 'password' => '1'])->secure();
        }
    }

    public function frontendRepo(Request $req)
    {
        $this->ensureBasicAuth();

        $slug = $req->path();
        if (!Validator::validateFrontendConfig()) {
            return $this->error_message;
        }

        $this->trackingHits();

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

            if (CacheHelper::conf('pname_query')) {
                $query['pname'] = CacheHelper::conf('alias_domain') ?? CacheHelper::getDomain();
            }

            $url = $front.$slug. '?' . http_build_query(array_merge($req->all(), $query));
            $page = file_get_contents($url, false, stream_context_create(CookieHelper::arrContextOptions()));

            $http_code = array_get($http_response_header, 0, 'HTTP/1.1 200 OK');

            if (strpos($http_code, '302') > -1 || strpos($http_code, '301') > -1) {
                $location = array_get($http_response_header, 7, '/');

                $location = str_replace("Location: ", "", $location);
                if (strpos($location, '?authType')) {
                    $location = "/?" . explode("?", $location)[1];
                }

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

            if (strpos($http_code, '404') > -1) {
                return response(CookieHelper::insertToken($page), 404);
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
        $assets_proxy_on = CacheHelper::conf('assets_proxy') ?? false;
        $assets_url_match = strpos($request->path(), 'assets') !== false;

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
        // we need to check exactly '/xml' here because .xlsx .docx file has
        // content type like this application/vnd.openxmlformats-officedocument.spreadsheetml.sheet
        // 'xml' substring is here, but it is not XML :)
        if (strpos('q'.$r->content_type, '/xml')) {
            return (new \SimpleXMLElement($r->body))->asXML();
        }

        $response = response($r->body)
              ->header('Content-Type', $r->content_type)
              ->header('Content-Disposition', array_get($r->headers, 'content-disposition'));

        if ($assets_proxy_on && $assets_url_match) {
          $response = $response->header('Cache-Control', "max-age=7776000");
        }

        return $response;
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

    // hit is valid if $_SERVER has HTTP_REFERER, and it`s not current domain
    // and if $_SERVER hasn`t HTTP_REFERER
    private function validateHitTracking()
    {
        logger('Let`s check if everything is okey \n');

        $http_referer = array_get($_SERVER, 'HTTP_REFERER', null);
        logger('Getting referer from server');
        logger($http_referer);
        // if user just write site in the browser by hands than hit is valid
        if (!$http_referer) {
            return true;
        }

        // get only domain name from referer
        $http_referer = parse_url($http_referer, PHP_URL_HOST);
        $http_host = array_get($_SERVER, 'HTTP_HOST', null);

        logger('Referer after formating');
        logger($http_referer);
        logger('Host from server');
        logger($http_host);

        // if no host in server array we can`t determine hit valid state
        if (!$http_host) {
            return false;
        }

        // if the user came from another site than hit is valid
        if ($http_referer != $http_host) {
            return true;
        }

        return false;
    }

    // save new hit
    public function trackingHits()
    {
        if (!CacheHelper::conf('tracking_hits')) {
            return null;
        }

        $can_track_hit = $this->validateHitTracking();

        if (!$can_track_hit) {
            return null;
        }

        $data = [
            'rt' => request()->get('rt'),
            'app_id' => CacheHelper::conf('client_id')
        ];

        $url = CacheHelper::conf('secret_url') . '/hits';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $response = curl_exec($ch);
        curl_close($ch);

        $hit_id = json_decode($response)->data->id ?? 0;

        logger('Hit id from response');
        logger($hit_id);

        return setcookie('hit_id', $hit_id, 0, '/');
    }
}
