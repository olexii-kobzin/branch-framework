<?php
declare(strict_types=1);

namespace Branch\Container;

use Branch\Interfaces\Container\DefinitionInfoInterface;

class DefinitionInfo implements DefinitionInfoInterface
{
    public function isTransient($definition): bool
    {
        return (
                $this->isResolvableArray($definition)
                && empty($definition['singleton'])
            )
            || (
                !$this->isResolvableArray($definition)
                && $this->isArray($definition)
            )
            || (
                !$this->isClass($definition) 
                && $this->isScalar($definition)
            )
            || $this->isInstance($definition)
            || $this->isResource($definition);
    }

    public function isClass($definition): bool
    {
        return is_string($definition)
            && class_exists($definition);
    }

    public function isArrayClass($definition): bool
    {
        return $this->isResolvableArray($definition)
            && $this->isClass($definition['definition']);
    }

    public function isClosure($definition): bool
    {
        return $this->isInstance($definition)
            && $definition instanceof \Closure;
    }

    public function isArrayClosure($definition): bool
    {
        return $this->isResolvableArray($definition)
            && $this->isClosure($definition['definition']);
    }

    public function isInstance($definition): bool
    {
        return is_object($definition);
    }

    public function isArray($definition): bool
    {
        return is_array($definition);
    }

    public function isScalar($definition): bool
    {
        return is_string($definition)
            || is_float($definition)
            || is_integer($definition)
            || is_bool($definition)
            || is_null($definition);
    }

    public function isResource($definition): bool
    {
        return is_resource($definition);
    }

    public function isResolvableArray($definition): bool
    {
        return $this->isArray($definition)
            && !empty($definition['definition']);
    }
}