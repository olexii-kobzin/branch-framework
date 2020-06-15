<?php
declare(strict_types=1);

use Branch\Events\Event;
use Branch\Events\ListenerProvider;
use Branch\Interfaces\Events\EventInterface;
use Branch\Tests\BaseTestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class ListenerProviderTest extends BaseTestCase
{
    use ProphecyTrait;

    protected const COUNTER_DEFAULT = PHP_INT_MAX;

    protected ListenerProvider $provider;

    protected $eventProphecy;

    public function setUp(): void
    {
        $this->eventProphecy = $this->prophesize(EventInterface::class);

        $this->provider = new ListenerProvider();
    }

    public function testListenersAreEmptyAfterCreation(): void
    {
        $listenersRefleciton = $this->getPropertyReflection($this->provider, 'listeners');

        $this->assertEmpty($listenersRefleciton->getValue($this->provider));
    }

    public function testListenerLookupIsEmptyAfterCreation(): void
    {
        $lookupReflection = $this->getPropertyReflection($this->provider, 'listenerLookup');

        $this->assertEmpty($lookupReflection->getValue($this->provider));
    }

    public function testCanAddListener(): void
    {
        $listenersReflection = $this->getPropertyReflection($this->provider, 'listeners');
        $lookupReflection = $this->getPropertyReflection($this->provider, 'listenerLookup');

        $this->provider->addListener('test', fn(EventInterface $event) => 'test-1');
        $this->provider->addListener('test', fn(EventInterface $event) => 'test-2');
        $this->provider->addListener('test2', fn(EventInterface $event) => 'test2-1');

        $liseners = $listenersReflection->getValue($this->provider);
        $lookup = $lookupReflection->getValue($this->provider);

        $this->assertNotEmpty($liseners['']['test']);
        $this->assertNotEmpty($liseners['']['test2']);

        $this->assertNotEmpty($lookup['']['test']);
        $this->assertNotEmpty($lookup['']['test2']);

        $this->assertCount(2, $liseners['']['test']);
        $this->assertCount(1, $liseners['']['test2']);

        $this->assertCount(2, $lookup['']['test']);
        $this->assertCount(1, $lookup['']['test2']);

        $counter = 0;
        $priorities = [
            '0-' . self::COUNTER_DEFAULT, 
            '0-' . (self::COUNTER_DEFAULT - 1),
        ];
        $listenersOrder = ['test-1', 'test-2'];
        foreach ($liseners['']['test'] as $hash => $listener) {
            $this->assertInstanceOf(\Closure::class, $listener);
            $this->assertSame($listenersOrder[$counter], $listener($this->eventProphecy->reveal()));
            $this->assertSame($hash, $lookup['']['test'][$priorities[$counter]]);

            $counter++;
        }

        $counter2 = 0;
        $priorities2 = ['0-' . self::COUNTER_DEFAULT];
        foreach ($liseners['']['test2'] as $hash => $listener) {
            $this->assertInstanceOf(\Closure::class, $listener);
            $this->assertSame($hash, $lookup['']['test2'][$priorities2[$counter2]]);

            $counter2++;
        }
    }

    public function testCanAddListenerWithTarget(): void
    {
        $target = new \stdClass();

        $listenersReflection = $this->getPropertyReflection($this->provider, 'listeners');
        $lookupReflection = $this->getPropertyReflection($this->provider, 'listenerLookup');

        $this->provider->addListener('test', fn(EventInterface $event) => 'test-1', $target);
        $this->provider->addListener('test', fn(EventInterface $event) => 'test-2', $target);

        $liseners = $listenersReflection->getValue($this->provider);
        $lookup = $lookupReflection->getValue($this->provider);

        $this->assertNotEmpty($liseners[spl_object_hash($target)]['test']);
        $this->assertNotEmpty($lookup[spl_object_hash($target)]['test']);

        $this->assertCount(2, $liseners[spl_object_hash($target)]['test']);
        $this->assertCount(2, $lookup[spl_object_hash($target)]['test']);

        $counter = 0;
        $priorities = [
            '0-' . self::COUNTER_DEFAULT, 
            '0-' . (self::COUNTER_DEFAULT - 1),
        ];
        $listenersOrder = ['test-1', 'test-2'];
        foreach ($liseners[spl_object_hash($target)]['test'] as $hash => $listener) {
            $this->assertInstanceOf(\Closure::class, $listener);
            $this->assertSame($listenersOrder[$counter], $listener($this->eventProphecy->reveal()));
            $this->assertSame($hash, $lookup[spl_object_hash($target)]['test'][$priorities[$counter]]);

            $counter++;
        }
    }

    public function testCanAddListenerWithPriority(): void
    {
        $listenersReflection = $this->getPropertyReflection($this->provider, 'listeners');
        $lookupReflection = $this->getPropertyReflection($this->provider, 'listenerLookup');

        $this->provider->addListener('test', fn(EventInterface $event) => 'test-1', null, 90);
        $this->provider->addListener('test', fn(EventInterface $event) => 'test-2', null, 0);
        $this->provider->addListener('test', fn(EventInterface $event) => 'test-3', null, 90);
        $this->provider->addListener('test', fn(EventInterface $event) => 'test-4', null, 30);
        $this->provider->addListener('test', fn(EventInterface $event) => 'test-5', null, 0);
        $this->provider->addListener('test', fn(EventInterface $event) => 'test-6', null, 110);
        $this->provider->addListener('test', fn(EventInterface $event) => 'test-7', null, 90);

        $liseners = $listenersReflection->getValue($this->provider);
        $lookup = $lookupReflection->getValue($this->provider);

        $this->assertNotEmpty($liseners['']['test']);
        $this->assertNotEmpty($lookup['']['test']);

        $this->assertCount(7, $liseners['']['test']);
        $this->assertCount(7, $lookup['']['test']);

        $counter = 0;
        $priorities = [
            ['110-' . self::COUNTER_DEFAULT, 'test-6'],
            ['90-' . self::COUNTER_DEFAULT, 'test-1'],
            ['90-' . (self::COUNTER_DEFAULT - 1), 'test-3'],
            ['90-' . (self::COUNTER_DEFAULT - 2), 'test-7'],
            ['30-' . self::COUNTER_DEFAULT, 'test-4'],
            ['0-' . self::COUNTER_DEFAULT, 'test-2'],
            ['0-' . (self::COUNTER_DEFAULT - 1), 'test-5'],
        ];
        foreach ($lookup['']['test'] as $prioritiy => $hash) {
            $this->assertSame($priorities[$counter][0], $prioritiy);
            $this->assertSame(
                $priorities[$counter][1], 
                $liseners['']['test'][$hash]($this->eventProphecy->reveal())
            );

            $counter++;
        }
    }

    public function testExceptionIsThrownIfAddingListenerWithWrongPriority(): void
    {
        $this->expectException(\LogicException::class);

        $this->provider->addListener('test', fn(EventInterface $event) => null, null, -1);
    }

    public function testReturnEmptyArrayIfListenersNotFound(): void
    {
        $this->eventProphecy->getName()
            ->willReturn('test')
            ->shouldBeCalledTimes(1);
        $this->eventProphecy->getTarget()
            ->willReturn(null)
            ->shouldBeCalledTimes(1);
            
        $listeners = [];

        foreach ($this->provider->getListenersForEvent($this->eventProphecy->reveal()) as $listener) {
            $listeners[] = $listener;
        }

        $this->assertEmpty($listeners);
    }

    public function testGenListenrsForEvent(): void
    {
        $this->eventProphecy->getName()
            ->willReturn('test')
            ->shouldBeCalledTimes(1);
        $this->eventProphecy->getTarget()
            ->willReturn(null)
            ->shouldBeCalledTimes(1);

        $this->provider->addListener('test', fn(EventInterface $event) => null);
        $this->provider->addListener('test', fn(EventInterface $event) => null);

        $listeners = [];
        
        foreach ($this->provider->getListenersForEvent($this->eventProphecy->reveal()) as $listener) {
            $this->assertInstanceOf(\Closure::class, $listener);
            $listeners[] = $listener;
        }

        $this->assertCount(2, $listeners);
    }

    public function testGetListenersForEventWithTarget(): void
    {
        $target = new \stdClass();

        $this->eventProphecy->getName()
            ->willReturn('test')
            ->shouldBeCalledTimes(2);
        $this->eventProphecy->getTarget()
            ->willReturn($target, $target, null)
            ->shouldBeCalledTimes(3);

        $this->provider->addListener('test', fn(EventInterface $event) => null, $target);
        $this->provider->addListener('test', fn(EventInterface $event) => null, $target);

        $listeners = [];

        foreach ($this->provider->getListenersForEvent($this->eventProphecy->reveal()) as $listener) {
            $this->assertInstanceOf(\Closure::class, $listener);
            $listeners[] = $listener;
        }

        $this->assertCount(2, $listeners);

        $noTargetListeners = [];

        foreach ($this->provider->getListenersForEvent($this->eventProphecy->reveal()) as $listener) {
            $this->assertInstanceOf(\Closure::class, $listener);
            $noTargetListeners[] = $listener;
        }

        $this->assertEmpty($noTargetListeners);
    }
}