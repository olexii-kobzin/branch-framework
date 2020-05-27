<?php
declare(strict_types=1);

use Branch\Env;
use Branch\Tests\BaseTestCase;

class EnvTest extends BaseTestCase
{
    protected Env $env;

    protected string $path;

    public function setUp(): void
    {
        $this->path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . microtime() . uniqid();

        $env = <<<ENV
ENV_PARAM_1=value 1   
ENV_PARAM_2=value 2
ENV;
        file_put_contents($this->path, $env);

        $this->env = new Env($this->path);
    }

    public function tearDown(): void
    {
        unlink($this->path);
    }

    public function testConfigIsReadIntoTheArray()
    {
        $config = $this->env->get();

        $this->assertIsArray($config);
    }

    public function testConfigWasReadCorrectly()
    {
        $config = $this->env->get();

        $this->assertArrayHasKey('ENV_PARAM_1', $config);
        $this->assertArrayHasKey('ENV_PARAM_2', $config);

        $this->assertEquals('value 1', $config['ENV_PARAM_1']);
        $this->assertEquals('value 2', $config['ENV_PARAM_2']);
    }
}