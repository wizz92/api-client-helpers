<?php 
use Illuminate\Http\Request;
use use \Wizz\ApiClientHelpers\Token;

Route::get('/r/{slug?}', function(Request $request, $slug)
{ 
	$u = config('api_configs.secret_url').'/'.$slug;

	return redirect()->to($u);

})->where('slug', '.+');

Route::any('api/{slug?}', function($slug, Request $request) 
{
	$method = $request->method();
	$res = apiRequestProxy($request);
	
	$data = explode("\r\n\r\n", $res);
	$headers = (count($data) == 3) ? $data[1] : $data[0];
	$res = (count($data) == 3) ? $data[2] : $data[1];
    $cookies = setCookiesFromCurlResponse($headers);

	if (strpos('qwe'.$slug, 'download')) 
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
	}
	if (strpos('qwe'.$slug, 'documents')) 
	{
		$chunks = explode('/', $slug);
		$filename = array_get($chunks, count($chunks) - 1);
        if ($filename) 
        {
            file_put_contents(public_path().'/documents/'.$filename, $res);
			return response()->download(public_path().'/documents/'.$filename);
        }
	}
	if (strpos('qwe'.$slug, 'actions/print') || strpos('qwe'.$slug, 'superstats')) 
	{
		return $res;
	}
	if (strpos('qwe'.$res, 'Whoops,')) {
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


Route::get('{slug?}', function($slug, Token $token, Request $request) 
{
	
	if(!$token->init($request))
	{
		return $token->errors;
	}
	return view('index')
		->with('access_token', $token->getToken())
		->with('bootstrap', $token->getBootstrapData())
		;

})->where('slug', '.+');

