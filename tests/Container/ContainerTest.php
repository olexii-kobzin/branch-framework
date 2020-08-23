<?php
declare(strict_types=1);

use Branch\Container\Container;
use Branch\Interfaces\Container\ContainerInterface;
use Branch\Interfaces\Container\DefinitionInfoInterface;
use Branch\Interfaces\Container\InvokerInterface;
use Branch\Interfaces\Container\ResolverInterface;
use Branch\Tests\BaseTestCase;
use Branch\Tests\Mocks\Constructor\WithoutConstructor;
use Branch\Tests\Mocks\Constructor\WithParams;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

class ContainerTest extends BaseTestCase
{
    use ProphecyTrait;

    protected Container $container;

    protected $definitionInfoProphecy;

    protected $resolverProphecy;

    protected $invokerProphecy;

    public function setUp(): void
    {
        $this->definitionInfoProphecy = $this->prophesize(DefinitionInfoInterface::class);
        $this->resolverProphecy = $this->prophesize(ResolverInterface::class);
        $this->invokerProphecy = $this->prophesize(InvokerInterface::class);

        $this->container = new Container();

        $definitionInfoReflection = $this->getPropertyReflection($this->container, 'definitionInfo');
        $definitionInfoReflection->setValue($this->container, $this->definitionInfoProphecy->reveal());
        $resolverReflection = $this->getPropertyReflection($this->container, 'resolver');
        $resolverReflection->setValue($this->container, $this->resolverProphecy->reveal());
        $invokerReflection = $this->getPropertyReflection($this->container, 'invoker');
        $invokerReflection->setValue($this->container, $this->invokerProphecy->reveal());
    }

    public function testDefinitionsAreEmptyAfterCreration(): void
    {
        $definitionsReflection = $this->getPropertyReflection($this->container, 'definitions');
        $definitions = $definitionsReflection->getValue($this->container);

        $this->assertEquals(0, $definitions->count());
    }

    public function testEntriesResolvedAreEmptyAfterCreation(): void
    {
        $entriesResolvedReflection = $this->getPropertyReflection($this->container, 'entriesResolved');
        $entriesResolved = $entriesResolvedReflection->getValue($this->container);

        $this->assertEquals(0, $entriesResolved->count());
    }

    public function testEntriesBeingResolvedAreEmptyAfterCreation(): void
    {
        $entriesBeingResolvedReflection = $this->getPropertyReflection($this->container, 'entriesBeingResolved');
        $entriesBeingResolved = $entriesBeingResolvedReflection->getValue($this->container);

        $this->assertEmpty($entriesBeingResolved);
    }

    public function testCanSetDefiniton(): ContainerInterface
    {
        $definitionsReflection = $this->getPropertyReflection($this->container, 'definitions');
        $definitions = $definitionsReflection->getValue($this->container);

        $this->container->set('test', 3);

        $this->assertSame(3, $definitions->get('test'));

        return $this->container;
    }

    /**
     * @depends testCanSetDefiniton
     */
    public function testCanRepleceDefinition(ContainerInterface $container): void
    {
        $definitionsReflection = $this->getPropertyReflection($container, 'definitions');
        $definitions = $definitionsReflection->getValue($container);

        $container->set('test', 4, true);

        $this->assertSame(4, $definitions->get('test'));
    }

    public function testExcepciontIsThrownOnSettingExistingDefinition(): void
    {
        $this->container->set('test', 3);

        $this->expectException(\OutOfRangeException::class);

        $this->container->set('test', 4);
    }

    public function testNoExceptionIfSetEmptyMultipleDefinitions(): void
    {
        $definitionsReflection = $this->getPropertyReflection($this->container, 'definitions');
        $definitions = $definitionsReflection->getValue($this->container);

        $this->container->setMultiple([]);

        $this->assertEquals(0, $definitions->count());
    }

    public function testCanSetMultipleDefinitions(): ContainerInterface
    {
        $definitionsReflection = $this->getPropertyReflection($this->container, 'definitions');
        $definitions = $definitionsReflection->getValue($this->container);

        $definitionsToSet = [
            'hello' => 3,
            'world' => 4,
        ];

        $this->container->setMultiple($definitionsToSet);

        $this->assertSame(3, $definitions->get('hello'));
        $this->assertSame(4, $definitions->get('world'));

        return $this->container;
    }

    /**
     * @depends testCanSetMultipleDefinitions
     */
    public function testCanReplaceMultipleDefinitions(ContainerInterface $container): void
    {
        $definitionsReflection = $this->getPropertyReflection($container, 'definitions');
        $definitions = $definitionsReflection->getValue($container);

        $definitionsToSet = [
            'hello' => ['hello'],
            'world' => ['world'],
        ];

        $container->setMultiple($definitionsToSet, true);

        $this->assertSame(['hello'], $definitions->get('hello'));
        $this->assertSame(['world'], $definitions->get('world'));
    }

    public function testCanGetResolvedEntry(): void
    {
        $this->definitionInfoProphecy->isTransient(Argument::exact(3))
            ->willReturn(false)
            ->shouldBeCalledTimes(1);
        $this->definitionInfoProphecy->isClass(Argument::exact(3))
            ->willReturn(false)
            ->shouldBeCalledTimes(1);
        $this->definitionInfoProphecy->isClosure(Argument::exact(3))
            ->willReturn(false)
            ->shouldBeCalledTimes(1);
        $this->definitionInfoProphecy->isInstance(Argument::exact(3))
            ->willReturn(false)
            ->shouldBeCalledTimes(1);
        $this->definitionInfoProphecy->isResolvableArray(Argument::exact(3))
            ->willReturn(false)
            ->shouldBeCalledTimes(1);
        $this->resolverProphecy->resolve(Argument::any())
            ->shouldNotBeCalled();

        $this->container->set('test', 3);

        $this->assertSame(3, $this->container->get('test')); // Resolving an entry first time
        $this->assertSame(3, $this->container->get('test'));
    }

    public function testCanGetResolvableResolvedEntry(): void
    {
        $this->definitionInfoProphecy->isTransient(Argument::type(\Closure::class))
            ->willReturn(false)
            ->shouldBeCalledTimes(1);
        $this->definitionInfoProphecy->isClass(Argument::type(\Closure::class))
            ->willReturn(false)
            ->shouldBeCalledTimes(1);
        $this->definitionInfoProphecy->isClosure(Argument::type(\Closure::class))
            ->willReturn(true)
            ->shouldBeCalledTimes(1);
        $this->definitionInfoProphecy->isInstance(Argument::type(\Closure::class))
            ->shouldNotBeCalled();
        $this->definitionInfoProphecy->isResolvableArray(Argument::type(\Closure::class))
            ->shouldNotBeCalled();
        $this->resolverProphecy->resolve(Argument::type(\Closure::class))
            ->willReturn('test')
            ->shouldBeCalledTimes(1);

        $this->container->set('test', fn(ContainerInterface $container): string => 'test');

        $this->assertEquals('test', $this->container->get('test'));
    }

    public function testCanGetTransientResolvedEntry(): void
    {
        $this->definitionInfoProphecy->isTransient(Argument::type('array'))
            ->willReturn(true)
            ->shouldBeCalledTimes(1);
        $this->definitionInfoProphecy->isClass(Argument::type('array'))
            ->willReturn(false)
            ->shouldBeCalledTimes(1);
        $this->definitionInfoProphecy->isClosure(Argument::type('array'))
            ->willReturn(false)
            ->shouldBeCalledTimes(1);
        $this->definitionInfoProphecy->isInstance(Argument::type('array'))
            ->willReturn(false)
            ->shouldBeCalledTimes(1);
        $this->definitionInfoProphecy->isResolvableArray(Argument::type('array'))
            ->willReturn(true)
            ->shouldBeCalledTimes(1);
        $this->resolverProphecy->resolve(Argument::type('array'))
            ->willReturn(new WithoutConstructor())
            ->shouldBeCalledTimes(1);

        $entriesResolvedReflection = $this->getPropertyReflection($this->container, 'entriesResolved');
        $entriesResolved = $entriesResolvedReflection->getValue($this->container);

        $this->container->set('test', [
            'definition' => WithoutConstructor::class,
            'singleton' => false,
        ]);

        $this->assertInstanceOf(WithoutConstructor::class, $this->container->get('test'));
        $this->assertEquals(0, $entriesResolved->count());
    }

    public function testCanForceNotResolveDefinition(): void
    {
        $this->definitionInfoProphecy->isTransient(Argument::any())
            ->shouldNotBeCalled();
        $this->definitionInfoProphecy->isClass(Argument::any())
            ->shouldNotBeCalled();
        $this->definitionInfoProphecy->isClosure(Argument::any())
            ->shouldNotBeCalled();
        $this->definitionInfoProphecy->isInstance(Argument::any())
            ->shouldNotBeCalled();
        $this->definitionInfoProphecy->isResolvableArray(Argument::any())
            ->shouldNotBeCalled();
        $this->resolverProphecy->resolve(Argument::any())
            ->shouldNotBeCalled();

        $entriesResolvedReflection = $this->getPropertyReflection($this->container, 'entriesResolved');
        $entriesResolved = $entriesResolvedReflection->getValue($this->container);

        $this->container->set('test', WithoutConstructor::class);

        $this->assertEquals(WithoutConstructor::class, $this->container->get('test', false));
        $this->assertEquals(0, $entriesResolved->count());
    }

    public function testExceptionIsThrownIfIdDefinitionNotFound(): void
    {
        $this->expectException(\OutOfRangeException::class);

        $this->container->get('test');
    }

    public function testCanFindDefinition(): void
    {
        $this->container->set('test', 3);

        $this->assertTrue($this->container->has('test'));
    }

    public function testCanFindResolvedEntry(): void
    {
        $this->definitionInfoProphecy->isTransient(Argument::exact(3))
            ->willReturn(false)
            ->shouldBeCalledTimes(1);
        $this->definitionInfoProphecy->isClass(Argument::exact(3))
            ->willReturn(false)
            ->shouldBeCalledTimes(1);
        $this->definitionInfoProphecy->isClosure(Argument::exact(3))
            ->willReturn(false)
            ->shouldBeCalledTimes(1);
        $this->definitionInfoProphecy->isInstance(Argument::exact(3))
            ->willReturn(false)
            ->shouldBeCalledTimes(1);
        $this->definitionInfoProphecy->isResolvableArray(Argument::exact(3))
            ->willReturn(false)
            ->shouldBeCalledTimes(1);
        $this->resolverProphecy->resolve(Argument::exact(3))
            ->shouldNotBeCalled();

        $this->container->set('test', 3);
        $this->container->get('test');

        $this->assertTrue($this->container->has('test'));
    }

    public function testMakeInstance(): void
    {
        $this->resolverProphecy->resolve(Argument::type('array'))
            ->willReturn(new WithoutConstructor())
            ->shouldBeCalledTimes(1);

        $result = $this->container->make(WithoutConstructor::class);

        $this->assertInstanceOf(WithoutConstructor::class, $result);
    }

    public function testMakeInstanceWithArgs(): void
    {
        $this->resolverProphecy->resolve(Argument::that(
            fn(array $argument): bool => 
            !empty($argument['definition'])
            && $argument['definition'] === WithParams::class
            && !empty($argument['args']['string'])
            && $argument['args']['string'] === 'test'
        ))
            ->willReturn(new WithParams('test'))
            ->shouldBeCalledTimes(1);

        $result = $this->container->make(WithParams::class, ['string' => 'test']);

        $this->assertInstanceOf(WithParams::class, $result);
        $this->assertSame('test', $result->string);
    }

    public function testInvokeCallable(): void
    {
        $this->invokerProphecy->invoke(
            Argument::type(\Closure::class),
            Argument::size(1)
        )
            ->willReturn(3)
            ->shouldBeCalledTimes(1);

        $result = $this->container->invoke(
            fn(int $int) => null,
            [3]
        );

        $this->assertSame(3, $result);
    }

    public function testResolveDefinitionCircularDependency(): void
    {
        $container = $this->container;
        $this->container->set(WithoutConstructor::class, new WithoutConstructor());

        $this->definitionInfoProphecy->isTransient(Argument::type(WithoutConstructor::class))
            ->shouldNotBeCalled();
        $this->definitionInfoProphecy->isClass(Argument::type(WithoutConstructor::class))
            ->willReturn(false)
            ->shouldBeCalledTimes(1);
        $this->definitionInfoProphecy->isClosure(Argument::type(WithoutConstructor::class))
            ->willReturn(false)
            ->shouldBeCalledTimes(1);
        $this->definitionInfoProphecy->isInstance(Argument::type(WithoutConstructor::class))
            ->willReturn(true)
            ->shouldBeCalledTimes(1);
        $this->definitionInfoProphecy->isResolvableArray(Argument::type(WithoutConstructor::class))
            ->shouldNotBeCalled();
        $this->resolverProphecy->resolve(Argument::type(WithoutConstructor::class))
            ->will(function() use ($container) {
                $container->get(WithoutConstructor::class);
            })
            ->shouldBeCalledTimes(1);

        $resolveDefinitionReflection = $this->getMethodReflection($this->container, 'resolveDefinition');

        $this->expectException(\Exception::class);

        $resolveDefinitionReflection->invokeArgs($this->container, [
            WithoutConstructor::class,
            new WithoutConstructor()
        ]);
    }
}