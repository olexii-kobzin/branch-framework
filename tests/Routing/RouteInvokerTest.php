<?php
declare(strict_types=1);

use Branch\App;
use Branch\Interfaces\Container\ContainerInterface;
use Branch\Interfaces\Middleware\ActionInterface;
use Branch\Interfaces\Middleware\CallbackActionInterface;
use Branch\Interfaces\Middleware\MiddlewarePipeInterface;
use Branch\Routing\RouteInvoker;
use Branch\Tests\BaseTestCase;
use Psr\Http\Message\ServerRequestInterface;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RouteInvokerTest extends BaseTestCase
{
    use ProphecyTrait;

    protected RouteInvoker $invoker;

    protected $appProphecy;

    protected $callbackActionProphecy;

    protected $requestProphecy;

    protected $pipeProphecy;

    protected $middlewareProphecy;

    public function setUp(): void
    {
        $this->appProphecy = $this->prophesize(App::class)->willImplement(ContainerInterface::class);
        $this->callbackActionProphecy = $this->prophesize(CallbackActionInterface::class)
            ->willImplement(ActionInterface::class);
        $this->requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $this->pipeProphecy = $this->prophesize(MiddlewarePipeInterface::class);
        $this->middlewareProphecy = $this->prophesize(MiddlewareInterface::class);

        $responseProphecy = $this->prophesize(ResponseInterface::class);

        $this->appProphecy->get(Argument::exact('middleware'), Argument::type('bool'))
            ->willReturn(fn(array  $env, array $config): array =>
            [
                'MiddlewareA',
                'MiddlewareB',
            ]);
        $this->appProphecy->get(Argument::exact('env'))
            ->willReturn([]);
        $this->appProphecy->get(Argument::exact('settings'))
            ->willReturn([]);
        $this->callbackActionProphecy->setArgs(Argument::type('array'));
        $this->pipeProphecy->process(
            Argument::type(ServerRequestInterface::class),
            Argument::type(RequestHandlerInterface::class)
        )->willReturn($responseProphecy->reveal());

        $this->invoker = new RouteInvoker(
            $this->appProphecy->reveal(),
            $this->callbackActionProphecy->reveal(),
            $this->requestProphecy->reveal(),
            $this->pipeProphecy->reveal()
        );        
    }

    public function testPathIsEmptyAfterCreation(): void
    {
        $pathReflection = $this->getPropertyReflection($this->invoker, 'path');

        $this->assertFalse($pathReflection->isInitialized($this->invoker));
    }

    public function testMiddlewareIsEmptyAfterCreation(): void
    {
        $middlewareReflection = $this->getPropertyReflection($this->invoker, 'middleware');

        $this->assertCount(0, $middlewareReflection->getValue($this->invoker));
    }

    public function testInvokeWithActionAsHandlerReturnsResponse(): void
    {
        $actionProphecy = $this->prophesize(ActionInterface::class);

        $pathReflection = $this->getPropertyReflection($this->invoker, 'path');

        $this->pipeProphecy->pipe(Argument::type(MiddlewareInterface::class))
            ->shouldBeCalledTimes(2);

        $this->appProphecy->make(Argument::that(fn(string $argument): bool => in_array($argument, [
            'MiddlewareA',
            'MiddlewareB',
        ])))
            ->willReturn($this->middlewareProphecy->reveal())
            ->shouldBeCalledTimes(2);

        $this->appProphecy->make(Argument::exact('ActionA'))
            ->willReturn($actionProphecy->reveal())
            ->shouldBeCalledTimes(1);

        $response = $this->invoker->invoke([
            'path' => 'path-part-1/path-part-2',
            'handler' => 'ActionA',
        ], []);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals('path-part-1/path-part-2', $pathReflection->getValue($this->invoker));
    }

    public function testInvokeWithCallbackReturnsResponse(): void
    {
        $responseProphecy = $this->prophesize(ResponseInterface::class);

        $this->pipeProphecy->pipe(Argument::type(MiddlewareInterface::class))
            ->shouldBeCalledTimes(2);

        $this->appProphecy->make(Argument::that(fn(string $argument): bool => in_array($argument, [
            'MiddlewareA',
            'MiddlewareB',
        ])))
            ->willReturn($this->middlewareProphecy->reveal())
            ->shouldBeCalledTimes(2);

        $this->callbackActionProphecy->setHandler(Argument::type(Closure::class))
            ->shouldBeCalledTimes(1);

        $response = $this->invoker->invoke([
            'path' => 'path-part-1/path-part-2',
            'handler' => fn(): ResponseInterface => $responseProphecy->reveal(),
        ], []);

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testWrongHandlerTypeResultsInException(): void
    {
        $actionProphecy = $this->prophesize(ActionInterface::class);

        $this->pipeProphecy->pipe(Argument::type(MiddlewareInterface::class))
            ->shouldNotBeCalled();

        $this->appProphecy->make(Argument::that(fn(string $argument): bool => in_array($argument, [
            'MiddlewareA',
            'MiddlewareB',
        ])))
            ->willReturn($this->middlewareProphecy->reveal())
            ->shouldBeCalledTimes(2);

        $this->appProphecy->make(Argument::exact('ActionA'))
            ->willReturn($actionProphecy->reveal())
            ->shouldNotBeCalled();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Handler type is not recognized');

        $this->invoker->invoke([
            'path' => 'path-part-1/path-part-2',
            'handler' => [],
        ], []);
    }

    public function testMiddlewareIsMerged(): void
    {
        $actionProphecy = $this->prophesize(ActionInterface::class);

        $middlewarePropertyReflection = $this->getPropertyReflection($this->invoker, 'middleware');

        $this->pipeProphecy->pipe(Argument::type(MiddlewareInterface::class))
            ->shouldBeCalledTimes(4);

        $this->appProphecy->make(Argument::that(fn(string $argument): bool => in_array($argument, [
            'MiddlewareA',
            'MiddlewareB',
            'MiddlewareC',
            'MiddlewareD',
        ])))->willReturn($this->middlewareProphecy->reveal())
            ->shouldBeCalledTimes(4);

        $this->appProphecy->make(Argument::exact('ActionA'))
            ->willReturn($actionProphecy->reveal())
            ->shouldBeCalledTimes(1);

        $this->invoker->invoke([
            'path' => 'path-part-1/path-part-2',
            'middleware' => [
                'MiddlewareC',
                'MiddlewareD',
            ],
            'handler' => 'ActionA',
        ], []);

        $this->assertCount(4, $middlewarePropertyReflection->getValue($this->invoker));
    }

    public function testMiddlewareWithArguemntsReturnsMiddlewareInterface(): void
    {
        $actionProphecy = $this->prophesize(ActionInterface::class);

        $middlewarePropertyReflection = $this->getPropertyReflection($this->invoker, 'middleware');

        $this->pipeProphecy->pipe(Argument::type(MiddlewareInterface::class))
            ->shouldBeCalledTimes(4);

        $this->appProphecy->make(Argument::that(fn(string $argument): bool => in_array($argument, [
            'MiddlewareA',
            'MiddlewareB',
            'MiddlewareD',
        ])))->willReturn($this->middlewareProphecy->reveal())
            ->shouldBeCalledTimes(3);

        $this->appProphecy->make(
            Argument::exact('MiddlewareC'),
            Argument::size(2)
        )->willReturn($this->middlewareProphecy->reveal())
            ->shouldBeCalledTimes(1);

        $this->appProphecy->make(Argument::exact('ActionA'))
            ->willReturn($actionProphecy->reveal())
            ->shouldBeCalledTimes(1);

        $this->invoker->invoke([
            'path' => 'path-part-1/path-part-2',
            'middleware' => [
                'MiddlewareC' => [
                    'param1' => 'value1',
                    'param2' => 'value2'
                ],
                'MiddlewareD',
            ],
            'handler' => 'ActionA',
        ], []);

        $buildMiddleware = $middlewarePropertyReflection->getValue($this->invoker);

        $this->assertTrue(isset($buildMiddleware['MiddlewareC']));
        $this->assertInstanceOf(MiddlewareInterface::class, $buildMiddleware['MiddlewareC']);
    }

}