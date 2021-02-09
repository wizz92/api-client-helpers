<?php
use Wizz\ApiClientHelpers\Helpers\CacheHelper;

class TestCache extends Orchestra\Testbench\TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $myArray = include __DIR__ .'/../src/configs/api_configs.php';
        $app['config']->set('api_configs', $myArray);
    }

    public function test_should_we_cache_returns_false()
    {
        $_SERVER['SERVER_NAME'] = 'domain_that_not_exists';
        config(['api_configs.defaults.use_cache_frontend' => false]);
        $this->assertFalse(CacheHelper::shouldWeCache());
    }
}
