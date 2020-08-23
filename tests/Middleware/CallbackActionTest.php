<?php
declare(strict_types=1);

use Branch\Middleware\CallbackAction;
use Branch\Tests\BaseTestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class CallbackActionTest extends BaseTestCase
{
    use ProphecyTrait;

    protected CallbackAction $callbackAction;

    public function setUp(): void
    {
        $responseProphecy = $this->prophesize(ResponseInterface::class);
        
        $this->callbackAction = new CallbackAction($responseProphecy->reveal());
    }

    public function testHandlerIsEmptyAfterCreation(): void
    {
        $handlerReflection = $this->getPropertyReflection($this->callbackAction, 'handler');

        $this->assertFalse($handlerReflection->isInitialized($this->callbackAction));
    }

    public function testHandlerCanBeSet(): void
    {
        $handlerReflection = $this->getPropertyReflection($this->callbackAction, 'handler');

        $this->callbackAction->setHandler(fn() => null);

        $this->assertInstanceOf(\Closure::class, $handlerReflection->getValue($this->callbackAction));
    }

    public function testRunReturnsResponseObject(): void
    {
        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $responseProphecy = $this->prophesize(ResponseInterface::class);

        $callbackMock = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['__invoke'])
            ->getMock();

        $callbackMock->expects($this->once())
            ->method('__invoke')
            ->with(
                $this->isInstanceOf(ServerRequestInterface::class),
                $this->isInstanceOf(ResponseInterface::class),
                $this->isType('array')
            )
            ->willReturn($responseProphecy->reveal());

        $this->callbackAction->setHandler(\Closure::fromCallable($callbackMock));

        $response = $this->callbackAction->run(
            $requestProphecy->reveal(),
            $responseProphecy->reveal(),
            []
        );

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }
}