<?php
declare(strict_types=1);

use Branch\Env;
use Branch\Tests\BaseTestCase;

class EnvTest extends BaseTestCase
{
    protected Env $env;

    protected function setUp(): void
    {
        $envPath = __DIR__ . '/sample-files/.env';

        $this->env = new Env($envPath);
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