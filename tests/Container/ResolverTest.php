<?php
declare(strict_types=1);

use Branch\App;
use Branch\Container\Resolver;
use Branch\Interfaces\Container\ContainerInterface;
use Branch\Interfaces\Container\DefinitionInfoInterface;
use Branch\Tests\BaseTestCase;
use Branch\Tests\Mocks\Constructor\WithDependencies;
use Branch\Tests\Mocks\Constructor\WithoutConstructor;
use Branch\Tests\Mocks\Constructor\WithParams;
use Branch\Tests\Mocks\Constructor\WithParamsNoType;
use Branch\Tests\Mocks\Constructor\WithParamsNoTypeDefault;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

class ResolverTest extends BaseTestCase
{
    use ProphecyTrait;

    protected Resolver $resolver;

    protected $appProphecy;

    protected $definitionInfoProphecy;

    public function setUp(): void
    {
        $this->appProphecy = $this->prophesize(App::class)->willImplement(ContainerInterface::class);
        $this->definitionInfoProphecy = $this->prophesize(DefinitionInfoInterface::class);

        $this->resolver = new Resolver(
            $this->appProphecy->reveal(),
            $this->definitionInfoProphecy->reveal()
        );
    
    }

    public function testClosureResolved(): void
    {
        $this->definitionInfoProphecy->isClosure(Argument::type(\Closure::class))
            ->willReturn(true)
            ->shouldBeCalledTimes(1);
        $this->definitionInfoProphecy->isArrayClass()
            ->shouldNotBeCalled();
        $this->definitionInfoProphecy->isClass()
            ->shouldNotBeCalled();

        $definition = fn(ContainerInterface $container): string => 'test string';

        $result = $this->resolver->resolve($definition);

        $this->assertSame('test string', $result);
    }

    public function testClassWithDependenciesResolved(): void
    {
        $this->definitionInfoProphecy->isClosure(Argument::exact(WithDependencies::class))
            ->willReturn(false)
            ->shouldBeCalledTimes(1);
        $this->definitionInfoProphecy->isArrayClass(Argument::exact(WithDependencies::class))
            ->willReturn(false)
            ->shouldBeCalledTimes(1);
        $this->definitionInfoProphecy->isClass(Argument::exact(WithDependencies::class))
            ->willReturn(true)
            ->shouldBeCalledTimes(1);
        $this->appProphecy->has(Argument::exact(WithoutConstructor::class))
            ->willReturn(true)
            ->shouldBeCalledTimes(1);
        $this->appProphecy->get(Argument::exact(WithoutConstructor::class))
            ->willReturn(new WithoutConstructor())
            ->shouldBeCalledTimes(1);

        $resolved = $this->resolver->resolve(WithDependencies::class);

        $this->assertInstanceOf(WithDependencies::class, $resolved);
        $this->assertInstanceOf(WithoutConstructor::class, $resolved->dependency);
    }

    public function testArrayClassResolved(): void
    {
        $this->definitionInfoProphecy->isClosure(Argument::any())
            ->willReturn(false)
            ->shouldBeCalledTimes(1);
        $this->definitionInfoProphecy->isArrayClass(Argument::type('array'))
            ->willReturn(true)
            ->shouldBeCalledTimes(1);
        $this->definitionInfoProphecy->isClass()
            ->shouldNotBeCalled();
        $this->appProphecy->has()
            ->shouldNotBeCalled();
        $this->appProphecy->get()
            ->shouldNotBeCalled();

        $definition = ['definition' => WithoutConstructor::class];

        $result = $this->resolver->resolve($definition);

        $this->assertInstanceOf(WithoutConstructor::class, $result);
    }

    public function testClassResolved(): void
    {
        $this->definitionInfoProphecy->isClosure(Argument::any())
            ->willReturn(false)
            ->shouldBeCalledTimes(1);
        $this->definitionInfoProphecy->isArrayClass(Argument::any())
            ->willReturn(false)
            ->shouldBeCalledTimes(1);
        $this->definitionInfoProphecy->isClass(Argument::type('string'))
            ->willReturn(true)
            ->shouldBeCalledTimes(1);
        $this->appProphecy->has()
            ->shouldNotBeCalled();
        $this->appProphecy->get()
            ->shouldNotBeCalled();

        $definition = WithoutConstructor::class;

        $result = $this->resolver->resolve($definition);

        $this->assertInstanceOf(WithoutConstructor::class, $result);
    }

    public function testOtherDefinitionsResolved(): void
    {
        $this->definitionInfoProphecy->isClosure(Argument::any())
            ->willReturn(false)
            ->shouldBeCalledTimes(3);
        $this->definitionInfoProphecy->isArrayClass(Argument::any())
            ->willReturn(false)
            ->shouldBeCalledTimes(3);
        $this->definitionInfoProphecy->isClass(Argument::any())
            ->willReturn(false)
            ->shouldBeCalledTimes(3);

        $stringDefinition = 'test string';
        $intDefinition = 3;
        $arrayDefinition = ['test'];

        $stringResult = $this->resolver->resolve($stringDefinition);
        $intResult = $this->resolver->resolve($intDefinition);
        $arrayResult = $this->resolver->resolve($arrayDefinition);

        $this->assertSame('test string', $stringResult);
        $this->assertSame(3, $intResult);
        $this->assertSame(['test'], $arrayResult);
    }

    public function testArgsResolved(): void
    {
        $this->appProphecy->has(Argument::exact('string'))
            ->willReturn(true)
            ->shouldBeCalledTimes(1);
        $this->appProphecy->has(Argument::exact('int'))
            ->willReturn(false)
            ->shouldBeCalledTimes(1);
        $this->appProphecy->get(Argument::exact('string'))
            ->willReturn('test')
            ->shouldBeCalledTimes(1);
        $this->appProphecy->get(Argument::exact('int'))
            ->shouldNotBeCalled();
        
        $reflection = new \ReflectionClass(WithParams::class);
        $constructor = $reflection->getConstructor();
        $parameters = $constructor->getParameters();

        $arguments = $this->resolver->resolveArgs($parameters);

        $this->assertSame('test', $arguments[0]);
    }

    public function testArgsResolvedWithPredefined(): void
    {
        $this->appProphecy->has(Argument::exact('string'))
            ->willReturn(true)
            ->shouldBeCalledTimes(1);
        $this->appProphecy->has(Argument::exact('int'))
            ->shouldNotBeCalled();
        $this->appProphecy->get(Argument::exact('string'))
            ->willReturn('test')
            ->shouldBeCalledTimes(1);
        $this->appProphecy->get(Argument::exact('int'))
            ->shouldNotBeCalled();
        $this->definitionInfoProphecy->isClass(Argument::exact(11))
            ->willReturn(false)
            ->shouldBeCalledTimes(1);
        
        $reflection = new \ReflectionClass(WithParams::class);
        $constructor = $reflection->getConstructor();
        $parameters = $constructor->getParameters();

        $arguments = $this->resolver->resolveArgs($parameters, ['int' => 11]);

        $this->assertSame('test', $arguments[0]);
        $this->assertSame(11, $arguments[1]);
    }

    public function testArgsRrsolvedWithDefaults(): void
    {
        $this->appProphecy->has(Argument::any())
            ->shouldNotBeCalled();
        $this->appProphecy->get(Argument::any())
            ->shouldNotBeCalled();
        
        $reflection = new \ReflectionClass(WithParamsNoTypeDefault::class);
        $constructor = $reflection->getConstructor();
        $parameters = $constructor->getParameters();

        $arguments = $this->resolver->resolveArgs($parameters);

        $this->assertSame([], $arguments);
    }

    public function testErrorIsThrownIfTypeIsNotAvailable(): void
    {
        $this->appProphecy->has(Argument::any())
            ->shouldNotBeCalled();
        $this->appProphecy->get(Argument::any())
            ->shouldNotBeCalled();
        
        $reflection = new \ReflectionClass(WithParamsNoType::class);
        $constructor = $reflection->getConstructor();
        $parameters = $constructor->getParameters();

        $this->expectException(\LogicException::class);
        $this->resolver->resolveArgs($parameters);
    }
}