<?php 


return [


	'view_routes' => [
		'actions/print',
		'admin/stats/giving',
	],
	'file_routes' => [
		'download',
	],
	'client_id' 		=> env('client_id', 1),
    'client_secret' 	=> env('client_secret', 'abc'),
    'url'   			=> env('url', 'http://localhost:8001'),
    'secret_url'   		=> env('secret_url', 'http://localhost:8001'),
    'grant_type' 		=> env('grant_type','client_credentials'),
];