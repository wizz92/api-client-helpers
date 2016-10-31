<?php

namespace Wizz\ApiClientHelpers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Cache;
use File;

class ACHController extends Controller
{
    /*

    Setting up our error message for the client.

    */
    public function __construct()
    {
        $one = "Sorry, looks like something went wrong. "; 

        $two = (env('support_email')) ? "Please contact us at <a href='mailto:".env('support_email')."'>".env('support_email')."</a>" : "Please contact us via email";

        $three = ' for further assistance.';

        $this->error_message = $one.$two.$three;
        $this->security_code = config('api_configs.security_code');
    }

    /*

    Little helper for our check function.

    */
    protected function is_ok($func)
    {
        return ($this->$func()) ? 'OK' : 'OFF';
    }

    /*

    Validating that all our configs necessary for frontend repo are in place.

    */
    protected function validate_frontend_config()
    {
        if(! env('frontend_repo_url')) return false;

        if(substr(env('frontend_repo_url'), -1) != '/') return false;

        return true;
    }

    /*

    Validating that all our configs necessary for redirect are in place.

    */
    protected function validate_redirect_config()
    {
        if(! env('secret_url')) return false;

        return true;
    }

    /*

    Function to see if we should be caching response from frontend repo. 

    If $slug is passed, it will also check whether this $slug is already in cache;

    */
    protected function should_we_cache($slug = false)
    {
        if(env('use_cache_frontend') === false) return false;

        if(request()->input('cache') === 'false') return false;

        if(!app()->environment('production')) return false;

        if($slug && !Cache::has($slug)) return false;

        return true;
    }

    /*

    The actual function for handling frontend repo requests.

    */
    public function frontend_repo($slug)
    {
        $additions = request()->all();
        if ($additions) {
            session(['addition' => $additions]);
        }

        if(!$this->validate_frontend_config()) return $this->error_message;

        if ($this->should_we_cache($slug)) return Cache::get($slug);

        try {

            $url = ($slug == '/') ? env('frontend_repo_url') : env('frontend_repo_url').$slug;
    
            $arrContextOptions = array(
                "ssl" => array(
                    "verify_peer" => false,
                    "verify_peer_name" => false,
                    'follow_location' => 1,
                    'method' => "GET",
                    'header' => 'User-Agent: '.request()->header('user-agent').'\r\n',
                    // 'ignore_errors' => true
                ),
                'http' => array(
                    'method'=>"GET",
                    'follow_location' => 1,
                    'header' => 'User-Agent: '.request()->header('user-agent').'\r\n',
                    // 'ignore_errors' => true
                )
            ); 

            $page = file_get_contents($url, false, stream_context_create($arrContextOptions));

            $http_code = array_get($http_response_header, 0, 'HTTP/1.1 200 OK');

            if(strpos($http_code, '238') > -1) return response(view('api-client-helpers::not_found'), 410); // code 238 is used for our internal communication between frontend repo and client site, so that we do not ignore errors (410 is an error);

            if ($this->should_we_cache()) Cache::put($slug, $page, config('api_configs.cache_frontend_for'));

            return $page;

        }
        catch (Exception $e) 
        {
            \Log::info($e);
            return $this->error_message;
        }
    }

    /*

    Function to clear all cache (e.g. when new frontend repo code is pushed).

    */
    public function clear_cache()
    {
        if(request()->input('code') !== $this->security_code) return;
        try {
            \Artisan::call('cache:clear');
            return 'success';
        } catch (Exception $e) {
            \Log::info($e);
            return "Sorry, looks like something went wrong.";
        }
    }

    protected $file_types = [
        'image/jpeg',
        'image/png',
        'image/tiff',
        'text/csv',
        'audio/basic',
        'audio/L24',
        'audio/mp4',
        'audio/aac',
        'audio/mpeg',
        'audio/ogg',
        'audio/vorbis',
        'audio/x-ms-wma',
        'audio/x-ms-wax',
        'audio/vnd.rn-realaudio',
        'audio/vnd.wave',
        'audio/webm: WebM',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/pdf',
        'application/msword',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',

    ];

    protected $view_types = [
        'text/html;charset=UTF-8',
        'text/html'
    ];
    /*

    Prozy function for api by @wizz.

    */
    public function proxy($slug = '/')
    {

        $method = array_get($_SERVER, 'REQUEST_METHOD');
        $res = apiRequestProxy(request());
        $data = explode("\r\n\r\n", $res);
        if(preg_match('/^HTTP\/\d\.\d\s+(301|302)/',$data[0]))
        {
            $headers = array_get(http_parse_headers($data[0]), 0);
            return redirect()->to(array_get($headers, 'location'));
        }
        $headers = (count($data) == 3) ? $data[1] : $data[0];
        $res = (count($data) == 3) ? $data[2] : $data[1];
        $cookies = setCookiesFromCurlResponse($headers);
        $headers = array_get(http_parse_headers($headers), 0);
        
        $content_type = array_get($headers, 'content-type');
        switch ($content_type) {
            case 'application/json':
                return response()->json(json_decode($res));
                break;
            case in_array($content_type, $this->view_types):
                return $res;
                break;
            case 'text/plain':
                return $res;
                break;
            case in_array($content_type, $this->file_types):
                $path = getPathFromHeaderOrRoute(array_get($headers, 'content-disposition'), $slug);
                file_put_contents($path, $res);
                return response()->download($path);
                break;
            case "application/xml":
                $xml = new \SimpleXMLElement($res);
                return $xml->asXML();
                break;
            case 'text/plain; charset=UTF-8':
                $filename = getFilenameFromHeader(array_get($headers, 'content-disposition'));
                if ($filename == 'robots.txt') File::put(public_path().'/robots.txt',$res);
                break;
            default:
                $path = getPathFromHeaderOrRoute(array_get($headers, 'content-disposition'), $slug);
                file_put_contents($path, $res);
                return response()->download($path);
                break;
        }
        if (strpos('q'.$res, 'Whoops,')) {
            if (! json_decode($res)) {
                
                return response()->json([
                    'status' => 400,
                    'errors' => [$this->error_message],
                    'alerts' => []
                ]);
            }
        }
    }

    /*

    Our redirector to api functionality.

    */
    public function redirect($slug)
    {

        if(!$this->validate_redirect_config()) return $this->error_message; 

        return redirect()->to(env('secret_url').'/'.$slug);
    }

    /*

    Little helper to see the state of affairs in browser.

    */
    public function check()
    {
        if(request()->input('code') !== $this->security_code) return;

        return 'frontend_repo is '.$this->is_ok('validate_frontend_config').'<br/>'
            .'redirect is '.$this->is_ok('validate_redirect_config').'<br/>'
            .'caching is '.$this->is_ok('should_we_cache').'<br/>'
            ;
    }

}
