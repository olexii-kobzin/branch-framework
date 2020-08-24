<?php
namespace Branch\Interfaces\Container;

interface DefinitionInfoInterface
{
    public function isTransient($definition): bool;

    public function isClass($definition): bool;

    public function isClassArray($definition): bool;

    public function isClosure($definition): bool;

    public function isClosureArray($definition): bool;

    public function isInstance($definition): bool;

    public function isArray($definition): bool;

    public function isScalar($definition): bool;

    public function isResource($definition): bool;

    public function isResolvableArray($definition): bool;
}