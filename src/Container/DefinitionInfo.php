<?php
declare(strict_types=1);

namespace Branch\Container;

use Branch\Interfaces\Container\DefinitionInfoInterface;

class DefinitionInfo implements DefinitionInfoInterface
{
    public function isTransient($definition): bool
    {
        return $this->isArrayObjectDefinition($definition)
            && empty($definition['singleton']);
    }

    public function isArrayObjectDefinition($definition): bool
    {
        return is_array($definition)
            && isset($definition['class'])
            && is_string($definition['class']) 
            && class_exists($definition['class']);
    }

    public function isStringObjectDefinition($definition): bool
    {
        return is_string($definition)
            && class_exists($definition);
    }

    public function isInstanceDefinition($definition): bool
    {
        return is_object($definition);
    }

    public function isClosureDefinition($definition): bool
    {
        return $this->isInstanceDefinition($definition)
            && $definition instanceof \Closure;
    }

    public function isArrayDefinition($definition): bool
    {
        return is_array($definition);
    }

    public function isScalarDefinition($definition): bool
    {
        return is_string($definition)
            || is_float($definition)
            || is_integer($definition)
            || is_bool($definition)
            || is_null($definition);
    }

    public function isResourceDefinition($definition): bool
    {
        return is_resource($definition);
    }
}