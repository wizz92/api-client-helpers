<?php

use Wizz\ApiClientHelpers\Helpers\CacheHelper;


class TestConfig extends Orchestra\Testbench\TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $myArray = include __DIR__ .'/../src/configs/api_configs.php';
        $app['config']->set('api_configs', $myArray);
    }
    
    public function testThatReturnsDefaultValueIfDomainNotExists(){
        $_SERVER['SERVER_NAME'] = 'domain_that_not_exists';
        $this->assertEquals(CacheHelper::conf('client_secret'), 'abc');
    }
    public function testThatReturnsCorrectValueIfDomainExists(){
        $_SERVER['SERVER_NAME'] = 'domain.net';
        $this->assertEquals(CacheHelper::conf('client_secret'), 'domain.net.client_secret');
    }

    public function testReturnsFullConfigIfInputIsEmpty(){
        $_SERVER['SERVER_NAME'] = 'domain.net';
        $this->assertTrue(is_array(CacheHelper::conf()));
    }

    public function testThatReturnsCorrectValueIfDomainNotExistsAndAllowDefaultIsSetToFalse(){
        $_SERVER['SERVER_NAME'] = 'domain_that_not_exists';
        $this->assertEquals(null, CacheHelper::conf('client_secret', false));
    }
    
}