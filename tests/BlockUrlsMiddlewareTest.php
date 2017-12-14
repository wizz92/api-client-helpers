<?php
use Wizz\ApiClientHelpers\Middleware\BlockUrlsMiddleware;

class BlockUrlsMiddlewareTest extends Orchestra\Testbench\TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $myArray = include __DIR__ .'/../src/configs/api_configs.php';
        $app['config']->set('api_configs', $myArray);
    }
    
    public function test_blocks_if_referrer_has_gov(){
        $request = Request::create('/qweqwe/qweqwe', 'GET', [], [], [], ['HTTP_REFERER' => 'asda.gov']);
        $response = (new BlockUrlsMiddleware)->handle($request, function () {});
        $this->assertEquals($response->getStatusCode(), 302);
    }

    public function test_blocks_if_utm_host_has_gov(){
        $request = Request::create('/qweqwe/qweqwe', 'GET', ['utm_host' => 'asda.gov'], [], [], []);
        $response = (new BlockUrlsMiddleware)->handle($request, function () {});
        $this->assertEquals($response->getStatusCode(), 302);
    }

    public function test_blocks_if_in_list_of_urls_to_block(){
        config(['api_configs.defaults.list_of_urls_to_block' => ['asda.com' => 'asda.com']]);
        $request = Request::create('/qweqwe/qweqwe', 'GET', [], [], [], ['HTTP_REFERER' => 'asda.com']);
        $response = (new BlockUrlsMiddleware)->handle($request, function () {});
        $this->assertEquals($response->getStatusCode(), 302);
    }


    public function test_returns_next_if_everything_is_ok(){
        config(['api_configs.defaults.list_of_urls_to_block' => ['asda.com' => 'asda.com']]);
        $request = Request::create('/qweqwe/qweqwe', 'GET', [], [], [], ['HTTP_REFERER' => 'asdaa.com']);
        $response = (new BlockUrlsMiddleware)->handle($request, function () {});
        $this->assertEquals($response, null);
    }

    
}