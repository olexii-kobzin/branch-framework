<?php
declare(strict_types=1);

use Branch\Container\Invoker;
use Branch\Interfaces\Container\ResolverInterface;
use Branch\Tests\BaseTestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

class InvokerTest extends BaseTestCase
{
    use ProphecyTrait;

    protected Invoker $invoker;

    protected $resolverProphecy;

    public function setUp(): void
    {
        $this->resolverProphecy = $this->prophesize(ResolverInterface::class);

        $this->invoker = new Invoker($this->resolverProphecy->reveal());
    }

    public function testObjectIsInvoked(): void
    {
        $object = new class {
            public function __invoke(): string
            {
                return 'test value';
            }
        };

        $this->resolverProphecy->resolveArgs(
            Argument::type('array'),
            Argument::type('array')
        )
            ->willReturn([])
            ->shouldBeCalledTimes(1);

        $result = $this->invoker->invoke($object);

        $this->assertEquals('test value', $result);
    }

    public function testObjctWithArgsIsInvoked(): void
    {
        $object = new class {
            public function __invoke(string $name, string $age): string
            {
                return "$name: $age";
            }
        };

        $this->resolverProphecy->resolveArgs(
            Argument::size(2),
            Argument::type('array')
        )
            ->willReturn(['User', 50])
            ->shouldBeCalledTimes(1);

        $result = $this->invoker->invoke($object, [
            'name' => 'User',
            'age' => 50,
        ]);

        $this->assertEquals('User: 50', $result);
    }

    public function testClosureIsInvoked(): void
    {
        $closure = fn(): string => 'test value';

        $this->resolverProphecy->resolveArgs(
            Argument::type('array'),
            Argument::type('array')
        )
            ->willReturn([])
            ->shouldBeCalledTimes(1);

        $result = $this->invoker->invoke($closure);

        $this->assertEquals('test value', $result);
    }

    public function testClosureWithArgsIsInvoked(): void
    {
        $closure = fn(string $user, string $age): string => "$user: $age";

        $this->resolverProphecy->resolveArgs(
            Argument::size(2),
            Argument::type('array')
        )
            ->willReturn(['User', 50])
            ->shouldBeCalledTimes(1);

        $result = $this->invoker->invoke($closure);

        $this->assertEquals('User: 50', $result);
    }

    public function testClosureIsResolved(): void
    {
        $callableResolverReflection = $this->getMethodReflection($this->invoker, 'resolveCallable');

        $this->resolverProphecy->resolve()->shouldNotBeCalled();

        $closure = fn() => 'test value';

        [$object, $reflection] = $callableResolverReflection->invokeArgs($this->invoker, [$closure]);

        $this->assertNull($object);
        $this->assertInstanceOf(\ReflectionFunction::class, $reflection);
        $this->assertTrue($reflection->isClosure());
    }

    public function testCallableObjectIsResolved(): void
    {
        $callableResolverReflection = $this->getMethodReflection($this->invoker, 'resolveCallable');

        $this->resolverProphecy->resolve()->shouldNotBeCalled();

        $callableObject = new class {
            public function __invoke(): string
            {
                return 'test value';
            }
        };

        [$object, $reflection] = $callableResolverReflection->invokeArgs($this->invoker, [$callableObject]);

        $this->assertEquals($callableObject, $object);
        $this->assertInstanceOf(\ReflectionMethod::class, $reflection);
        $this->assertEquals('__invoke', $reflection->getName());
    }

    public function testArrayIsResolved(): void
    {
        $callableResolverReflection = $this->getMethodReflection($this->invoker, 'resolveCallable');

        $this->resolverProphecy->resolve(Argument::exact(self::class))
            ->willReturn($this)
            ->shouldBeCalledTimes(1);

        [$object, $reflection] = $callableResolverReflection->invokeArgs($this->invoker, [
            [self::class, 'testArrayIsResolved']
        ]);

        $this->assertEquals($this, $object);
        $this->assertInstanceOf(\ReflectionMethod::class, $reflection);
        $this->assertEquals('testArrayIsResolved', $reflection->getName());
    }

    public function testExceptionIfCallableNotResolved(): void
    {
        $callableResolverReflection = $this->getMethodReflection($this->invoker, 'resolveCallable');

        $this->resolverProphecy->resolve()->shouldNotBeCalled();

        $this->expectException(\LogicException::class);

        $callableResolverReflection->invokeArgs($this->invoker, ['is_array']);
    }
}