<?php
declare(strict_types=1);

use Branch\Interfaces\Middleware\MiddlewareHandlerInterface;
use Branch\Interfaces\Middleware\MiddlewarePipeInterface;
use Branch\Middleware\MiddlewarePipe;
use Branch\Tests\BaseTestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class MiddlewarePipeTest extends BaseTestCase
{
    use ProphecyTrait;

    protected MiddlewarePipeInterface $middlewarePipe;

    protected $middlewareHandlerProphecy;

    public function setUp(): void
    {
        $this->middlewareHandlerProphecy = $this->prophesize(MiddlewareHandlerInterface::class);

        $this->middlewarePipe = new MiddlewarePipe($this->middlewareHandlerProphecy->reveal());
    }

    public function testPipeIsEmptyAtStart(): void
    {
        $pipeReflection = $this->getPropertyReflection($this->middlewarePipe, 'pipe');

        $this->assertCount(0, $pipeReflection->getValue($this->middlewarePipe));
    }

    public function testMiddlewareIsAddedToPipe(): void
    {
        $pipeReflection = $this->getPropertyReflection($this->middlewarePipe, 'pipe');

        $middlewareProphecy = $this->prophesize(MiddlewareInterface::class);

        $this->middlewarePipe->pipe($middlewareProphecy->reveal());

        $this->assertCount(1, $pipeReflection->getValue($this->middlewarePipe));
    }

    public function testMiddlewareHandlerIsCalledCorrectly(): void
    {
        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $middlewareHandlerProphecy = $this->prophesize(RequestHandlerInterface::class);

        $this->middlewareHandlerProphecy
            ->setPipe(Argument::type('array'))
            ->shouldBeCalledTimes(1);
        $this->middlewareHandlerProphecy
            ->setFallbackHandler(Argument::type(RequestHandlerInterface::class))
            ->shouldBeCalledTimes(1);
        $this->middlewareHandlerProphecy
            ->handle(Argument::type(ServerRequestInterface::class))
            ->willReturn($responseProphecy->reveal())
            ->shouldBeCalledTimes(1);

        $response = $this->middlewarePipe->process(
            $requestProphecy->reveal(),
            $middlewareHandlerProphecy->reveal()
        );

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }
}