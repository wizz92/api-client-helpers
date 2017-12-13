<?php

class TestConfig extends Orchestra\Testbench\TestCase
{
    public function setUp()
    {
        parent::setUp();
    }

    protected function getEnvironmentSetUp($app)
    {
        $myArray = include __DIR__ .'/../src/configs/api_configs.php';
        $app['config']->set('api_configs', $myArray);
    }
    
    public function testThatReturnsDefaultValueIfDomainNotExists(){
        $_SERVER['SERVER_NAME'] = 'domain_that_not_exists';
        $this->assertEquals(from_config('client_secret'), 'abc');
    }
    public function testThatReturnsCorrectValueIfDomainExists(){
        $_SERVER['SERVER_NAME'] = 'domain.net';
        $this->assertEquals(from_config('client_secret'), 'domain.net.client_secret');
    }
    
}