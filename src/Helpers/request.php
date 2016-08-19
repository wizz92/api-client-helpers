<?php 
use Symfony\Component\HttpFoundation\File\UploadedFile;
use \Illuminate\Http\Request;

function apiRequestProxy(Request $request)
{
    $requestString = array_get($_SERVER, 'REQUEST_URI');
    $method = array_get($_SERVER, 'REQUEST_METHOD');
    $root = array_get($_SERVER, 'HTTP_HOST');
    $data = $request->all();
    // $cookie_string = array_get($_SERVER, 'HTTP_COOKIE');
    $cookie_string = getCookieStringFromRequest($request);
    // TODO: add advanced IP getter
    $data['ip'] = $request->ip();
    $data['app_id'] = config('api_configs.client_id');
    $data['access_token'] = session('access_token');
    $addition = (session('addition')) ? session('addition') : [];
    $data = array_merge($data, $addition);

    $requestString = str_replace(config('api_configs.url'), '', $requestString);
    $query = config('api_configs.secret_url').$requestString;
    $query .= ($method == "GET") ? '?'.http_build_query($data) : '';
    session_write_close();
    $ch = curl_init(); 
    curl_setopt($ch, CURLOPT_URL, $query); 
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_HEADER, true); 
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method); 
    curl_setopt($ch, CURLOPT_COOKIE, $cookie_string);

    if (in_array($method, ["PUT", "POST", "DELETE"])) 
    {
        
        if (array_get($data, 'files')) 
        {
            $data['files'] = prepare_files_for_curl($data);
        }

        $data = ($method == "POST") ? array_sign($data) : http_build_query($data);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }
    $res = curl_exec($ch);
    curl_close($ch);
    return $res;
}

function prepare_files_for_curl(array $data, $file_field = 'files')
{
    $files = array_pull($data, $file_field);
    $files = array_sign($files);
    foreach ($files as $key => $file) 
    {
        if (is_object($file) && $file instanceof UploadedFile) 
        {
            $tmp_name = $file->getRealPath();
            $name = $file->getClientOriginalName();
            $type = $file->getMimeType();
            $files[$key] = new CURLFile($tmp_name, $type, $name);
        } 
    }
    return $files;
}