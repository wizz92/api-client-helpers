<?php 
use Illuminate\Http\Request;

function apiRequestProxy(Request $request)
{
    $method = $request->method();
    $root = $request->root();
    $requestString = $request->fullUrl();
    $requestString = str_replace($root.config('api_configs.url'), '', $requestString);
    $query = config('api_configs.secret_url').$requestString;
    session_write_close();
    $ch = curl_init(); 
    curl_setopt($ch, CURLOPT_URL, $query); 
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_HEADER, true); 
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method); 
    curl_setopt($ch, CURLOPT_COOKIE, getCookieStringFromRequest($request));
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
        $data = ($method == "POST") ? array_sign($data) : http_build_query($data);
        // if ($method == "POST") 
        // {
            // $data = array_sign($data);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        // } else 
        // {
            // curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        // }
    }
    $res = curl_exec($ch);
    curl_close($ch);
    return $res;
}