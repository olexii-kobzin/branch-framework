<?php
declare(strict_types=1);

use Branch\Events\Event;
use Branch\Tests\BaseTestCase;

class EventTest extends BaseTestCase
{
    public function testСonstructorParamsCanBeSet(): void
    {
        $event = new Event('test', $this, ['test']);

        $targetReflection = $this->getPropertyReflection($event, 'target');
        $nameReflection = $this->getPropertyReflection($event, 'name');
        $payloadReflection = $this->getPropertyReflection($event, 'payload');

        $this->assertSame($this, $targetReflection->getValue($event));
        $this->assertSame('test', $nameReflection->getValue($event));
        $this->assertSame(['test'], $payloadReflection->getValue($event));
    }
 
    public function testTargetCanBeSetAsNull(): void
    {
        $event = new Event('test');

        $targetReflection = $this->getPropertyReflection($event, 'target');

        $this->assertNull($targetReflection->getValue($event));
    }

    public function testStoppedIsEmptyAfterCreation(): void
    {
        $event = new Event('test');

        $stoppedReflection = $this->getPropertyReflection($event, 'stopped');

        $this->assertNull($stoppedReflection->getValue($event));
    }

    public function testCanGetProperties(): void
    {
        $event = new Event('test', $this, ['test']);

        $this->assertSame('test', $event->getName());
        $this->assertSame($this, $event->getTarget());
        $this->assertSame(['test'], $event->getPayload());
        $this->assertNull($event->getStopped());
    }

    public function testCanSetPayload(): void
    {
        $event = new Event('test');

        $this->assertNull($event->getPayload());

        $event->setPayload(['test']);

        $this->assertSame(['test'], $event->getPayload());
    }

    public function testCanStopPropagation(): void
    {
        $event = new Event('test');

        $this->assertNull($event->getStopped());

        $event->stopPropagation();

        $this->assertTrue($event->getStopped());
    }

    public function testCanCheckIsPropagationStopped(): void
    {
        $event = new Event('test');

        $event->stopPropagation();

        $this->assertTrue($event->isPropagationStopped());
    }
}