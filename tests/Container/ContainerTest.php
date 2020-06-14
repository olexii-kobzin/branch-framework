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
        $this->container->setDefiniionInfo($this->definitionInfoProphecy->reveal());
        $this->container->setResolver($this->resolverProphecy->reveal());
        $this->container->setInvoker($this->invokerProphecy->reveal());
    }

    public function testDefinitionInfoIsEmptyAfterCreation(): void
    {
        $container = new Container();

        $definitionInfoReflection = $this->getPropertyReflection($container, 'definitionInfo');

        $this->assertFalse($definitionInfoReflection->isInitialized($container));
    }

    public function testResolverIsEmptyAfterCreation(): void
    {
        $container = new Container();

        $resolverReflection = $this->getPropertyReflection($container, 'resolver');
        
        $this->assertFalse($resolverReflection->isInitialized($container));
    }

    public function testInvokerIsEmptyAfterCreation(): void
    {
        $container = new Container();

        $invokerReflection = $this->getPropertyReflection($container, 'invoker');

        $this->assertFalse($invokerReflection->isInitialized($container));
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
        $this->definitionInfoProphecy->isTransient(Argument::any())
            ->willReturn(false)
            ->shouldBeCalledTimes(1);
        $this->resolverProphecy->resolve(Argument::exact(3))
            ->willReturn(3)
            ->shouldBeCalledTimes(1);

        $this->container->set('test', 3);

        $this->assertSame(3, $this->container->get('test')); // Resolving an entry first time
        $this->assertSame(3, $this->container->get('test'));
    }

    public function testCanGetResolableResolvedEntry(): void
    {
        $this->definitionInfoProphecy->isTransient(Argument::any())
            ->willReturn(false)
            ->shouldBeCalledTimes(1);
        $this->resolverProphecy->resolve(Argument::type(\Closure::class))
            ->willReturn('test')
            ->shouldBeCalledTimes(1);

        $this->container->set('test', fn(ContainerInterface $container): string => 'test');

        $this->assertEquals('test', $this->container->get('test'));
    }

    public function testCanGetTransientResolvedEntry(): void
    {
        $this->definitionInfoProphecy->isTransient(Argument::any())
            ->willReturn(true)
            ->shouldBeCalledTimes(1);
        $this->resolverProphecy->resolve(Argument::type('array'))
            ->willReturn(new WithoutConstructor())
            ->shouldBeCalledTimes(1);

        $entriesResolvedReflection = $this->getPropertyReflection($this->container, 'entriesResolved');
        $entriesResolved = $entriesResolvedReflection->getValue($this->container);

        $this->container->set('test', [
            'class' => WithoutConstructor::class,
            'singleton' => false,
        ]);

        $this->assertInstanceOf(WithoutConstructor::class, $this->container->get('test'));
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
        $this->definitionInfoProphecy->isTransient(Argument::any())
            ->willReturn(false)
            ->shouldBeCalledTimes(1);
        $this->resolverProphecy->resolve(Argument::exact(3))
            ->willReturn(3)
            ->shouldBeCalledTimes(1);

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
            !empty($argument['class'])
            && $argument['class'] === WithParams::class
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

        $this->definitionInfoProphecy->isTransient(Argument::any())
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