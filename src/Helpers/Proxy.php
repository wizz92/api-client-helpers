<?php
namespace Wizz\ApiClientHelpers\Helpers;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use \Illuminate\Http\Request;
use Wizz\ApiClientHelpers\Helpers\ArrayHelper;
use Wizz\ApiClientHelpers\Helpers\CookieHelper;
use Wizz\ApiClientHelpers\Helpers\CurlRequest;

class Proxy 
{
    protected $client;

    protected $request;

    public function __construct(CurlRequest $client, Request $request){
        $this->client = $client;
        $this->request = $request;
    }

    public function formRequestParams(){
        // maybe we should use https://github.com/php-curl-class/php-curl-class/blob/master/src/Curl/Curl.php
        $path = $this->request->path();
        // TODO do we need it here?
        
        $path = strpos($path, '/') === 0 ? $path : '/'.$path;
        $requestString = str_replace(conf('url'), '', $path);
        $method = $_SERVER['REQUEST_METHOD'];
        $data = $this->request->all();
        $data['ip'] = array_get($_SERVER, 'HTTP_CF_CONNECTING_IP', $this->request->ip());
        $data['app_id'] = conf('client_id');
        $addition = session('addition') ? session('addition') : [];
        $data = array_merge($data, $addition);

        $query = conf('secret_url').$requestString;
        $query .= ($method == "GET") ? '?'.http_build_query($data) : '';
        $cookie_string = CookieHelper::getCookieStringFromArray($this->request->cookie());

        return $this->client->config()->exec();
    }
   

}



