<?php

namespace Wizz\ApiClientHelpers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Wizz\ApiClientHelpers\Helpers\CurlRequest;
use Wizz\ApiClientHelpers\Helpers\CacheHelper;
use Wizz\ApiClientHelpers\Helpers\ContentHelper;
use Wizz\ApiClientHelpers\Helpers\CookieHelper;
use Wizz\ApiClientHelpers\Helpers\Validator;
use Spatie\Url\Url as UrlParser;
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

    public function frontendRepo(Request $req, $slug_force = null)
    {
        $this->ensureBasicAuth();

        if (!Validator::validateFrontendConfig()) {
          return $this->error_message;
        }

        $this->trackingHits();

        $slug = $slug_force ? $slug_force : request()->path();
        $current_url = request()->url();
        $parsed_url = UrlParser::fromString($current_url);
        $parsed_url_host = app()->environment('local') ? "{$parsed_url->getHost()}:{$parsed_url->getPort()}" : $parsed_url->getHost();
        $parsed_url_scheme = $parsed_url->getScheme();
        // if req url contains dashboard substring cache this page
        // in one key 'http://domain.name/dashboard'
        // because we have react on dash 
        // it doesn`t matter which page by pass we cache
        $cache_key = request()->is('dashboard*') ? "$parsed_url_scheme://$parsed_url_host/dashboard" : $current_url;
        // get cache_key hash for use in Cache facade
        $cache_key = md5($cache_key);
        
        $experiment_results = request()->get('experimentsResults') ?? null;
        $serialized_experiment_results = "";
        if ($experiment_results) {
          $serialized_experiment_results = serialize($experiment_results);
          $cache_key .= $serialized_experiment_results;
        }

        $cache_expire = CacheHelper::conf('cache_frontend_for') ?? 60 * 24 * 2; // 2 days by default
        $should_skip_cache = !CacheHelper::shouldWeCache();

        if ($should_skip_cache) {
          // get page
          $response = ContentHelper::getFrontendContent($slug, $serialized_experiment_results);
          //and return it
          return ContentHelper::getValidResponse($response);
        }

        $response = Cache::get($cache_key, null);

        if (!is_null($response)) { // meanse we have content in cache
          return ContentHelper::getValidResponse($response);
        }
        // get page 
        $response = ContentHelper::getFrontendContent($slug, $serialized_experiment_results);
        // store in cache in case we do not have an error in response
        if (!in_array($response['status'], [500, 502, 504])) {
          Cache::add($cache_key, $response, $cache_expire);
        }
        // and return it
        return ContentHelper::getValidResponse($response);
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
          $response = response()->json(json_decode($r->body));
          return CacheHelper::attachCORSToResponse($response);
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
        $http_referer = array_get($_SERVER, 'HTTP_REFERER', null);

        // if user just write site in the browser by hands than hit is valid
        if (!$http_referer) {
            return true;
        }

        // get only domain name from referer
        $http_referer = parse_url($http_referer, PHP_URL_HOST);
        $http_host = array_get($_SERVER, 'HTTP_HOST', null);

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

        $this->curl_post_async($url, $data);
        // $ch = curl_init();
        // curl_setopt($ch, CURLOPT_URL, $url);
        // curl_setopt($ch, CURLOPT_POST, 1);
        // curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        // $response = curl_exec($ch);
        // curl_close($ch);

        return null;
        // $hit_id = json_decode($response)->data->id ?? 0;

        // return setcookie('hit_id', $hit_id, 0, '/');
    }

    public function curl_post_async($url, $params)
    {
        $post_string = json_encode($params);

        $cmd = "curl -X POST -H 'Content-Type: application/json'";
        $cmd.= " -d '" . $post_string . "' " . "'" . $url . "'";
        $cmd .= " > /dev/null 2>&1 &";
        exec($cmd, $output, $exit);
        return $exit == 0;
    }

    public function outerAuthForm(Request $request, $order_id, $token)
    {
      $app_id = CacheHelper::conf('client_id');
      $api_url = CacheHelper::conf('secret_url');
      $api_url = ends_with($api_url, '/') ? $api_url : "$api_url/";
      $url = "{$api_url}auth/order_form/{$order_id}/{$token}?app_id={$app_id}";
      $stream_options = [
        'http' => [
          'follow_location' => 0,
          'max_redirects' => 0,
          'ignore_errors' => true,
          'header' => [
            'User-Agent: ' . request()->header('user-agent'),
          ]
        ],
        'ssl' => [
          'verify_peer' => false,
          'verify_peer_name' => false
        ]
      ];
      
      $response_body = file_get_contents($url, false, stream_context_create($stream_options));
      $response_headers = ContentHelper::parseHeaders($http_response_header);
      // dd($response_headers);
      $response_status_code = $response_headers['StatusCode'];

      if ($response_status_code !== 200) {
        return response()->redirectTo('/');
      }

      $response = json_decode($response_body);
      $redirect_url = optional($response->data)->redirect;

      if (!$redirect_url) {
        return response()->redirectTo('/');
      }

      return response("
        <!DOCTYPE html>
        <html>
          <head>
            <meta charset='utf-8' />
            <title>Please Wait!</title>
          </head>
          <body>
            <p>Please wait...</p>
            <script>
              window.location.href = '{$redirect_url}'
            </script>
          </body>
        </html>
      ")->header('Set-Cookie', array_get($response_headers, 'Set-Cookie', "") );
    }
}
