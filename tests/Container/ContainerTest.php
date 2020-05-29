<?php
declare(strict_types=1);

use Adbar\Dot;
use Branch\Interfaces\Container\DefinitionInfoInterface;
use Branch\Interfaces\Container\InvokerInterface;
use Branch\Interfaces\Container\ResolverInterface;
use Branch\Tests\BaseTestCase;
use Branch\Tests\Mocks\Container\TestContainer;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

class ContainerTest extends BaseTestCase
{
    use ProphecyTrait;

    protected TestContainer $container;

    protected $definitionInfoProphecy;

    protected $resolverProphecy;

    protected $invokerProphecy;

    public function setUp(): void
    {
        $this->definitionInfoProphecy = $this->prophesize(DefinitionInfoInterface::class);
        $this->resolverProphecy = $this->prophesize(ResolverInterface::class);
        $this->invokerProphecy = $this->prophesize(InvokerInterface::class);

        $this->container = new TestContainer();
        $this->container->setDefiniionInfo($this->definitionInfoProphecy->reveal());
        $this->container->setResolver($this->resolverProphecy->reveal());
        $this->container->setInvoker($this->invokerProphecy->reveal());
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

    public function testCanSetDefiniton(): array
    {
        $definitionsReflection = $this->getPropertyReflection($this->container, 'definitions');
        $definitions = $definitionsReflection->getValue($this->container);

        $this->container->set('test', 3);

        $this->assertSame(3, $definitions->get('test'));

        return [$this->container, $definitions];
    }

    /**
     * @depends testCanSetDefiniton
     */
    public function testCanRepleceDefinition($params): void
    {
        $container = $params[0];
        $definitions = $params[1];

        $container->set('test', 4, true);

        $this->assertSame(4, $definitions->get('test'));
    }

    public function testExcepciontIsThrownOnSettingExistingDefinition(): void
    {
        $this->container->set('test', 3);

        $this->expectException(\OutOfRangeException::class);

        $this->container->set('test', 4);
    }

    public function testGetResolvedEntry(): void
    {
        
    }

    public function testGetDefinition(): void
    {

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
        $this->container->set('test', 3);
        $this->container->get('test');

        $this->assertTrue($this->container->has('test'));
    }

    public function testExceptionIsThrownIfIdNotFound(): void
    {

    }

    public function testCanSetMultipleDefinitions(): void
    {

    }

    public function testNoExceptionIfSetEmptyMultipleDefinitions(): void
    {

    }

    public function testMakeInstance(): void
    {

    }

    public function testMakeInstanceWithArgs(): void
    {

    }

    public function testInvokeCallable(): void
    {

    }

    public function testInvokeCallableWithArgs(): void
    {

    }

    public function testResolveDefinitionCircularDependency(): void
    {

    }
}