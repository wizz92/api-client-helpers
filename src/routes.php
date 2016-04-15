<?php 
use \Wizz\ApiClientHelpers\Token;

Route::get('/r/{slug?}', function($slug)
{ 
	$u = config('api_configs.secret_url').'/'.$slug;

	return redirect()->to($u);

})->where('slug', '.+');

Route::any('api/{slug?}', function($slug) 
{
	$method = $_SERVER['REQUEST_METHOD'];
	$res = apiRequestProxy();

	$data = explode("\r\n\r\n", $res);
	$headers = (count($data) == 3) ? $data[1] : $data[0];
	$res = (count($data) == 3) ? $data[2] : $data[1];
    $cookies = setCookiesFromCurlResponse($headers);

	if (strpos('q'.$slug, 'download')) 
	{
		$headers = http_parse_headers($headers);
		$filename = array_get($headers[0], 'content-disposition');
        if ($filename) 
        {
            preg_match('/filename="(.*)"/', $filename, $filename);
            $filename = $filename[1];
            file_put_contents(public_path().'/files/'.$filename, $res);
			return response()->download(public_path().'/files/'.$filename);
        }
	} elseif (strpos('q'.$slug, 'documents')) 
	{
		$chunks = explode('/', $slug);
		$filename = array_get($chunks, count($chunks) - 1);
        if ($filename) 
        {
            file_put_contents(public_path().'/documents/'.$filename, $res);
			return response()->download(public_path().'/documents/'.$filename);
        }
	} elseif (strpos('q'.$slug, 'actions/print')) 
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
	return response()->json(json_decode($res));

})->where('slug', '.*');


Route::get('{slug?}', function($slug, Token $token) 
{
	
	if(!$token->init())
	{
		return $token->errors;
	}
	return view('index')
		->with('access_token', $token->getToken())
		->with('bootstrap', $token->getBootstrapData())
		;

})->where('slug', '.+');

