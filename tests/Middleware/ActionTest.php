<?php
declare(strict_types=1);

use Branch\Interfaces\Middleware\ActionInterface;
use Branch\Middleware\Action;
use Branch\Tests\BaseTestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ActionTest extends BaseTestCase
{
    use ProphecyTrait;

    protected Action $action;

    public function setUp(): void
    {
        $responseProphecy = $this->prophesize(ResponseInterface::class);
        
        $this->action = new class($responseProphecy->reveal()) extends Action {
            public function run(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
            {
                return $response;
            }
        };
    }

    public function testArgsAreEmptyAfterCreation(): void
    {
        $argsReflection = $this->getPropertyReflection($this->action, 'args');

        $this->assertCount(0, $argsReflection->getValue($this->action));
    }

    public function testArgsCanBeSet(): void
    {
        $argsReflection = $this->getPropertyReflection($this->action, 'args');

        $this->action->setArgs([
            'arg1' => 'value1',
            'arg2' => 'value2',
        ]);

        $this->assertCount(2, $argsReflection->getValue($this->action));
    }

    public function testHandlerReturnsResponse(): void
    {
        $requestProphecy = $this->prophesize(ServerRequestInterface::class);

        $response = $this->action->handle($requestProphecy->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }
}