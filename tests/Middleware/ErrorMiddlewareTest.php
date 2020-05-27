<?php
declare(strict_types=1);

use Branch\App;
use Branch\Interfaces\EnvInterface;
use Branch\Middleware\ErrorMiddleware;
use Branch\Tests\BaseTestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ErrorMiddlewareTest extends BaseTestCase
{
    use ProphecyTrait;

    protected $streamProphecy;

    protected $requestProphecy;

    protected $responseProphecy;

    protected $handlerProphecy;

    public function buildMiddleware(string $env): MiddlewareInterface
    {
        $appProphecy = $this->prophesize(App::class);
        $this->streamProphecy = $this->prophesize(StreamInterface::class);
        $this->requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $this->responseProphecy = $this->prophesize(ResponseInterface::class);
        $this->handlerProphecy = $this->prophesize(RequestHandlerInterface::class);

        $appProphecy->get(Argument::exact('env'))
            ->willReturn(['APP_ENV' => $env])
            ->shouldBeCalledTimes(1);

        return new ErrorMiddleware(
            $appProphecy->reveal(),
            $this->responseProphecy->reveal()
        );
    }

    public function testResponseReturnedIfNoError(): void
    {
        $middleware = $this->buildMiddleware(EnvInterface::ENV_PROD);

        $this->handlerProphecy->handle(Argument::type(ServerRequestInterface::class))
            ->willReturn($this->responseProphecy->reveal())
            ->shouldBeCalledTimes(1);
        $this->responseProphecy->withHeader()->shouldNotBeCalled();
        $this->responseProphecy->withStatus()->shouldNotBeCalled();
        $this->responseProphecy->getBody()->shouldNotBeCalled();
        $this->streamProphecy->write()->shouldNotBeCalled();

        $response = $middleware->process(
            $this->requestProphecy->reveal(),
            $this->handlerProphecy->reveal()
        );

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testReportCreatedIfError(): void
    {
        $middleware = $this->buildMiddleware(EnvInterface::ENV_PROD);

        $this->handlerProphecy->handle(Argument::type(ServerRequestInterface::class))
            ->willThrow(new \Exception('Test exception', 500))
            ->shouldBeCalledTimes(1);
        $this->responseProphecy->withHeader(
            Argument::type('string'),
            Argument::type('string')
        )
            ->willReturn($this->responseProphecy->reveal())
            ->shouldBeCalledTimes(1);
        $this->responseProphecy->withStatus(Argument::type('int'))
            ->willReturn($this->responseProphecy->reveal())
            ->shouldBeCalledTimes(1);
        $this->responseProphecy->getBody()
            ->willReturn($this->streamProphecy->reveal())
            ->shouldBeCalledTimes(1);
        $this->streamProphecy->write(Argument::type('string'))
            ->shouldBeCalledTimes(1);

        $response = $middleware->process(
            $this->requestProphecy->reveal(),
            $this->handlerProphecy->reveal()
        );

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testReportIsCreatedForDev(): void
    {
        $middleware = $this->buildMiddleware(EnvInterface::ENV_DEV);

        $formReportReflection = $this->getMethodReflection($middleware, 'formReport');
        $report = $formReportReflection->invokeArgs($middleware, [new \Exception('Test exception', 500)]);

        $this->assertTrue(
            is_array($report)
            && !array_diff_key([
                'code' => null,
                'message' => null,
                'file' => null,
                'line' => null,
                'trace' => null,
            ], $report)
        );
    }

    public function testReportIsCreatedForProd(): void
    {
        $middleware = $this->buildMiddleware(EnvInterface::ENV_PROD);

        $formReportReflection = $this->getMethodReflection($middleware, 'formReport');
        $report = $formReportReflection->invokeArgs($middleware, [new \Exception('Test exception', 500)]);

        $this->assertTrue(
            is_array($report)
            && !array_diff_key([
                'code' => null,
                'message' => null,
            ], $report)
        );
    }

    public function testHttpCodeForValidIntegerCode(): void
    {
        $middleware = $this->buildMiddleware(EnvInterface::ENV_PROD);

        $getHttpCodeReflection = $this->getMethodReflection($middleware, 'getHttpCode');
        $code = $getHttpCodeReflection->invokeArgs($middleware, [200]);

        $this->assertEquals(200, $code);
    }

    public function testHttpCodeForInvalidIntegerCode(): void
    {
        $middleware = $this->buildMiddleware(EnvInterface::ENV_PROD);

        $getHttpCodeReflection = $this->getMethodReflection($middleware, 'getHttpCode');
        $code = $getHttpCodeReflection->invokeArgs($middleware, [999]);

        $this->assertEquals(500, $code);
    }

    public function testHttpCodeForStringCode(): void
    {
        $middleware = $this->buildMiddleware(EnvInterface::ENV_PROD);

        $getHttpCodeReflection = $this->getMethodReflection($middleware, 'getHttpCode');
        $code = $getHttpCodeReflection->invokeArgs($middleware, ['code']);

        $this->assertEquals(500, $code);
    }

    public function testHttpCodeAllowanceForCorrectHttpCode(): void
    {
        $middleware = $this->buildMiddleware(EnvInterface::ENV_PROD);

        $isAllowedHttpCodeReflection = $this->getMethodReflection($middleware, 'isAllowedHttpCode');

        $this->assertTrue($isAllowedHttpCodeReflection->invokeArgs($middleware, [300]));
    }

    public function testHttpCodeAllowanceForNotCorrectHttpCode(): void
    {
        $middleware = $this->buildMiddleware(EnvInterface::ENV_PROD);

        $isAllowedHttpCodeReflection = $this->getMethodReflection($middleware, 'isAllowedHttpCode');

        $this->assertFalse($isAllowedHttpCodeReflection->invokeArgs($middleware, [99]));
        $this->assertFalse($isAllowedHttpCodeReflection->invokeArgs($middleware, [600]));
    }
}