<?php
namespace Wizz\ApiClientHelpers\Helpers;

class CookieHelper
{
    public static function insertToken($page)
    {
        return str_replace('<head>', "<head><script>window.csrf='".csrf_token()."'</script>", $page);
    }

    public static function arrContextOptions()
    {
        return array(
                    "ssl" => array(
                        "verify_peer" => false,
                        "verify_peer_name" => false,
                        'follow_location' => 1,
                        'method' => "GET",
                        'header' => 'User-Agent: '.request()->header('user-agent').'\r\n',
                    ),
                    'http' => array(
                        'method'=>"GET",
                        'follow_location' => 1,
                        'header' => [
                            'User-Agent: '.request()->header('user-agent').'\r\n',
                            'Referrer: '.asset('/').'\r\n',
                        ],
                    )
                );
    }
    
    public static function parse($header, $named = true)
    {
        $parts = explode(";", $header);
        $cookie = [];
        foreach ($parts as $i => $part) {
            $cook = explode("=", $part);
            if ($i == 0 && $named) {
                $cookie['name'] = trim($cook[0]);
                $cookie['value'] = $cook[1];
            } else {
                $cookie[trim($cook[0])] = (array_key_exists(1, $cook)) ? $cook[1] : '';
            }
        }
        return $cookie;
    }
    // does it work?
    public static function getCookieStringFromArray(array $cookies): string
    {
        $cookies_string = '';
        foreach ($cookies as $cookieName => $cookieValue) {
            $cookies_string .= $cookieName.'='.$cookieValue.'; ';
        }
         return $cookies_string;
    }
    
    public static function setCookiesFromCurlResponse(array $cookies)
    {
        foreach ($cookies as $cookie) {
            $minutes = new \Carbon\Carbon($cookie['expires']);
            setcookie($cookie['name'], $cookie['value'], $minutes->timestamp, $cookie['path']);
        }
    }
}
