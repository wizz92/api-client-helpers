<?php 
use \Wizz\ApiClientHelpers\Token;
use \Illuminate\Http\Request;
Route::get('/r/{slug?}', function($slug)
{ 
	$u = config('api_configs.secret_url').'/'.$slug;

	return redirect()->to($u);

})->where('slug', '.+');

Route::any('api/{slug?}', function(Request $request, $slug = '/') 
{
	$method = array_get($_SERVER, 'REQUEST_METHOD');
	$res = apiRequestProxy($request);
	$data = explode("\r\n\r\n", $res);
	$headers = (count($data) == 3) ? $data[1] : $data[0];
	$res = (count($data) == 3) ? $data[2] : $data[1];
    $cookies = setCookiesFromCurlResponse($headers);

	if (contains_string($slug, config('api_configs.file_routes'))) 
	{
		$headers = http_parse_headers($headers);
		$filename = array_get($headers[0], 'content-disposition');
        if ($filename) 
        {
            
            preg_match('/filename="(.*)"/', $filename, $filename);

            $filename = clear_string_from_shit($filename[1]);

            file_put_contents(public_path().'/files/'.$filename, $res);
			
			return response()->download(public_path().'/files/'.$filename);
        
        }
        
	} elseif (contains_string($slug, ['documents'])) 
	{
		$chunks = explode('/', $slug);
		$filename = array_get($chunks, count($chunks) - 1);
        if ($filename) 
        {
            $filename = clear_string_from_shit($filename);
            file_put_contents(public_path().'/documents/'.$filename, $res);
			return response()->download(public_path().'/documents/'.$filename);
        }
	} elseif (contains_string($slug, config('api_configs.view_routes'))) 
	{
		return $res;

	} elseif (strpos('q'.$res, 'Whoops,')) {
		if (! json_decode($res)) {
			return response()->json([
				'status' => 400,
				'errors' => ['Whoops, something went wrong. Please contact our support to receive discount and further assistance'],
				'alerts' => []
			]);
		}
	}
	// dd($res);
	return response()->json(json_decode($res));

})->where('slug', '.*');


