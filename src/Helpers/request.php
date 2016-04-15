<?php 
use Symfony\Component\HttpFoundation\File\UploadedFile;

function apiRequestProxy()
{
    $requestString = $_SERVER['PATH_INFO'];
    $method = $_SERVER['REQUEST_METHOD'];
    $root = $_SERVER['HTTP_HOST'];
    $data = ($method == "GET") ? $_GET : $_POST;

    // TODO: add advanced IP getter
    $data['ip'] = $_SERVER['REMOTE_ADDR'];
    $data['app_id'] = config('api_configs.client_id');
    $requestString = str_replace(config('api_configs.url'), '', $requestString);
    $query = config('api_configs.secret_url').$requestString;
    $query .= ($method == "GET") ? http_build_query($data) : '';

    session_write_close();
    $ch = curl_init(); 
    curl_setopt($ch, CURLOPT_URL, $query); 
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_HEADER, true); 
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method); 
    curl_setopt($ch, CURLOPT_COOKIE, $_SERVER['HTTP_COOKIE']);

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