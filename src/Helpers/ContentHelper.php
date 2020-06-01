<?php

namespace Wizz\ApiClientHelpers\Helpers;

use Wizz\ApiClientHelpers\Helpers\CacheHelper;
use Spatie\Url\Url as UrlParser;

class ContentHelper {

  public static function getFrontendContent($slug = '', string $experiment_results = "") {
    if (!$slug) {
      throw new \Exception("Missing argument 'slug'");
    }
    $base_url = CacheHelper::conf('frontend_repo_url');
    // remove first slash of slug if present
    // because base_url already finished with slash
    if (starts_with($slug, '/')) {
      $slug = str_replace_first('/', '', $slug);
    }

    $query = request()->query();
    if (CacheHelper::conf('pname_query')) {
      $query['pname'] = CacheHelper::conf('alias_domain') ?? CacheHelper::getDomain();
    }

    $query_string = http_build_query($query);
    $url = "{$base_url}{$slug}?{$query_string}";

    $host = request()->header('host');
    $referrer = request()->secure() ? "https://{$host}" : "http://{$host}";
    $headers = [
      'User-Agent: ' . request()->header('user-agent'),
      'Referrer: ' . $referrer
    ];

    if ($experiment_results) {
      $headers[] = 'X-Experiments-Info: ' . $experiment_results;
    }

    $stream_options = [
      'http' => [
        'follow_location' => 0,
        'max_redirects' => 0,
        'ignore_errors' => true,
        'header' => $headers
      ],
      'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false
      ]
    ];
    $response_body = file_get_contents($url, false, stream_context_create($stream_options));

    $exeptionPages = [
      'OneSignalSDKWorker.js',
    ];
      $projectsForTest = [

      ];

    if (!in_array(request()->path(), $exeptionPages))
    {
        if (in_array($query['pname'], $projectsForTest)) {
            $response_body = AutocomposeHelper::parseBody($response_body, $query['pname']);
        }
    }
    $response_headers = self::parseHeaders($http_response_header);
    $response_status_code = $response_headers['StatusCode'];

    return [
      'status' => $response_status_code,
      'headers' => $response_headers,
      'body' => $response_body
    ];
  }

  public static function getValidResponse($response = null) {
    if (!$response) {
      throw new \Exception("Missing argument 'response'");
    }

    if (in_array($response['status'], [301, 302])) {
      $response_location = array_get($response['headers'], 'Location', '');
      if (!$response_location) {
        throw new \Exception("Missing header 'Location' on status {$response['status']}");
      }
      $response_location = UrlParser::fromString($response_location);
      $response_query = $response_location->getQuery();
      $response_path = $response_location->getPath();
      $redirect_location = $response_query ? "{$response_path}?{$response_query}" : $response_path;

      if ($response_location->getHost() !== request()->header('host')) {
        $redirect_location = $response_location;
      }

      return redirect($redirect_location, $response['status']);
    }
    $response_headers = [
      'Content-Type' => array_get($response['headers'], 'Content-Type', 'text/html'),
      'Cache-Control' => array_get($response['headers'], 'Cache-Control', 'no-cache private'),
    ];
    
    return response($response['body'], $response['status'])
      ->withHeaders($response_headers);
  }

  public static function parseHeaders( $headers ) {
      $head = array();
      foreach( $headers as $k=>$v )
      {
          $t = explode( ':', $v, 2 );
          if( isset( $t[1] ) )
              $head[ trim($t[0]) ] = trim( $t[1] );
          else
          {
              $head[] = $v;
              if( preg_match( "#HTTP/[0-9\.]+\s+([0-9]+)#",$v, $out ) )
                  $head['StatusCode'] = intval($out[1]);
          }
      }
      return $head;
  }
}