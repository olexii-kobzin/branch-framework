<?php
declare(strict_types=1);

use Branch\Events\Event;
use Branch\Events\EventDispatcher;
use Branch\Interfaces\Events\EventInterface;
use Branch\Interfaces\Events\ListenerProviderInterface;
use Branch\Tests\BaseTestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

class EventDispatcherTest extends BaseTestCase
{
    use ProphecyTrait;

    protected EventDispatcher $dispatcher;

    protected $providerProphecy;

    protected $eventProphecy;

    public function setUp(): void
    {
        $this->eventProphecy = $this->prophesize(EventInterface::class);
        $this->providerProphecy = $this->prophesize(ListenerProviderInterface::class);

        $this->eventProphecy->getName()->willReturn('test');
        $this->eventProphecy->getTarget()->willReturn(null);
        $this->eventProphecy->getPayload()->willReturn(null);
        $this->eventProphecy->isPropagationStopped()->willReturn(false);

        $this->dispatcher = new EventDispatcher($this->providerProphecy->reveal());
    }

    public function testAnEventReturnedAfterDispatch(): void
    {
        $this->providerProphecy->getListenersForEvent(Argument::type(EventInterface::class))
            ->willReturn([])
            ->shouldBeCalledTimes(1);

        $event = $this->dispatcher->dispatch($this->eventProphecy->reveal());

        $this->assertSame($event, $this->eventProphecy->reveal());
    }

    public function testListenersAreLoopedAndCalled(): void
    {
        $listenerMock = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['test'])
            ->getMock();

        $listenerMock->expects($this->exactly(2))
            ->method('test')
            ->with($this->isInstanceOf(EventInterface::class));

        $this->providerProphecy->getListenersForEvent(Argument::type(EventInterface::class))
            ->willReturn([
                [$listenerMock, 'test'],
                [$listenerMock, 'test']
            ]);

        $event = $this->dispatcher->dispatch($this->eventProphecy->reveal());

        $this->assertSame($event, $this->eventProphecy->reveal());
    }

    public function testEventPropagationIsStopped(): void
    {
        $eventPass1 = false;
        $eventPass2 = false;

        $this->eventProphecy->stopPropagation()
            ->will(
                function() { $this->isPropagationStopped()->willReturn(true); }
        );
        $this->providerProphecy->getListenersForEvent(Argument::type(EventInterface::class))
            ->willReturn([
                function (EventInterface $event) use (&$eventPass1) {
                    $event->stopPropagation();
                    $eventPass1 = !$eventPass1;
                },
                function (EventInterface $event) use (&$eventPass2) {
                    $eventPass2 = !$eventPass2;
                }
            ]);

        $event = $this->dispatcher->dispatch($this->eventProphecy->reveal());

        $this->assertSame($event, $this->eventProphecy->reveal());
        $this->assertTrue($eventPass1);
        $this->assertFalse($eventPass2);
    }
}