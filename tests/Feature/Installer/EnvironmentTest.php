<?php

namespace Tests\Feature\Installer;

use App\Installer\Environment;
use Tests\TestCase;

class EnvironmentTest extends TestCase
{
    private Environment $env;

    protected function setUp(): void
    {
        parent::setUp();

        $this->env = new Environment(
            name: 'MyApp',
            key: 'base64:randomkey==',
            identificationKey: 'idKey123',
            url: 'http://myapp.test',
            dbHost: 'localhost',
            dbPort: '3306',
            dbName: 'myapp_db',
            dbUser: 'myapp_user',
            dbPassword: 'secret',
        );
    }

    public function test_get_name()
    {
        $this->assertEquals('MyApp', $this->env->getName());
    }

    public function test_get_key()
    {
        $this->assertEquals('base64:randomkey==', $this->env->getKey());
    }

    public function test_get_identification_key()
    {
        $this->assertEquals('idKey123', $this->env->getIdentificationKey());
    }

    public function test_get_url()
    {
        $this->assertEquals('http://myapp.test', $this->env->getUrl());
    }

    public function test_get_db_host()
    {
        $this->assertEquals('localhost', $this->env->getDbHost());
    }

    public function test_get_db_port()
    {
        $this->assertEquals('3306', $this->env->getDbPort());
    }

    public function test_get_db_name()
    {
        $this->assertEquals('myapp_db', $this->env->getDbName());
    }

    public function test_get_db_user()
    {
        $this->assertEquals('myapp_user', $this->env->getDbUser());
    }

    public function test_get_db_password()
    {
        $this->assertEquals('secret', $this->env->getDbPassword());
    }

    public function test_get_additional()
    {
        $additional = [
            'CACHE_DRIVER' => 'file',
            'SESSION_DRIVER' => 'database',
        ];

        // Use the with method to add additional variables
        $this->env = $this->env->with($additional);

        $this->assertEquals($additional, $this->env->getAdditional());
    }
}
