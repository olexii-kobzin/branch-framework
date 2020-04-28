<?php
declare(strict_types=1);

namespace Branch\Container;

use Branch\Interfaces\Container\ContainerInterface;
use Closure;

class DefinitionHelper
{
    public static function isTransient($definition): bool
    {
        return self::isArrayObjectDefinition($definition)
            && isset($definition['type'])
            && $definition['type'] === ContainerInterface::DI_TYPE_TRANSIENT;
    }

    public static function isArrayObjectDefinition($definition): bool
    {
        return is_array($definition)
            && isset($definition['class'])
            && is_string($definition['class']) 
            && class_exists($definition['class']);
    }

    public static function isStringObjectDefinition($definition): bool
    {
        return is_string($definition)
            && class_exists($definition);
    }

    public static function isInstanceDefinition($definition): bool
    {
        return is_object($definition);
    }

    public static function isClosureDefinition($definition): bool
    {
        return self::isInstanceDefinition($definition)
            && $definition instanceof Closure;
    }

    public static function isArrayDefinition($definition): bool
    {
        return is_array($definition);
    }

    public static function isScalarDefinition($definition): bool
    {
        return is_string($definition)
            || is_float($definition)
            || is_integer($definition)
            || is_bool($definition)
            || is_null($definition);
    }

    public static function isResourceDefinition($definition): bool
    {
        return is_resource($definition);
    }
}