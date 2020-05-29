<?php
declare(strict_types=1);

use Branch\Interfaces\Container\ContainerInterface;
use Branch\Middleware\MethodValidationMiddleware;
use Branch\Tests\BaseTestCase;
use Fig\Http\Message\StatusCodeInterface;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class MethodValidationMiddlewareTest extends BaseTestCase
{
    use ProphecyTrait;

    protected MethodValidationMiddleware $middleware;

    protected $containerProphecy;

    protected $requestProphecy;

    protected $responseProphecy;

    protected $handlerProphecy;

    public function setUp(): void
    {
        $this->containerProphecy = $this->prophesize(ContainerInterface::class);
        $this->requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $this->responseProphecy = $this->prophesize(ResponseInterface::class);
        $this->handlerProphecy = $this->prophesize(RequestHandlerInterface::class);

        $this->middleware = new MethodValidationMiddleware($this->containerProphecy->reveal());
    }

    public function testProcessedWithEmptyMethods(): void
    {
        $this->requestProphecy->getMethod()
            ->willReturn('GET')
            ->shouldBeCalledTimes(1);
        $this->containerProphecy->get(Argument::exact('_branch.routing.action.methods'))
            ->willReturn([])
            ->shouldBeCalledTimes(1);
        $this->handlerProphecy->handle(Argument::type(ServerRequestInterface::class))
            ->willReturn($this->responseProphecy->reveal())
            ->shouldBeCalledTimes(1);

        $response = $this->middleware->process(
            $this->requestProphecy->reveal(),
            $this->handlerProphecy->reveal()
        );

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testProcessedWithCorrectMethods(): void
    {
        $this->requestProphecy->getMethod()
            ->willReturn('PUT')
            ->shouldBeCalledTimes(1);
        $this->containerProphecy->get(Argument::exact('_branch.routing.action.methods'))
            ->willReturn(['POST', 'PUT'])
            ->shouldBeCalledTimes(1);
        $this->handlerProphecy->handle(Argument::type(ServerRequestInterface::class))
            ->willReturn($this->responseProphecy->reveal())
            ->shouldBeCalledTimes(1);

        $response = $this->middleware->process(
            $this->requestProphecy->reveal(),
            $this->handlerProphecy->reveal()
        );

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testExceptionIsThrownDuringProcessingWithWrongMethods(): void
    {
        $this->requestProphecy->getMethod()
            ->willReturn('GET')
            ->shouldBeCalledTimes(1);
        $this->containerProphecy->get(Argument::exact('_branch.routing.action.methods'))
            ->willReturn(['POST', 'PUT'])
            ->shouldBeCalledTimes(1);
        $this->handlerProphecy->handle(Argument::type(ServerRequestInterface::class))
            ->willReturn($this->responseProphecy->reveal())
            ->shouldNotBeCalled();

        $this->expectException(\Exception::class);
        $this->expectExceptionCode(StatusCodeInterface::STATUS_METHOD_NOT_ALLOWED);

        $this->middleware->process(
            $this->requestProphecy->reveal(),
            $this->handlerProphecy->reveal()
        );
    }
}