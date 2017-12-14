<?php
use Symfony\Component\HttpFoundation\File\UploadedFile;
use \Illuminate\Http\Request;
use Wizz\ApiClientHelpers\Helpers\ArrayHelper;
// TODO move to static class to test
function apiRequestProxy(Request $request)
{
    // maybe we should use https://github.com/php-curl-class/php-curl-class/blob/master/src/Curl/Curl.php
    $path = $request->path();
    $path = strpos($path, '/') === 0 ? $path : '/'.$path;
    $requestString = str_replace(conf('url'), '', $path);
    $method = $_SERVER['REQUEST_METHOD'];
    // $method = $request->method();
    $data = $request->all();
    $cookie_string = getCookieStringFromArray($request->cookie());
    $data['ip'] = array_get($_SERVER, 'HTTP_CF_CONNECTING_IP', $request->ip());
    $data['app_id'] = conf('client_id');
    $addition = (session('addition')) ? session('addition') : [];
    $data = array_merge($data, $addition);

    $query = conf('secret_url').$requestString;
    $query .= ($method == "GET") ? '?'.http_build_query($data) : '';
    // TODO is it nessesary here?
    // session_write_close();
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $query);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept-Language: '.$request->header('Accept-Language')]);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_COOKIE, $cookie_string);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

    if (in_array($method, ["PUT", "POST", "DELETE"]))
    {

        if (array_get($data, 'files'))
        {
            $data['files'] = prepare_files_for_curl($data);
        }

        $data = ($method == "POST") ? ArrayHelper::array_sign($data) : http_build_query($data);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }
    $res = curl_exec($ch);
    curl_close($ch);
    return $res;
}

// TODO seems to be useless
function getFilenameFromHeader($contentDisposition)
{
    if (!$contentDisposition) {
        return false;
    }

    preg_match('/filename="(.*)"/', $contentDisposition, $filename);
    $filename = clear_string_from_shit($filename[1]);
    return $filename;
}
// TODO seems to be useless
function getPathFromHeaderOrRoute($contentDisposition, $slug)
{
    if ($contentDisposition) {
        preg_match('/filename="(.*)"/', $contentDisposition, $filename);
        $filename = clear_string_from_shit($filename[1]);
        $path = public_path().'/files/'.$filename;
        return $path;
    }
    $chunks = explode('/', $slug);
    $filename = array_get($chunks, count($chunks) - 1);
    if ($filename){
        $filename = clear_string_from_shit($filename);
        $path = public_path().'/documents/'.$filename;
        return response()->download($path);
    }

}

function prepare_files_for_curl(array $data, $file_field = 'files')
{
    $files = array_pull($data, $file_field);
    $files = ArrayHelper::array_sign($files);
    foreach ($files as $key => $file){
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
