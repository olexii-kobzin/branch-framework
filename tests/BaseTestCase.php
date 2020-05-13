<?php
declare(strict_types=1);

namespace Branch\Tests;

use PHPUnit\Framework\TestCase;

class BaseTestCase extends TestCase
{
    protected function getMethodReflection($item, string $method): \ReflectionMethod
    {
        $classReflection = new \ReflectionClass($item);
        $methodReflection = $classReflection->getMethod($method);
        $methodReflection->setAccessible(true);

        return $methodReflection;
    }

    protected function getPropertyReflection($item, string $property): \ReflectionProperty
    {
        $classReflection = new \ReflectionClass($item);
        $propertyReflection = $classReflection->getProperty($property);
        $propertyReflection->setAccessible(true);

        return $propertyReflection;
    }
}