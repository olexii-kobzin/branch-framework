<?php
declare(strict_types=1);

use Branch\App;
use Branch\Container\Loader;
use Branch\Interfaces\Container\ContainerInterface;
use Branch\Tests\BaseTestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

class LoaderTest extends BaseTestCase
{
    use ProphecyTrait;

    protected Loader $loader;

    protected $appProphecy;

    protected string $configDir = '';

    protected string $routesDir = '';

    public function setUp(): void
    {
        $this->appProphecy = $this->prophesize(App::class)->willImplement(ContainerInterface::class);

        $this->loader = new Loader(
            $this->appProphecy->reveal()
        );

        $this->configDir = dirname(__DIR__) . '/Mocks/Files/config';
        $this->routesDir = dirname(__DIR__) . '/Mocks/Files/routes';
    }

    public function testConfigsAreLoaded(): void
    {
        $this->appProphecy->get(Argument::exact('path.config'))
            ->willReturn($this->configDir)
            ->shouldBeCalledTimes(1);
        $this->appProphecy->set(
            Argument::containingString('config'),
            Argument::withKey('config')
        )
            ->shouldBeCalledTimes(2);

        $this->loader->loadConfigs();
    }

    public function testRoutesAreLoaded(): void
    {
        $this->appProphecy->get(Argument::exact('path.routes'))
            ->willReturn($this->routesDir)
            ->shouldBeCalledTimes(1);
        $this->appProphecy->set(
            Argument::containingString('routes.route'),
            Argument::withKey('route')
        )
            ->shouldBeCalledTimes(2);

        $this->loader->loadRoutes();
    }
}