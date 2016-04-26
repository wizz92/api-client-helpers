<?php 
namespace Wizz\ApiClientHelpers;
use \Illuminate\Http\Request;

class Token
{
    
    protected $data = '';
    public $errors;
    public $cookies;
    public $request;

    // public function __construct(Request $request)
    // {
    //     $this->request = $request;
    //     dd($_SERVER);
    //     dd($request->url());
    // }

    protected function getFromBootstrap($query)
    {   
            $addition = array_get($_SERVER, 'QUERY_STRING', '');
            $query .= '&'.$addition;
            $ch = curl_init(); 
            curl_setopt($ch, CURLOPT_URL, $query); 
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $o = curl_exec($ch); 
            curl_close($ch);
            $output = json_decode($o);
            // dd($out)
            if(!is_object($output))
            {
                $this->errors = $o;
                return 'false';
            }
            if(property_exists($output, 'errors') && count($output->errors) > 0)
            {
                $this->errors = $output->errors;
                return false;
            }
            $this->data = $output->data;
            return true;
    }
    
    public function getBootstrapData()
    {
        if (is_object($this->data)) 
        {
            return $this->data->bootstrap;
        }
        
        return [];
    }

    public function getToken()
    {
        if (is_object($this->data)) 
        {
            return $this->data->access_token;
        }

        return '';
    }

    public function init()
    {
        return $this->getFromBootstrap($this->prepareQuery());
    }

    private function prepareQuery()
    {
        $form_params = [
            'grant_type' => config('api_configs.grant_type'),
            'client_id' => config('api_configs.client_id'),
            'client_secret' => config('api_configs.client_secret'),
        ];
        return config('api_configs.secret_url').'/oauth/access_token'.'?'.http_build_query($form_params);
    }
}
