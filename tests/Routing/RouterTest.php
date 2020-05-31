<?php
declare(strict_types=1);

use Branch\Interfaces\Container\ContainerInterface;
use Branch\Interfaces\Routing\RouteConfigBuilderInterface;
use Branch\Interfaces\Routing\RouteInvokerInterface;
use Branch\Interfaces\Routing\RouterInterface;
use Branch\Routing\Router;
use Branch\Tests\BaseTestCase;
use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

class RouterTest extends BaseTestCase
{
    use ProphecyTrait;

    protected static $paths = [
        'root' => [
            'requestedPath' => '/',
            'path' => '',
            'pattern' => '/^\/$/',
            'name' => 'rootRoute',
        ],
        'route' => [
            'requestedPath' => '/route/path',
            'path' => 'route/path',
            'pattern' => '/^\/route\/path$/',
            'name' => 'simpleRoute',
        ],
        'routeWithParams' => [
            'requestedPath' => '/route/10/path/test',
            'path' => 'route/:param1/path/:param2',
            'pattern' => "/^\/route\/(?'param1'.+)\/path\/(?'param2'.+)$/",
            'name' => 'routeWithParams',
        ],
    ];

    protected $containerProphecy;

    protected $invokerProphecy;

    protected $configBuilderProphecy;

    protected $requestProphecy;

    protected $responseProphecy;

    protected function setUpRouter(string $path): RouterInterface
    {
        $this->containerProphecy = $this->prophesize(ContainerInterface::class);
        $this->invokerProphecy = $this->prophesize(RouteInvokerInterface::class);
        $this->configBuilderProphecy = $this->prophesize(RouteConfigBuilderInterface::class);
        $this->requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $this->responseProphecy = $this->prophesize(ResponseInterface::class);

        $emitterProphecy = $this->prophesize(EmitterInterface::class);
        $uriProphecy = $this->prophesize(UriInterface::class);

        $emitterProphecy->emit(Argument::type(ResponseInterface::class))
            ->willReturn(true);
        $uriProphecy->getPath()->willReturn($path);

        $this->containerProphecy->set(Argument::type('string'), Argument::any());
        $this->containerProphecy->get('_branch.routing.routes')->willReturn(fn() => null);
        $this->requestProphecy->getUri()->willReturn($uriProphecy->reveal());

        return new Router(
            $this->containerProphecy->reveal(),
            $this->invokerProphecy->reveal(),
            $this->configBuilderProphecy->reveal(),
            $this->requestProphecy->reveal(),
            $this->responseProphecy->reveal(),
            $emitterProphecy->reveal()
        );
    }

    public function testGroupStackIsEmptyAfterCreation(): void
    {
        $router = $this->setUpRouter(self::$paths['root']['requestedPath']);
        $groupStackReflection = $this->getPropertyReflection($router, 'groupStack');

        $this->assertCount(0, $groupStackReflection->getValue($router));
    }

    public function testRoutesAreEmptyAfterCreation(): void
    {
        $router = $this->setUpRouter(self::$paths['root']['requestedPath']);
        $routesReflection = $this->getPropertyReflection($router, 'routes');

        $this->assertCount(0, $routesReflection->getValue($router));
    }

    public function testRouterWithRootRoute(): void
    {
        $router = $this->setUpRouter(self::$paths['root']['requestedPath']);

        $config = fn(RouterInterface $router) => $router->get([
            'path' => self::$paths['root']['path']
        ], fn(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface => $response);
        
        $this->configBuilderProphecy->getRouteConfig(
            Argument::type('array'),
            Argument::type('array')
        )
            ->willReturn([
                'path' =>self::$paths['root']['requestedPath'],
                'pattern' => self::$paths['root']['pattern']
            ])
            ->shouldBeCalledTimes(1);

        $this->containerProphecy->invoke(Argument::type(\Closure::class))
            ->willReturn(call_user_func($config, $router))
            ->shouldBeCalledTimes(1);

        $this->invokerProphecy->invoke(
            Argument::type('array'),
            Argument::type('array')
        )
            ->willReturn($this->responseProphecy->reveal())
            ->shouldBeCalledTimes(1);

        $this->assertTrue($router->init());
    }

    /**
     * @dataProvider simpleRouteProvider
     */
    public function testRouterWithAllRequestTypes(\Closure $config): void
    {
        $router = $this->setUpRouter(self::$paths['route']['requestedPath']);
        
        $this->configBuilderProphecy->getRouteConfig(
            Argument::type('array'),
            Argument::type('array')
        )
            ->willReturn([
                'path' =>self::$paths['route']['requestedPath'],
                'pattern' => self::$paths['route']['pattern']
            ])
            ->shouldBeCalledTimes(1);

        $this->containerProphecy->invoke(Argument::type(\Closure::class))
            ->willReturn(call_user_func($config, $router))
            ->shouldBeCalledTimes(1);

        $this->invokerProphecy->invoke(
            Argument::type('array'),
            Argument::type('array')
        )
            ->willReturn($this->responseProphecy->reveal())
            ->shouldBeCalledTimes(1);

        $this->assertTrue($router->init());
    }

    public function testRouteWithParams(): void 
    {
        $router = $this->setUpRouter(self::$paths['routeWithParams']['requestedPath']);

        $config = fn(RouterInterface $router) => $router->get([
            'path' => self::$paths['routeWithParams']['path'],
        ], fn(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface => $response);
        
        $this->configBuilderProphecy->getRouteConfig(
            Argument::type('array'),
            Argument::type('array')
        )
            ->willReturn([
                'path' =>self::$paths['routeWithParams']['requestedPath'],
                'pattern' => self::$paths['routeWithParams']['pattern']
            ])
            ->shouldBeCalledTimes(1);

        $this->containerProphecy->invoke(Argument::type(\Closure::class))
            ->willReturn(call_user_func($config, $router))
            ->shouldBeCalledTimes(1);

        $this->invokerProphecy->invoke(
            Argument::type('array'),
            Argument::type('array')
        )
            ->willReturn($this->responseProphecy->reveal())
            ->shouldBeCalledTimes(1);

        $this->assertTrue($router->init());
    }

    public function testExceptionIsThrownIfRouteNotFound(): void
    {
        $router = $this->setUpRouter('/path/does/not/exists');

        $config = fn(RouterInterface $router) => $router->get([
            'path' => self::$paths['routeWithParams']['path']
        ], fn(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface => $response);
        
        $this->configBuilderProphecy->getRouteConfig(
            Argument::type('array'),
            Argument::type('array')
        )
            ->willReturn([
                'path' =>self::$paths['routeWithParams']['requestedPath'],
                'pattern' => self::$paths['routeWithParams']['pattern']
            ])
            ->shouldBeCalledTimes(1);

        $this->containerProphecy->invoke(Argument::type(\Closure::class))
            ->willReturn(call_user_func($config, $router))
            ->shouldBeCalledTimes(1);

        $this->invokerProphecy->invoke(
            Argument::type('array'),
            Argument::type('array')
        )
            ->willReturn($this->responseProphecy->reveal())
            ->shouldNotBeCalled();

        $this->expectException(\Exception::class);
        $this->expectExceptionCode(404);

        $router->init();
    }

    public function testRouterWithRouteInGroup(): void
    {
        $router = $this->setUpRouter('/group/groupRoute');

        $groupConfig = fn(RouterInterface $router) => $router->group([
            'path' => 'group'
        ], fn(RouterInterface $router) => null);

        $routeConfig = fn(RouterInterface $router) => $router->get([
            'path' => 'groupRoute'
        ], fn(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface => $response);
        
        $this->configBuilderProphecy->getGroupConfig(
            Argument::type('array'),
            Argument::type('array')
        )
            ->willReturn(['path' => 'group'])
            ->shouldBeCalledTimes(1);

        $this->configBuilderProphecy->getRouteConfig(
            Argument::type('array'),
            Argument::type('array')
        )
            ->willReturn([
                'path' => '/group/groupRoute',
                'pattern' => '/^\/group\/groupRoute$/'
            ])
            ->shouldBeCalledTimes(1);

        $this->containerProphecy->invoke(Argument::type(\Closure::class))
            ->willReturn(
                call_user_func($groupConfig, $router),
                call_user_func($routeConfig, $router)
            )
            ->shouldBeCalledTimes(2);

        $this->invokerProphecy->invoke(
            Argument::type('array'),
            Argument::type('array')
        )
            ->willReturn($this->responseProphecy->reveal())
            ->shouldBeCalledTimes(1);

        $this->assertTrue($router->init());
    }

    public function testRouteConfigIsMergedCorrectly(): void
    {
        $router = $this->setUpRouter(self::$paths['routeWithParams']['requestedPath']);
        $routesReflection = $this->getPropertyReflection($router, 'routes');

        $this->configBuilderProphecy->getRouteConfig(
            Argument::exact([]),
            Argument::that(fn(array $argument): bool =>
                isset($argument['methods'])
                && is_array($argument['methods'])
                && !array_diff(['GET', 'PUT'], $argument['methods'])
                && isset($argument['path'])
                && is_string($argument['path'])
                && isset($argument['handler'])
                && is_callable($argument['handler'])
            )
        )
            ->willReturn([])
            ->shouldBeCalledTimes(1);

        $router->map(['GET', 'PUT'], [
            'path' => self::$paths['routeWithParams']['path']
        ], fn() => null);

        $this->assertCount(1, $routesReflection->getValue($router));
    }

    public function testPathByNameIsFoundIfExists(): void
    {
        $router = $this->setUpRouter(self::$paths['root']['requestedPath']);

        $this->configBuilderProphecy->getRouteConfig(
            Argument::type('array'),
            Argument::type('array')
        )
            ->willReturn([
                'path' =>self::$paths['root']['requestedPath'],
                'pattern' => self::$paths['root']['pattern'],
                'name' =>  self::$paths['root']['name']
            ])
            ->shouldBeCalledTimes(1);

        $router->map([], [
            'path' => self::$paths['root']['path']
        ], fn() => null);

        $path = $router->getPathByName(self::$paths['root']['name']);

        $this->assertEquals('/', $path);
    }

    public function testExcpetionIsThrownIfRouteNameDoesNotExists(): void
    {
        $router = $this->setUpRouter(self::$paths['root']['requestedPath']);

        $this->configBuilderProphecy->getRouteConfig(
            Argument::type('array'),
            Argument::type('array')
        )
            ->willReturn([
                'path' =>self::$paths['root']['requestedPath'],
                'pattern' => self::$paths['root']['pattern'],
                'name' =>  self::$paths['root']['name']
            ])
            ->shouldBeCalledTimes(1);

        $router->map([], [
            'path' => self::$paths['root']['path']
        ], fn() => null);

        $this->expectException(\Exception::class);

        $router->getPathByName('nameDoesNotExists');
    }

    public function testPathByNameIsBuiltCorrectly(): void
    {
        $router = $this->setUpRouter(self::$paths['route']['requestedPath']);

        $this->configBuilderProphecy->getRouteConfig(
            Argument::type('array'),
            Argument::type('array')
        )
            ->willReturn([
                'path' => self::$paths['route']['requestedPath'],
                'pattern' => self::$paths['route']['pattern'],
                'name' =>  self::$paths['route']['name']
            ])
            ->shouldBeCalledTimes(1);

        $router->map([], [
            'path' => self::$paths['route']['path']
        ], fn() => null);

        $path = $router->getPathByName(self::$paths['route']['name']);

        $this->assertEquals(self::$paths['route']['requestedPath'], $path);
    }

    public function testPathByNameWithParamsIsBuiltCorrectly(): void
    {
        $router = $this->setUpRouter(self::$paths['routeWithParams']['requestedPath']);

        $this->configBuilderProphecy->getRouteConfig(
            Argument::type('array'),
            Argument::type('array')
        )
            ->willReturn([
                'path' => self::$paths['routeWithParams']['requestedPath'],
                'pattern' => self::$paths['routeWithParams']['pattern'],
                'name' =>  self::$paths['routeWithParams']['name']
            ])
            ->shouldBeCalledTimes(1);

        $router->map([], [
            'path' => self::$paths['routeWithParams']['path']
        ], fn() => null);

        $path = $router->getPathByName(self::$paths['routeWithParams']['name'], [
            'param1' => 10,
            'param2' => 'test',
        ]);

        $this->assertEquals(self::$paths['routeWithParams']['requestedPath'], $path);
    }


    public function testPathByNameIsBuiltCorrectlyWithExtraParams(): void
    {
        $router = $this->setUpRouter(self::$paths['routeWithParams']['requestedPath']);

        $this->configBuilderProphecy->getRouteConfig(
            Argument::type('array'),
            Argument::type('array')
        )
            ->willReturn([
                'path' => self::$paths['routeWithParams']['requestedPath'],
                'pattern' => self::$paths['routeWithParams']['pattern'],
                'name' =>  self::$paths['routeWithParams']['name']
            ])
            ->shouldBeCalledTimes(1);

        $router->map([], [
            'path' => self::$paths['routeWithParams']['path']
        ], fn() => null);

        $path = $router->getPathByName(self::$paths['routeWithParams']['name'], [
            'param1' => 10,
            'param2' => 'test',
            'param3' => 'hello',
            'param4' => 'world',
        ]);

        $this->assertEquals(
            self::$paths['routeWithParams']['requestedPath'] . '?' . http_build_query([
                'param3' => 'hello',
                'param4' => 'world',
            ]),
            $path
        );
    }

    public function testExceptionIsThrownIfRouteParamsDoesNotMatch(): void
    {
        $router = $this->setUpRouter(self::$paths['routeWithParams']['requestedPath']);

        $this->configBuilderProphecy->getRouteConfig(
            Argument::type('array'),
            Argument::type('array')
        )
            ->willReturn([
                'path' => self::$paths['routeWithParams']['requestedPath'],
                'pattern' => self::$paths['routeWithParams']['pattern'],
                'name' =>  self::$paths['routeWithParams']['name']
            ])
            ->shouldBeCalledTimes(1);

        $router->map([], [
            'path' => self::$paths['routeWithParams']['path']
        ], fn() => null);

        $this->expectException(\UnexpectedValueException::class);

        $router->getPathByName(self::$paths['routeWithParams']['name'], [
            'param1' => 10,
        ]);
    }

    public function simpleRouteProvider(): array
    {
        return  [
            [fn(RouterInterface $router) => $router->get([
                'path' => self::$paths['route']['path']
            ], fn(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface => $response)],
            [fn(RouterInterface $router) => $router->post([
                'path' => self::$paths['route']['path']
            ], fn(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface => $response)],
            [fn(RouterInterface $router) => $router->put([
                'path' => self::$paths['route']['path']
            ], fn(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface => $response)],
            [fn(RouterInterface $router) => $router->patch([
                'path' => self::$paths['route']['path']
            ], fn(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface => $response)],
            [fn(RouterInterface $router) => $router->delete([
                'path' => self::$paths['route']['path']
            ], fn(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface => $response)],
            [fn(RouterInterface $router) => $router->options([
                'path' => self::$paths['route']['path']
            ], fn(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface => $response)],
            [fn(RouterInterface $router) => $router->any([
                'path' => self::$paths['route']['path']
            ], fn(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface => $response)],
            [fn(RouterInterface $router) => $router->map(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], [
                'path' => self::$paths['route']['path']
            ], fn(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface => $response)],
        ];
    }
}