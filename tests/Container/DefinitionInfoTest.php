<?php
declare(strict_types=1);

use Branch\Container\DefinitionInfo;
use Branch\Interfaces\Container\ContainerInterface;
use Branch\Tests\BaseTestCase;

class DefinitionInfoTest extends BaseTestCase
{
    protected DefinitionInfo $definitionInfo;

    public function setUp(): void
    {
        $this->definitionInfo = new DefinitionInfo();
    }

    public function testIsTransient(): void
    {
        $definition = [
            'class' => self::class,
            'type' => ContainerInterface::DI_TYPE_TRANSIENT,
        ];

        $this->assertTrue($this->definitionInfo->isTransient($definition));
    }

    public function testIsNotTransient(): void
    {
        $definition = [
            'class' => self::class,
            'type' => ContainerInterface::DI_TYPE_SINGLETON,
        ];

        $this->assertFalse($this->definitionInfo->isTransient($definition));
    }

    public function testIsArrayObjectDefinition(): void
    {
        $definition = [
            'class' => self::class,
        ];

        $this->assertTrue($this->definitionInfo->isArrayObjectDefinition($definition));
    }

    public function testIsNotArrayObjectDefinition(): void
    {
        $notArrayDefinition = self::class;
        $notStringDefinition = [
            'class' => $this,
        ];
        $notExistsDefinition = [
            'class' => 'ClassDoesNotExists',
        ];

        $this->assertFalse($this->definitionInfo->isArrayObjectDefinition($notArrayDefinition));
        $this->assertFalse($this->definitionInfo->isArrayObjectDefinition($notStringDefinition));
        $this->assertFalse($this->definitionInfo->isArrayObjectDefinition($notExistsDefinition));
    }

    public function testIsStringObjectDefinition(): void
    {
        $definition = self::class;

        $this->assertTrue($this->definitionInfo->isStringObjectDefinition($definition));
    }

    public function testIsNotStringObjectDefinition(): void
    {
        $notStringDefinition = $this;
        $notExistsDefinition = 'ClassDoesNotExists';

        $this->assertFalse($this->definitionInfo->isStringObjectDefinition($notStringDefinition));
        $this->assertFalse($this->definitionInfo->isStringObjectDefinition($notExistsDefinition));
    }

    public function testIsInstanceDefinition(): void
    {
        $definition = $this;

        $this->assertTrue($this->definitionInfo->isInstanceDefinition($definition));
    }

    public function testIsNotInstanceDefinition(): void
    {
        $definition = self::class;

        $this->assertFalse($this->definitionInfo->isInstanceDefinition($definition));
    }

    public function testIsArrayDefinition(): void
    {
        $definition = [];

        $this->assertTrue($this->definitionInfo->isArrayDefinition($definition));
    }

    public function testIsClosureDefinition(): void
    {
        $definition = fn() => null;

        $this->assertTrue($this->definitionInfo->isClosureDefinition($definition));
    }

    public function testIsNotClosureDefinition(): void
    {
        $notObjectDefinition = 0;
        $notClosureDefinition = $this;

        $this->assertFalse($this->definitionInfo->isClosureDefinition($notObjectDefinition));
        $this->assertFalse($this->definitionInfo->isClosureDefinition($notClosureDefinition));
    }

    public function testIsNotArrayDefinition(): void
    {
        $definition = 0;

        $this->assertFalse($this->definitionInfo->isArrayDefinition($definition));
    }

    public function testIsScalarDefinition(): void
    {
        $stringDefinition = '';
        $floatDefinition = 0.123;
        $integerDefinition = 123;
        $boolDefinition = true;
        $nullDefinition = null;

        $this->assertTrue($this->definitionInfo->isScalarDefinition($stringDefinition));
        $this->assertTrue($this->definitionInfo->isScalarDefinition($floatDefinition));
        $this->assertTrue($this->definitionInfo->isScalarDefinition($integerDefinition));
        $this->assertTrue($this->definitionInfo->isScalarDefinition($boolDefinition));
        $this->assertTrue($this->definitionInfo->isScalarDefinition($nullDefinition));
    }

    public function testIsNotScalarDefinition(): void
    {
        $objectDefinition = $this;
        $arrayDefinition = [];

        $this->assertFalse($this->definitionInfo->isScalarDefinition($objectDefinition));
        $this->assertFalse($this->definitionInfo->isScalarDefinition($arrayDefinition));
    }

    public function testIsResrouceDefinition(): void
    {
        $definition = tmpfile();

        $this->assertTrue($this->definitionInfo->isResourceDefinition($definition));
    }

    public function testIsNotResourceDefinition(): void
    {
        $scalarDefinition = 0;
        $arrayDefinition = [];
        $objectDefinition = $this;

        $this->assertFalse($this->definitionInfo->isResourceDefinition($scalarDefinition));
        $this->assertFalse($this->definitionInfo->isResourceDefinition($arrayDefinition));
        $this->assertFalse($this->definitionInfo->isResourceDefinition($objectDefinition));
    }
}