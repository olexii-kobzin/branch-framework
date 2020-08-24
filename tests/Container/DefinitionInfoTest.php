<?php
declare(strict_types=1);

use Branch\Container\DefinitionInfo;
use Branch\Tests\BaseTestCase;
use Branch\Tests\Mocks\Constructor\WithoutConstructor;

class DefinitionInfoTest extends BaseTestCase
{
    protected DefinitionInfo $definitionInfo;

    public function setUp(): void
    {
        $this->definitionInfo = new DefinitionInfo();
    }

    public function testIsDefinitionTransient(): void
    {
        $definition = [
            'definition' => self::class,
            'singleton' => false,
        ];

        $this->assertTrue($this->definitionInfo->isTransient($definition));
    }

    public function testDefinitionIsNotTransient(): void
    {
        $definition = [
            'definition' => self::class,
            'singleton' => true,
        ];

        $this->assertFalse($this->definitionInfo->isTransient($definition));
    }

    public function testIsArrayTransient(): void
    {
        $definition = [
            'testKey1' => 'testValue1',
        ];

        $this->assertTrue($this->definitionInfo->isTransient($definition));
    }

    public function testClassIsNotTransient(): void
    {
        $this->assertFalse($this->definitionInfo->isTransient(self::class));
    }

    public function testIsScalarTransient(): void
    {
        $this->assertTrue($this->definitionInfo->isTransient('striing'));
        $this->assertTrue($this->definitionInfo->isTransient(3));
        $this->assertTrue($this->definitionInfo->isTransient(3.14));
        $this->assertTrue($this->definitionInfo->isTransient(true));
        $this->assertTrue($this->definitionInfo->isTransient(null));
        $this->assertFalse($this->definitionInfo->isTransient(self::class));
    }

    public function testIsInstanceTransient(): void
    {
        $this->assertTrue($this->definitionInfo->isTransient(new WithoutConstructor()));
    }

    public function testIsResourceTransient(): void
    {
        $this->assertTrue($this->definitionInfo->isTransient(tmpfile()));
    }
    
    public function testIsClass(): void
    {
        $definition = self::class;

        $this->assertTrue($this->definitionInfo->isClass($definition));
    }

    public function testIsNotClass(): void
    {
        $notStringDefinition = $this;
        $notExistsDefinition = 'ClassDoesNotExists';

        $this->assertFalse($this->definitionInfo->isClass($notStringDefinition));
        $this->assertFalse($this->definitionInfo->isClass($notExistsDefinition));
    }

    public function testIsClassArray(): void
    {
        $definition = [
            'definition' => self::class,
        ];

        $this->assertTrue($this->definitionInfo->isClassArray($definition));
    }

    public function testIsNotClassArray(): void
    {
        $notArrayDefinition = self::class;
        $notStringDefinition = [
            'definition' => $this,
        ];
        $notExistsDefinition = [
            'definition' => 'ClassDoesNotExists',
        ];

        $this->assertFalse($this->definitionInfo->isClassArray($notArrayDefinition));
        $this->assertFalse($this->definitionInfo->isClassArray($notStringDefinition));
        $this->assertFalse($this->definitionInfo->isClassArray($notExistsDefinition));
    }

    public function testIsClosure(): void
    {
        $definition = fn() => null;

        $this->assertTrue($this->definitionInfo->isClosure($definition));
    }

    public function testIsNotClosure(): void
    {
        $notObjectDefinition = 0;
        $notClosureDefinition = $this;

        $this->assertFalse($this->definitionInfo->isClosure($notObjectDefinition));
        $this->assertFalse($this->definitionInfo->isClosure($notClosureDefinition));
    }

    public function testIsClosureArray(): void
    {
        $definition = [
            'definition' => fn() => 'test',
        ];

        $this->assertTrue($this->definitionInfo->isClosureArray($definition));
    }

    public function testIsNotClosureArray(): void
    {
        $notArrayDefinition = self::class;
        $notClosureDefinition = [
            'definition' => $this,
        ];

        $this->assertFalse($this->definitionInfo->isClosureArray($notArrayDefinition));
        $this->assertFalse($this->definitionInfo->isClosureArray($notClosureDefinition));
    }

    public function testIsInstance(): void
    {
        $definition = $this;

        $this->assertTrue($this->definitionInfo->isInstance($definition));
    }

    public function testIsNotInstance(): void
    {
        $definition = self::class;

        $this->assertFalse($this->definitionInfo->isInstance($definition));
    }

    public function testIsArray(): void
    {
        $definition = [];

        $this->assertTrue($this->definitionInfo->isArray($definition));
    }

    public function testIsNotArray(): void
    {
        $definition = 0;

        $this->assertFalse($this->definitionInfo->isArray($definition));
    }

    public function testIsScalar(): void
    {
        $stringDefinition = '';
        $floatDefinition = 0.123;
        $integerDefinition = 123;
        $boolDefinition = true;
        $nullDefinition = null;

        $this->assertTrue($this->definitionInfo->isScalar($stringDefinition));
        $this->assertTrue($this->definitionInfo->isScalar($floatDefinition));
        $this->assertTrue($this->definitionInfo->isScalar($integerDefinition));
        $this->assertTrue($this->definitionInfo->isScalar($boolDefinition));
        $this->assertTrue($this->definitionInfo->isScalar($nullDefinition));
    }

    public function testIsNotScalar(): void
    {
        $objectDefinition = $this;
        $arrayDefinition = [];

        $this->assertFalse($this->definitionInfo->isScalar($objectDefinition));
        $this->assertFalse($this->definitionInfo->isScalar($arrayDefinition));
    }

    public function testIsResrouce(): void
    {
        $definition = tmpfile();

        $this->assertTrue($this->definitionInfo->isResource($definition));
    }

    public function testIsNotResource(): void
    {
        $scalarDefinition = 0;
        $arrayDefinition = [];
        $objectDefinition = $this;

        $this->assertFalse($this->definitionInfo->isResource($scalarDefinition));
        $this->assertFalse($this->definitionInfo->isResource($arrayDefinition));
        $this->assertFalse($this->definitionInfo->isResource($objectDefinition));
    }

    public function testIsResolvableArray(): void
    {
        $definition = [
            'definition' => WithoutConstructor::class,
        ];

        $this->assertTrue($this->definitionInfo->isResolvableArray($definition));
    }

    public function testIsNotResolvableArray(): void
    {
        $definition1 = 'test';

        $definition2 = [
            'testKey1' => 'testValue1',
        ];

        $this->assertFalse($this->definitionInfo->isResolvableArray($definition1));
        $this->assertFalse($this->definitionInfo->isResolvableArray($definition2));
    }
}