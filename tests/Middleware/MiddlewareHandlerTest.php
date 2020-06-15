<?php
declare(strict_types=1);

use Branch\Middleware\MiddlewareHandler;
use Branch\Tests\BaseTestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class MiddlewareHandlerTest extends BaseTestCase
{
    use ProphecyTrait;
    
    protected MiddlewareHandler $middlewareHandler;

    public function setUp(): void
    {
        $this->middlewareHandler = new MiddlewareHandler();
    }

    public function checkPipeIsEmptyAfterCreation(): void
    {
        $pipeReflection = $this->getPropertyReflection($this->middlewareHandler, 'pipe');

        $this->assertCount(0, $pipeReflection->getValue($this->middlewareHandler));
    }

    public function testFallbackHandlerIsEmptyAfterCreation(): void
    {
        $fallbackHandler = $this->getPropertyReflection($this->middlewareHandler, 'fallbackHandler');

        $this->assertFalse($fallbackHandler->isInitialized($this->middlewareHandler));
    }

    public function testPipeCanBeSet(): void
    {
        $pipeReflection = $this->getPropertyReflection($this->middlewareHandler, 'pipe');

        $this->middlewareHandler->setPipe([
            'testValue1',
            'testValue2',
        ]);

        $this->assertCount(2, $pipeReflection->getValue($this->middlewareHandler));
    }

    public function tesetFallbackHandlerCanBeSet(): void
    {
        $requestHandlerProphecy = $this->prophesize(RequestHandlerInterface::class);

        $requestHandlerReflection = $this->getPropertyReflection($this->middlewareHandler, 'fallbackHandler');

        $this->middlewareHandler->setFallbackHandler($requestHandlerProphecy->reveal());

        $this->assertCount(1, $requestHandlerReflection->getValue($this->middlewareHandler));
    }

    public function testHandleThrowsAnErrorIfFallbackHandlerIsEmpty(): void
    {
        $requestProphecy = $this->prophesize(ServerRequestInterface::class);

        $this->expectException(\LogicException::class);

        $this->middlewareHandler->handle($requestProphecy->reveal());
    }

    public function testFallbackHandlerIsUsedIfPipeIsEmpty(): void
    {
        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $requestHandlerProphecy = $this->prophesize(RequestHandlerInterface::class);
        $middlewareProphecy = $this->prophesize(MiddlewareInterface::class);

        $requestHandlerProphecy->handle(Argument::type(ServerRequestInterface::class))
            ->willReturn($responseProphecy->reveal())
            ->shouldBeCalledTimes(1);
        $middlewareProphecy->process(
            Argument::type(ServerRequestInterface::class),
            Argument::type(RequestHandlerInterface::class)
        )
            ->willReturn($responseProphecy->reveal())
            ->shouldNotBeCalled();

        $this->middlewareHandler->setFallbackHandler($requestHandlerProphecy->reveal());

        $response = $this->middlewareHandler->handle($requestProphecy->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testOneMiddlewareIsCalledIfPresent(): void
    {
        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $requestHandlerProphecy = $this->prophesize(RequestHandlerInterface::class);
        $middlewareProphecy = $this->prophesize(MiddlewareInterface::class);

        $pipeReflection = $this->getPropertyReflection($this->middlewareHandler, 'pipe');

        $requestHandlerProphecy->handle(Argument::type(ServerRequestInterface::class))
            ->willReturn($responseProphecy->reveal())
            ->shouldNotBeCalled();
        $middlewareProphecy->process(
            Argument::type(ServerRequestInterface::class),
            Argument::type(RequestHandlerInterface::class)
        )
            ->willReturn($responseProphecy->reveal())
            ->shouldBeCalledTimes(1);

        $middleware = $middlewareProphecy->reveal();

        $this->middlewareHandler->setFallbackHandler($requestHandlerProphecy->reveal());
        $this->middlewareHandler->setPipe([
            $middleware,
            clone $middleware,
        ]);

        $response = $this->middlewareHandler->handle($requestProphecy->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertCount(1, $pipeReflection->getValue($this->middlewareHandler));
    }

    public function testMiddlewareIsTakenFromFrontOfPipe(): void
    {
        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $requestHandlerProphecy = $this->prophesize(RequestHandlerInterface::class);
        $middlewareProphecy = $this->prophesize(MiddlewareInterface::class);
        $middleware2Prophecy = $this->prophesize(MiddlewareInterface::class);

        $pipeReflection = $this->getPropertyReflection($this->middlewareHandler, 'pipe');

        $requestHandlerProphecy->handle(Argument::type(ServerRequestInterface::class))
            ->willReturn($responseProphecy->reveal())
            ->shouldNotBeCalled();
        $middlewareProphecy->process(
            Argument::type(ServerRequestInterface::class),
            Argument::type(RequestHandlerInterface::class)
        )
            ->willReturn($responseProphecy->reveal())
            ->shouldBeCalledTimes(1);
        $middleware2Prophecy->process(
            Argument::type(ServerRequestInterface::class),
            Argument::type(RequestHandlerInterface::class)
        )
            ->willReturn($responseProphecy->reveal())
            ->shouldNotBeCalled();

        $this->middlewareHandler->setFallbackHandler($requestHandlerProphecy->reveal());
        $this->middlewareHandler->setPipe([
            $middlewareProphecy->reveal(),
            $middleware2Prophecy->reveal(),
        ]);

        $this->middlewareHandler->handle($requestProphecy->reveal());

        $pipe = $pipeReflection->getValue($this->middlewareHandler);

        $this->assertInstanceOf(MiddlewareInterface::class, reset($pipe));
        $this->assertCount(1, $pipe);
    }
}