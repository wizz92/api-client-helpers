<?php

class TestValidation extends Orchestra\Testbench\TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $myArray = include __DIR__ .'/../src/configs/api_configs.php';
        $app['config']->set('api_configs', $myArray);
    }
    
    public function test_validate_frontend_config(){
        $_SERVER['SERVER_NAME'] = 'domain_that_not_exists';
        $this->assertTrue(validate_frontend_config());
    }

    public function test_fails_validate_frontend_config_frontend_repo_url_is_false(){
        $_SERVER['SERVER_NAME'] = 'domain_that_not_exists';
        config(['api_configs.defaults.frontend_repo_url' => false]);
        $this->assertFalse(validate_frontend_config());
    }

    public function test_validate_redirect_config_fails_on_default(){
        $_SERVER['SERVER_NAME'] = 'domain.net';
        $this->assertFalse(validate_redirect_config());
    }

    public function test_validate_redirect_config_fails_on_domain_false(){
        $_SERVER['SERVER_NAME'] = 'domain.net';
        config(['api_configs.domain.net.secret_url' => false]);
        $this->assertFalse(validate_redirect_config());
    }

    public function test_validate_redirect_config_success_on_domain_true(){
        $_SERVER['SERVER_NAME'] = 'domain';
        config(['api_configs.domain.secret_url' => true]);
        $this->assertTrue(validate_redirect_config());
    }
    
}