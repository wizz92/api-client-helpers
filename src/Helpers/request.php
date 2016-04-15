<?php 
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile;

function apiRequestProxy(Request $request)
{
    $method = $_SERVER['REQUEST_METHOD'];
    // $method = $request->method();
    // $root = $request->root();
    $root = $_SERVER['HTTP_HOST'];
    // dd($root);
    // dd($_SERVER);

    $requestString = $_SERVER['PATH_INFO'];
    // $requestString = $request->fullUrl();
    // dd($requestString);
    $requestString = str_replace(config('api_configs.url'), '', $requestString);
    $query = config('api_configs.secret_url').$requestString;
    dd($_GET);
    if ($_GET) {
        # code...
    }
    // dd($query);
    session_write_close();
    $ch = curl_init(); 
    curl_setopt($ch, CURLOPT_URL, $query); 
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_HEADER, true); 
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method); 
    curl_setopt($ch, CURLOPT_COOKIE, $_SERVER['HTTP_COOKIE']);
    // curl_setopt($ch, CURLOPT_COOKIE, getCookieStringFromRequest($request));
    if (in_array($method, ["PUT", "POST", "DELETE"])) 
    {
        $data = $request->all();
        if (array_get($data, 'files')) 
        {
            $files = array_pull($data, 'files');
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
            $data['files'] = $files;
        }
        // TODO add some 
        $data['ip'] = $_SERVER['REMOTE_ADDR'];
        // $data['ip'] = $_SERVER['REMOTE_ADDR'];
        $data = ($method == "POST") ? array_sign($data) : http_build_query($data);
        // dd($data);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }
    $res = curl_exec($ch);
    curl_close($ch);
    return $res;
}